<?php
namespace CRON\Playground\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "CRON.Playground".       *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * @property \TYPO3\Neos\Domain\Model\Site site
 * @property \TYPO3\TYPO3CR\Domain\Service\Context context
 * @Flow\Scope("singleton")
 */
class NodecruncherCommandController extends \TYPO3\Flow\Cli\CommandController {

	const TEST_NODE_NAME = 'test';

	/**
	 * Inject PersistenceManagerInterface
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\NodeNameGenerator
	 */
	protected $nodeNameGenerator;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->site = $this->siteRepository->findFirstOnline();
		$this->context = $this->contextFactory->create();
	}

	private function tag($content, $tag = 'p') {
		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->writeElement($tag, $content);
		return $xml->outputMemory();
	}

	private function generateRandomPageInNode(NodeInterface $node) {

		$title = \TYPO3\Faker\Lorem::sentence(2);
		$node = $node->createNode(
			$this->nodeNameGenerator->generateUniqueNodeName($node, $title),
			$this->nodeTypeManager->getNodeType('TYPO3.Neos.NodeTypes:Page')
		);
		$node->setProperty('title', $title);

		$mainNode = $node->getNode('main');

		// generate random text data in the main content collection
		for ($i=0;$i<200;$i++) {
			$title = \TYPO3\Faker\Lorem::sentence();
			$textNode = $mainNode->createNode(
				$this->nodeNameGenerator->generateUniqueNodeName($node, $title),
				$this->nodeTypeManager->getNodeType('TYPO3.Neos.NodeTypes:Text')
			);
			$textNode->setProperty('title', $title);
			$textNode->setProperty('text', $this->tag(\TYPO3\Faker\Lorem::paragraph(10)));
		}
	}

	private function reportMemoryUsage() {
		$this->outputLine('mem: %.1f MB', [memory_get_usage()/1024/1024]);
	}

	/**
	 * Create Nodes
	 *
	 * This command creates a quite large number of nodes, trying to trigger a Doctrine Timeout
	 *
	 * @param int $count number of nodes to create
	 * @return void
	 */
	public function createCommand($count) {

		$this->reportMemoryUsage();

		$rootNode = $this->context->getNode('/sites/'.$this->site->getName());

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
			}
		}

		$this->outputLine('Nodecruncher in action, creating %d documents..', [$count]);
		$this->reportMemoryUsage();

		$this->output->progressStart($count);
		for ($i=0;$i<$count;$i++) {
			$this->generateRandomPageInNode($testNode);
			$this->output->progressAdvance();
			$this->persistenceManager->persistAll();
			if ($i % 1 == 0) {
				$this->reportMemoryUsage();
			}
		}
		$this->output->progressFinish();

	}

}