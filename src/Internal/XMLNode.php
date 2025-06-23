<?php

namespace PhpLocate\Internal;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNamedNodeMap;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Generator;

class XMLNode {
	private ?DOMXPath $xpath = null;
	
	public function __construct(
		private readonly DOMNode $node,
	) {}
	
	public function getDocument(): DOMDocument {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		return $doc;
	}
	
	public function has(string $xpath): bool {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		$this->xpath ??= new DOMXPath($doc);
		$result = $this->xpath->evaluate(sprintf("count(%s)", $xpath), $this->node);
		return $result > 0;
	}
	
	/**
	 * @param string $xpath
	 * @return Generator<XMLNode>
	 */
	public function getNodes(string $xpath): Generator {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		$this->xpath ??= new DOMXPath($doc);
		$children = $this->xpath->query($xpath, $this->node);
		if($children === false) {
			throw new DOMException("Invalid XPath query: $xpath");
		}
		foreach($children as $child) {
			yield new XMLNode($child);
		}
	}
	
	/**
	 * @param string $xpath
	 * @return XMLNode
	 * @throws DOMException
	 */
	public function getFirstNode(string $xpath): XMLNode {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		$this->xpath ??= new DOMXPath($doc);
		$children = $this->xpath->query($xpath, $this->node);
		if($children === false) {
			throw new DOMException("Invalid XPath query: $xpath");
		}
		
		if($children->length > 0) {
			$node = $children->item(0);
			if($node !== null) {
				return new XMLNode($node);
			}
		}
		
		throw new RuntimeDOMException("No node found for XPath query: $xpath");
	}
	
	/**
	 * @param string $xpath
	 * @return XMLNode|null
	 * @throws DOMException
	 */
	public function tryGetFirstNode(string $xpath): ?XMLNode {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		$this->xpath ??= new DOMXPath($doc);
		$children = $this->xpath->query($xpath, $this->node);
		if($children === false) {
			throw new DOMException("Invalid XPath query: $xpath");
		}
		
		if($children->length > 0) {
			$node = $children->item(0);
			if($node !== null) {
				return new XMLNode($node);
			}
		}
		
		return null;
	}
	
	public function getFirstString(string $xpath, ?string $default = null): ?string {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		$this->xpath ??= new DOMXPath($doc);
		
		/** @var false|DOMNodeList<DOMNode> $node */
		$node = $this->xpath->evaluate($xpath, $this->node);
		if($node === false) {
			throw new DOMException("Invalid XPath query: $xpath");
		}
		if($node->length < 1) {
			if(func_num_args() === 1) {
				throw new RuntimeDOMException("No node found for XPath query: $xpath");
			}
			return $default;
		}
		return $node->item(0)?->nodeValue;
	}
	
	/**
	 * @return array<string, string>
	 */
	public function getAttributes(): array {
		$map = $this->node->attributes;
		$result = [];
		if($map instanceof DOMNamedNodeMap) {
			foreach($map as $attribute) {
				if($attribute instanceof DOMAttr) {
					$result[$attribute->name] = $attribute->value;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * @param string $name
	 * @param string|null $default
	 * @return ($default is string ? string : string|null)
	 * @throws RuntimeDOMException
	 */
	public function getAttr(string $name, ?string $default = null): ?string {
		$node = $this->node;
		if($node instanceof DOMElement) {
			$attribute = $node->getAttribute($name);
			if((string) $attribute === '') {
				if($default === null) {
					throw new RuntimeDOMException("Attribute '$name' not found in node {$node->nodeName}");
				}
				return $default;
			}
			return $attribute;
		}
		throw new RuntimeDOMException("Node is not an element: {$node->nodeName}");
	}
	
	/**
	 * @param string $name
	 * @param bool|int|float|string $value
	 * @return $this
	 * @throws DOMException
	 */
	public function setAttr(string $name, bool|int|float|string $value): self {
		if(!$this->node instanceof DOMElement) {
			throw new DOMException("Node is not an element: {$this->node->nodeName}");
		}
		if(is_bool($value)) {
			$value = $value ? 'true' : 'false';
		}
		$this->node->setAttribute($name, (string) $value);
		return $this;
	}
	
	/**
	 * @return XMLNode
	 * @throws RuntimeDOMException
	 */
	public function parent(): XMLNode {
		if($this->node->parentNode !== null) {
			return new XMLNode($this->node->parentNode);
		}
		throw new RuntimeDOMException("No parent node found for node {$this->node->nodeName}");
	}
	
	/**
	 * @return XMLNode|null
	 */
	public function tryParent(): ?XMLNode {
		if($this->node->parentNode !== null) {
			return new XMLNode($this->node->parentNode);
		}
		return null;
	}
	
	/**
	 * @param string $name
	 * @param array<string, string> $attributes
	 * @return XMLNode
	 * @throws DOMException
	 */
	public function addChild(string $name, array $attributes = []): XMLNode {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		$element = $doc->createElement($name);
		$this->node->appendChild($element);
		
		foreach($attributes as $key => $value) {
			$element->setAttribute($key, $value);
		}
		
		return new XMLNode($element);
	}
	
	public function remove(): void {
		$this->node->parentNode?->removeChild($this->node);
	}
	
	public function clear(): void {
		if($this->node instanceof DOMElement) {
			while($this->node->hasChildNodes()) {
				/** @var DOMNode $child */
				$child = $this->node->firstChild;
				$this->node->removeChild($child);
			}
		}
	}
	
	public function __toString() {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		return (string) $doc->saveXML($this->node);
	}
}