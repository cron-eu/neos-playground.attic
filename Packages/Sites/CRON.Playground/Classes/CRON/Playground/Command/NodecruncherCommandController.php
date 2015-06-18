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

	/**
	 * Inject PersistenceManagerInterface
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	private function reportMemoryUsage() {
		$this->outputLine(' > mem: %.1f MB', [memory_get_peak_usage()/1024/1024]);
	}

	/**
	 * Create Nodes
	 *
	 * This command creates a quite large number of nodes, trying to trigger a Doctrine Timeout
	 *
	 * @param int $count number of nodes to create
	 * @param int $batchSize batch size after a clearState() will be performed
	 * @return void
	 */
	public function createCommand($count, $batchSize) {

		$this->reportMemoryUsage();

		/** @var DocumentGenerator $documentGenerator */
		$documentGenerator = new DocumentGenerator();

		$rootNode = $this->contextFactory->create()->getNode('/sites/playground');
		$testNode = $rootNode->getNode(self::TEST_NODE_NAME);

		if (!$testNode) {
			$testNode = $rootNode->createNode(
				self::TEST_NODE_NAME,
				$this->nodeTypeManager->getNodeType('TYPO3.Neos.NodeTypes:Page')
			);
		} else {
			// reuse the page, but purge old data..
			/** @var NodeInterface $childNode */
			$this->outputLine('Deleting old stuff..');
			foreach ($testNode->getChildNodes('TYPO3.Neos:Document') as $childNode) {
				$childNode->remove();
				$this->persistenceManager->persistAll();
			}
		}

		$this->outputLine('Nodecruncher in action, creating %d documents using batch size of %d',
			[$count, $batchSize]);

		$this->reportMemoryUsage();

		$this->output->progressStart($count);
		$path = $testNode->getPath();

		$this->nodeTypeManager = null;
		$this->contextFactory = null;

		for ($i=0;$i<$count;$i++) {

			if ($i % $batchSize == 0) {
				$this->persistenceManager->persistAll();
				$documentGenerator = null;
				$this->persistenceManager->clearState();
				$documentGenerator = new DocumentGenerator();
			}

			$documentGenerator->generateFakerPage($path, 10);
			$this->output->progressAdvance();
			$this->reportMemoryUsage();

		}
		$this->output->progressFinish();

	}

}