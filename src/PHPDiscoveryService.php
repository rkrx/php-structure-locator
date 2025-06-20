<?php

namespace PhpLocate;

use PhpLocate\Internal\AstContext;
use PhpLocate\Internal\XMLNode;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeDumper;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use RuntimeException;

class PHPDiscoveryService {
	/**
	 * Discovers all elements in a PHP file and feeds them info $node
	 *
	 * @param string $path
	 * @param XMLNode $node
	 * @return void
	 */
	public function discoverInFile(string $path, XMLNode $node): void {
		$parser = (new ParserFactory())->createForHostVersion();
		$contents = file_get_contents($path);
		if($contents === false) {
			throw new RuntimeException("Failed to read file: $path");
		}
		
		$node->clear();
		
		$stmts = $parser->parse($contents);
		if($stmts === null) {
			throw new RuntimeException("Failed to parse PHP file: $path");
		}
		
		$traverser = new NodeTraverser;
		$traverser->addVisitor(new NameResolver(null, [
			'preserveOriginalNames' => false,
			'replaceNodes' => true,
		]));
		//$traverser->addVisitor(new NodeVisitor());
		$modifiedStmts = $traverser->traverse($stmts);
		
		foreach($modifiedStmts as $astNode) {
			$this->interpreteAst($astNode, $node, new AstContext());
		}
		
		printf("%s\n", $node);
	}
	
	private function interpreteAst(Node $astNode, XMLNode $node, AstContext $ctx): void {
		if($astNode instanceof Node\Stmt\Namespace_) {
			foreach($astNode->stmts as $stmt) {
				$this->interpreteAst($stmt, $node, $ctx);
			}
		} elseif($astNode instanceof Node\Stmt\Use_) {
			// Do nothing for use statements in this context
		} elseif($astNode instanceof Node\Stmt\Class_) {
			$attr = ['name' => (string) $astNode->namespacedName];
			
			if($astNode->isFinal()) {
				$attr['final'] = 'true';
			}
			
			if($astNode->isAbstract()) {
				$attr['abstract'] = 'true';
			}
			
			if($astNode->extends instanceof FullyQualified) {
				/** @var string|null $namespacedName */
				$namespacedName = $astNode->extends->namespacedName ?? null;
				$attr['extends'] = (string) ($namespacedName ?? $astNode->extends->name);
			}
			
			$classNode = $node->addChild('class', $attr);
			
			foreach($astNode->implements as $interfaceNode) {
				$classNode->addChild('implements', [
					'name' => (string) ($interfaceNode->namespacedName ?? $interfaceNode->name), // @phpstan-ignore-line
				]);
			}
			
			foreach($astNode->attrGroups as $attrGroup) {
				$this->interpreteAst($attrGroup, $classNode, $ctx);
			}
			
			foreach($astNode->stmts as $stmt) {
				$this->interpreteAst($stmt, $classNode, $ctx);
			}
		} elseif($astNode instanceof Node\AttributeGroup) {
			foreach($astNode->attrs as $attrNode) {
				$this->interpreteAst($attrNode, $node, $ctx);
			}
		} elseif($astNode instanceof Node\Attribute) {
			$name = $astNode->name;
			if($name instanceof FullyQualified) {
				$name = $name->name;
			}
			$attr = ['name' => $name];
			$attrNode = $node->addChild('attribute', $attr);
			foreach($astNode->args as $argNode) {
				$this->interpreteAst($argNode, $attrNode, $ctx);
			}
		} elseif($astNode instanceof Node\Stmt\TraitUse) {
			foreach($astNode->traits as $traitNode) {
				$attr = ['name' => (string) ($traitNode->namespacedName ?? $traitNode->name)];
				$node->addChild('use', $attr);
			}
			
			//foreach($astNode->getMethods() as $methodNode) {
			//	$this->interpreteAst($methodNode, $traitUseNode, $ctx);
			//}
		} elseif($astNode instanceof Node\Stmt\ClassMethod) {
			$attr = ['name' => (string) $astNode->name];
			
			if($astNode->isPublic()) {
				$attr['visibility'] = 'public';
			} elseif($astNode->isProtected()) {
				$attr['visibility'] = 'protected';
			} elseif($astNode->isPrivate()) {
				$attr['visibility'] = 'private';
			}
			
			if($astNode->isStatic()) {
				$attr['static'] = 'true';
			}
			
			if($astNode->isFinal()) {
				$attr['final'] = 'true';
			}
			
			if($astNode->stmts === [] || $astNode->isAbstract()) {
				$attr['abstract'] = 'true';
			}
			
			if($astNode->isMagic()) {
				$attr['magic'] = 'true';
			}
			
			if($astNode->returnType !== null) {
				$type = $this->typeToString($astNode->returnType);
				if($type !== null) {
					$attr['returnType'] = $type;
				}
			}
			
			$methodNode = $node->addChild('method', $attr);
			
			foreach($astNode->getAttrGroups() as $attrGroup) {
				$this->interpreteAst($attrGroup, $methodNode, $ctx);
			}
			
			foreach($astNode->getParams() as $paramNode) {
				$this->interpreteAst($paramNode, $methodNode, $ctx);
			}
		} elseif($astNode instanceof Node\Param) {
			/** @var array<string, string> $attr */
			$name = $astNode->var->name; // @phpstan-ignore-line
			$attr = [
				'name' => $name instanceof Node\Expr ? $name->toString() : (string) $name, // @phpstan-ignore-line
			];
			
			$type = $this->typeToString($astNode->type);
			if($type !== null) {
				$attr['type'] = $this->typeToString($astNode->type);
			}
			
			if($astNode->byRef) {
				$attr['byRef'] = 'true';
			}
			
			if($astNode->variadic) {
				$attr['variadic'] = 'true';
			}
			
			$node->addChild('param', $attr); // @phpstan-ignore-line
		} elseif($astNode instanceof Node\Arg) {
			$attr = [
				'name' => (string) $astNode->name,
				//'value' => (string) $astNode->value
			];
			
			if($astNode->byRef) {
				$attr['byRef'] = 'true';
			}
			
			if($astNode->unpack) {
				$attr['unpack'] = 'true';
			}
			
			$node->addChild('argument', $attr);
		} else {
			$dumper = new NodeDumper;
			echo $dumper->dump($astNode) . "\n";
			exit;
		}
	}
	
	private function typeToString(Node|null $type): ?string {
		if($type === null) {
			return null;
		}
		
		if($type instanceof Node\NullableType) {
			if($type->type === null) { // @phpstan-ignore-line
				return null;
			}
			
			return sprintf('null|%s', $type->type->toString());
		}
		
		return $type->toString(); // @phpstan-ignore-line
	}
}