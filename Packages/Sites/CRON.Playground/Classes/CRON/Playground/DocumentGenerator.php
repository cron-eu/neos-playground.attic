<?php
namespace CRON\Playground;

/**
 * Created by PhpStorm.
 * User: lazarrs
 * Date: 18.06.15
 * Time: 19:26
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * @property \TYPO3\TYPO3CR\Domain\Service\Context context
 */
class DocumentGenerator  {

	/**
	 * Inject PersistenceManagerInterface
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

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
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->context = $this->contextFactory->create();
	}

	private function tag($content, $tag = 'p') {
		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->writeElement($tag, $content);
		return $xml->outputMemory();
	}

	public function clearState() {
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
		$this->contextFactory->reset();
		$this->initializeObject();
	}

	/**
	 * Generates a NEOS Page with some random Faker Content
	 *
	 * @param string $path
	 * @param int $count
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
	 */
	public function generateFakerPage($path, $count) {

		$node = $this->context->getNode($path);

		$title = \TYPO3\Faker\Lorem::sentence(2);
		$node = $node->createNode(
			$this->nodeNameGenerator->generateUniqueNodeName($node, $title),
			$this->nodeTypeManager->getNodeType('TYPO3.Neos.NodeTypes:Page')
		);
		$node->setProperty('title', $title);

		$mainNode = $node->getNode('main');

		// generate random text data in the main content collection
		for ($i=0;$i<$count;$i++) {
			$title = \TYPO3\Faker\Lorem::sentence();
			$textNode = $mainNode->createNode(
				$this->nodeNameGenerator->generateUniqueNodeName($node, $title),
				$this->nodeTypeManager->getNodeType('TYPO3.Neos.NodeTypes:Text')
			);
			$textNode->setProperty('title', $title);
			$textNode->setProperty('text', $this->tag(\TYPO3\Faker\Lorem::paragraph(10)));
		}
		return $node;
	}
}