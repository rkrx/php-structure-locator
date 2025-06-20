<?php

namespace PhpLocate\Internal;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNamedNodeMap;
use DOMNode;
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
		$result = $this->xpath->evaluate(sprintf("count(%s)", $xpath));
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
		$children = $this->xpath->query($xpath);
		if($children === false) {
			throw new DOMException("Invalid XPath query: $xpath");
		}
		foreach($children as $child) {
			yield new XMLNode($child);
		}
	}
	
	/**
	 * @param string $xpath
	 * @param bool $require
	 * @return ($require is true ? XMLNode : XMLNode|null)
	 * @throws DOMException
	 */
	public function getFirstNode(string $xpath, bool $require = false): ?XMLNode {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		$this->xpath ??= new DOMXPath($doc);
		$children = $this->xpath->query($xpath);
		if($children === false) {
			throw new DOMException("Invalid XPath query: $xpath");
		}
		
		if($children->length > 0) {
			$node = $children->item(0);
			if($node !== null) {
				return new XMLNode($node);
			}
		}
		
		if($require) {
			throw new DOMException("No node found for XPath query: $xpath");
		}
		
		return null;
	}
	
	public function getFirstString(string $xpath, ?string $default = null): ?string {
		/** @var DOMDocument $doc */
		$doc = $this->node->ownerDocument;
		$this->xpath ??= new DOMXPath($doc);
		$value = $this->xpath->evaluate('string('.$xpath.')');
		if($value === false && func_num_args() === 1) {
			throw new DOMException("Invalid XPath query: $xpath");
		}
		if($value === false) {
			$value = null;
		}
		return $value ?? $default;
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
	 * @throws DOMException
	 */
	public function getAttribute(string $name, ?string $default = null): ?string {
		$node = $this->node;
		if($node instanceof DOMElement) {
			$attribute = $node->getAttribute($name);
			if((string) $attribute === '') {
				if($default === null) {
					throw new DOMException("Attribute '$name' not found in node {$node->nodeName}");
				}
				return $default;
			}
			return $attribute;
		}
		throw new DOMException("Node is not an element: {$node->nodeName}");
	}
	
	/**
	 * @param string $name
	 * @param bool|int|float|string $value
	 * @return $this
	 * @throws DOMException
	 */
	public function setAttribute(string $name, bool|int|float|string $value): self {
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
	 * @param string $name
	 * @param array<string, string> $attributes
	 * @return XMLNode
	 * @throws \DOMException
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
			while($this->node->hasAttributes()) {
				/** @var DOMAttr $item */
				$item = $this->node->attributes->item(0);
				$this->node->removeAttribute($item->name);
			}
			
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