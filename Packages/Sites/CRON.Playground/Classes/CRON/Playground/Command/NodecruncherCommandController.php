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

	const NODE_NAME = 'nodecruncher-test';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\NodeNameGenerator
	 */
	protected $nodeNameGenerator;

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

	/**
	 * Gets the folder where we want to put our data in
	 *
	 * @param bool $create
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node|NodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
	 */
	private function getTestsuiteFolderNode($create=false) {
		$rootNode = $this->contextFactory->create()->getNode('/sites/playground');
		$node = $rootNode->getNode(self::NODE_NAME);
		if (!$node && $create) {
			$node = $rootNode->createNode(
				self::NODE_NAME,
				$this->nodeTypeManager->getNodeType('TYPO3.Neos.NodeTypes:Page')
			);
		}
		return $node;
	}

	/**
	 * Create Nodes
	 *
	 * This command creates a quite large number of nodes, trying to trigger a Doctrine Timeout
	 *
	 * @param int $count number of nodes to create
	 * @param int $batchSize batch size after a clearState() will be performed
	 * @param string $page put generated pages in a page with this title
	 * @param bool $purge purge old data
	 * @param bool $verbose show memory usage after each iteration
	 * @return void
	 */
	public function createCommand($count, $batchSize, $page=false, $purge='', $verbose=false) {

		/** @var DocumentGenerator $documentGenerator */
		$documentGenerator = new DocumentGenerator();

		$node = $this->getTestsuiteFolderNode(true);

		if ($purge) {
			$this->purge($node);
		}

		if ($page) {
			$node = $node->createNode(
				$this->nodeNameGenerator->generateUniqueNodeName($node, $page),
				$this->nodeTypeManager->getNodeType('TYPO3.Neos.NodeTypes:Page')
			);
			/** @var NodeInterface $node */
			$node->setProperty('title', $page);
		}

		$this->outputLine('Nodecruncher in action, creating %d documents using batch size of %d',
			[$count, $batchSize]);

		if ($verbose) $this->reportMemoryUsage();

		$this->output->progressStart($count);
		$path = $node->getPath();

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
		if ($testNode = $this->getTestsuiteFolderNode()) $this->purge($testNode);
	}

}
