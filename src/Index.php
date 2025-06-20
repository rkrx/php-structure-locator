<?php

namespace PhpLocate;

use DOMDocument;
use DOMElement;
use PhpLocate\Internal\XMLNode;
use RuntimeException;

class Index {
	public static function fromFile(string $path): Index {
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		if(file_exists($path) && !$doc->load($path)) {
			throw new RuntimeException("Failed to load XML from $path");
		}
		
		/** @var DOMElement $node */
		$node = $doc->documentElement;
		return new self(new XMLNode($node));
	}
	
	public function __construct(
		public readonly XMLNode $node
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
	 * @return array<string, string>
	 */
	public function getFilePathsAndLastModifiedDate(): array {
		$fileNodes = $this->node->getNodes('/files/*');
		$result = [];
		foreach($fileNodes as $fileNode) {
			$result[$fileNode->getAttribute('path')] = (string) $fileNode->getAttribute('mtime');
		}
		return $result;
	}
	
	public function addFile(string $relativePath, int $mtime, string $hash): XMLNode {
		$fileNode = $this->node->getFirstNode(sprintf('/files/file[@path="%s"]', strtr($relativePath, ['\\' => '/', '"' => '\\"'])));
		if($fileNode === null) {
			$fileNode = $this->node->getFirstNode('/files', require: true)->addChild('file');
		}
		$fileNode->setAttribute('path', $relativePath);
		$fileNode->setAttribute('mtime', $mtime);
		$fileNode->setAttribute('hash', $hash);
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