<?php

namespace PhpLocate\Builder;

use PhpLocate\Internal\XMLNode;
use PhpParser\Node;

class TypeToNodeService {
	public function typeToString(object $type, XMLNode $node): void {
		if($type instanceof Node\NullableType) {
			if($type->type === null) { // @phpstan-ignore-line
				return;
			}
			
			$node->addChild('nullable', []);
			$this->typeToString($type->type, $node);
		} elseif($type instanceof Node\UnionType) {
			foreach($type->types as $unionType) {
				if($unionType instanceof Node\NullableType) { // @phpstan-ignore-line
					if($unionType->type === null) {
						continue;
					}
					$node->addChild('nullable', []);
					$this->typeToString($unionType->type, $node); // @phpstan-ignore-line
				} else {
					$this->typeToString($unionType, $node);
				}
			}
		} else {
			$node->addChild('named', ['name' => $type->toString()]); // @phpstan-ignore-line
		}
	}
}