<?php

namespace PhpLocate;

use DOMDocument;
use DOMElement;
use Generator;
use PhpLocate\Internal\XMLNode;
use RuntimeException;

class Index {
	public static function fromFile(string $path): Index {
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		if(file_exists($path)) {
			if(!$doc->load($path)) {
				throw new RuntimeException("Failed to load XML from $path");
			}
		} else {
			$doc->loadXML('<files/>');
		}
		
		/** @var DOMElement $node */
		$node = $doc->documentElement;
		return new self(new XMLNode($node));
	}
	
	public function __construct(
		private readonly XMLNode $node
	) {
		$doc = $this->node->getDocument();
		if($doc->documentElement !== null && $doc->documentElement->nodeName !== 'files') {
			$doc->removeChild($doc->documentElement);
		}
		if($doc->childNodes->length === 0) {
			$rootElement = $doc->createElement('files');
			$doc->appendChild($rootElement);
		}
	}
	
	/**
	 * @param string $xpath
	 * @return Generator<XMLNode>
	 * @throws \DOMException
	 */
	public function getNodes(string $xpath): Generator {
		yield from $this->node->getNodes($xpath);
	}
	
	public function getFirstNode(string $xpath): XMLNode {
		return $this->node->getFirstNode($xpath);
	}
	
	public function tryGetFirstNode(string $xpath): ?XMLNode {
		return $this->node->tryGetFirstNode($xpath);
	}
	
	public function getFirstString(string $xpath, ?string $default = null): ?string {
		if(func_num_args() < 2) {
			return $this->node->getFirstString($xpath);
		}
		return $this->node->getFirstString($xpath, $default);
	}
	
	/**
	 * @return array<string, string>
	 */
	public function getFilePathsAndLastModifiedDate(): array {
		$fileNodes = $this->node->getNodes('/files/*');
		$result = [];
		foreach($fileNodes as $fileNode) {
			$result[$fileNode->getAttr('path')] = (string) $fileNode->getAttr('mtime');
		}
		return $result;
	}
	
	public function addFile(string $relativePath, int $mtime, string $hash): XMLNode {
		$fileNode = $this->node->tryGetFirstNode(sprintf('/files/file[@path="%s"]', strtr($relativePath, ['\\' => '/', '"' => '\\"'])));
		if($fileNode === null) {
			$fileNode = $this->node->getFirstNode('/files')->addChild('file');
		}
		$fileNode->setAttr('path', $relativePath);
		$fileNode->setAttr('mtime', $mtime);
		$fileNode->setAttr('hash', $hash);
		return $fileNode;
	}
	
	public function saveTo(string $path): void {
		$doc = $this->node->getDocument();
		$doc->formatOutput = true;
		$contents = $doc->saveXML();
		if($contents === false) {
			throw new RuntimeException("Failed to save XML to $path");
		}
		file_put_contents($path, $contents);
	}
	
	public function removeFile(string $relativePath): void {
		$filesNodes = $this->node->getNodes(sprintf('/files/file[@path="%s"]', strtr($relativePath, ['\\' => '/', '"' => '\\"'])));
		foreach($filesNodes as $filesNode) {
			$filesNode->remove();
		}
	}
}