<?php
namespace CRON\Playground\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "CRON.Playground".       *
 *                                                                        *
 *                                                                        */

use CRON\Playground\DocumentGenerator;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * @Flow\Scope("singleton")
 */
class NodecruncherCommandController extends \TYPO3\Flow\Cli\CommandController {

	const TEST_NODE_NAME = 'test';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	private function reportMemoryUsage() {
		$this->outputLine(' > mem: %.1f MB', [memory_get_peak_usage()/1024/1024]);
	}

	private function purge(NodeInterface $testNode) {
		$this->outputLine('Deleting old stuff in %s..', [$testNode->getPath()]);
		/** @var NodeInterface $childNode */
		foreach ($testNode->getChildNodes('TYPO3.Neos:Document') as $childNode) {
			// speedup things deleting the children using the nodeDataRepository first
			$this->nodeDataRepository->removeAllInPath($childNode->getPath());
			$childNode->remove();
		}
	}

	private function getTestNode() {
		$rootNode = $this->contextFactory->create()->getNode('/sites/playground');
		return $rootNode->getNode(self::TEST_NODE_NAME);
	}

	/**
	 * Create Nodes
	 *
	 * This command creates a quite large number of nodes, trying to trigger a Doctrine Timeout
	 *
	 * @param int $count number of nodes to create
	 * @param int $batchSize batch size after a clearState() will be performed
	 * @param bool $purge purge old data
	 * @param bool $verbose show memory usage after each iteration
	 * @return void
	 */
	public function createCommand($count, $batchSize, $purge=false, $verbose=false) {

		/** @var DocumentGenerator $documentGenerator */
		$documentGenerator = new DocumentGenerator();

		$testNode = $this->getTestNode();

		if (!$testNode) {
			$testNode = $rootNode->createNode(
				self::TEST_NODE_NAME,
				$this->nodeTypeManager->getNodeType('TYPO3.Neos.NodeTypes:Page')
			);
		} else {
			// reuse the page, purge old data if requested
			if ($purge) {
				$this->purge($testNode);
				$documentGenerator->clearState();
			}
		}

		$this->outputLine('Nodecruncher in action, creating %d documents using batch size of %d',
			[$count, $batchSize]);

		if ($verbose) $this->reportMemoryUsage();

		$this->output->progressStart($count);
		$path = $testNode->getPath();

		for ($i=0;$i<$count;$i++) {

			if ($batchSize && $i && $i % $batchSize == 0) {
				$documentGenerator->clearState();
				if ($verbose) $this->reportMemoryUsage();
			}

			$documentGenerator->generateFakerPage($path, 10);
			$this->output->progressAdvance();

		}
		$this->output->progressFinish();

		$this->reportMemoryUsage();
	}

	/**
	 * Purge old data
	 *
	 * @return void
	 */
	public function purgeCommand() {
		if ($testNode = $this->getTestNode()) $this->purge($testNode);
	}

}
