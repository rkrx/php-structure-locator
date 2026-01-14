<?php

namespace PhpLocate;

use PhpLocate\Builder\TypeToNodeService;
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
	public function __construct(
		private readonly TypeToNodeService $typeToNodeService
	) {}
	
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
		$modifiedStmts = $traverser->traverse($stmts);
		
		foreach($modifiedStmts as $astNode) {
			$this->analyzeAstNode($astNode, $node, new AstContext());
		}
	}
	
	private function analyzeAstNode(Node $astNode, XMLNode $node, AstContext $ctx): void {
		if($astNode instanceof Node\Stmt\Namespace_) {
			foreach($astNode->stmts as $stmt) {
				$this->analyzeAstNode($stmt, $node, $ctx);
			}
		} elseif($astNode instanceof Node\Stmt\Function_) {
			$attr = ['name' => (string) $astNode->name];
			
			$functionNode = $node->addChild('function', $attr);
			
			if($astNode->returnType !== null) {
				$returnNode = $node->addChild('return', []);
				$this->typeToNodeService->typeToString($astNode->returnType, $returnNode);
			}
			
			foreach($astNode->getAttrGroups() as $attrGroup) {
				$this->analyzeAstNode($attrGroup, $functionNode, $ctx);
			}
			
			foreach($astNode->getParams() as $paramNode) {
				$this->analyzeAstNode($paramNode, $functionNode, $ctx);
			}
			
			foreach($astNode->stmts as $stmt) {
				$this->analyzeAstNode($stmt, $functionNode, $ctx);
			}
		} elseif($astNode instanceof Node\Stmt\Trait_) {
			$attr = ['name' => (string) $astNode->namespacedName];
			
			$traitNode = $node->addChild('trait', $attr);
			
			foreach($astNode->stmts as $stmt) {
				$this->analyzeAstNode($stmt, $traitNode, $ctx);
			}
			
			foreach($astNode->attrGroups as $attrGroup) {
				$this->analyzeAstNode($attrGroup, $traitNode, $ctx);
			}
		} elseif($astNode instanceof Node\Stmt\Class_) {
			$attr = ['name' => (string) $astNode->namespacedName];
			
			if($astNode->isFinal()) {
				$attr['final'] = 'true';
			}
			
			if($astNode->isAbstract()) {
				$attr['abstract'] = 'true';
			}
			
			if($astNode->isReadonly()) {
				$attr['readonly'] = 'true';
			}

			if($astNode->isAnonymous()) {
				$attr['anonymous'] = 'true';
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
				$this->analyzeAstNode($attrGroup, $classNode, $ctx);
			}
			
			foreach($astNode->stmts as $stmt) {
				$this->analyzeAstNode($stmt, $classNode, $ctx);
			}
		} elseif($astNode instanceof Node\AttributeGroup) {
			foreach($astNode->attrs as $attrNode) {
				$this->analyzeAstNode($attrNode, $node, $ctx);
			}
		} elseif($astNode instanceof Node\Attribute) {
			$name = $astNode->name;
			if($name instanceof FullyQualified) {
				$name = $name->name;
			}
			$attr = ['name' => $name];
			$attrNode = $node->addChild('attribute', $attr);
			foreach($astNode->args as $argNode) {
				$this->analyzeAstNode($argNode, $attrNode, $ctx);
			}
		} elseif($astNode instanceof Node\Stmt\TraitUse) {
			foreach($astNode->traits as $traitNode) {
				/** @var null|string $namespacedName */
				$namespacedName = $traitNode->namespacedName ?? null;
				$attr = ['name' => (string) ($namespacedName ?? $traitNode->name)];
				$node->addChild('use', $attr);
			}
			
			//foreach($astNode->getMethods() as $methodNode) {
			//	$this->interpreteAst($methodNode, $traitUseNode, $ctx);
			//}
		} elseif($astNode instanceof Node\Stmt\Property) {
			foreach($astNode->props as $propNode) {
				/** @var string $name */
				$name = $propNode->name;
				$attr = ['name' => $name];
				
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
				
				if($astNode->isReadOnly()) {
					$attr['readonly'] = 'true';
				}
				
				if($astNode->type !== null) {
					$returnNode = $node->addChild('property', $attr);
					$this->typeToNodeService->typeToString($astNode->type, $returnNode);
				}
				
				$propertyNode = $node->addChild('property', $attr);
				
				foreach($astNode->attrGroups as $attrGroup) {
					$this->analyzeAstNode($attrGroup, $propertyNode, $ctx);
				}
			}
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
				$returnNode = $node->addChild('return', []);
				$this->typeToNodeService->typeToString($astNode->returnType, $returnNode);
			}
			
			$methodNode = $node->addChild('method', $attr);
			
			foreach($astNode->getAttrGroups() as $attrGroup) {
				$this->analyzeAstNode($attrGroup, $methodNode, $ctx);
			}
			
			foreach($astNode->getParams() as $paramNode) {
				$this->analyzeAstNode($paramNode, $methodNode, $ctx);
			}
		} elseif($astNode instanceof Node\Param) {
			/** @var array<string, string> $attr */
			$name = $astNode->var->name; // @phpstan-ignore-line
			
			/** @var string $paramName */
			$paramName = $name instanceof Node\Expr ? $name->toString() : (string) $name; // @phpstan-ignore-line
			
			$attr = [
				'name' => $paramName,
			];

			if($astNode->byRef) {
				$attr['byRef'] = 'true';
			}
			
			if($astNode->variadic) {
				$attr['variadic'] = 'true';
			}
			
			$paramNode = $node->addChild('param', $attr);
			
			foreach($astNode->attrGroups as $attrGroup) {
				$this->analyzeAstNode($attrGroup, $paramNode, $ctx);;
			}
			
			if($astNode->type !== null) {
				$typeNode = $paramNode->addChild('type', []);
				$this->typeToNodeService->typeToString($astNode->type, $typeNode);
			}
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
			$inspect = match($astNode::class) {
				Node\Stmt\Echo_::class,
				Node\Stmt\InlineHTML::class,
				Node\Stmt\Declare_::class,
				Node\Stmt\Const_::class,
				Node\Stmt\Expression::class,
				Node\Stmt\If_::class,
				Node\Stmt\Foreach_::class,
				Node\Stmt\Return_::class,
				Node\Stmt\Enum_::class,
				Node\Stmt\While_::class,
				Node\Stmt\TryCatch::class,
				Node\Stmt\Nop::class,
				Node\Stmt\ClassConst::class,
				Node\Stmt\Use_::class,
				Node\Stmt\Interface_::class => false,
				default => true
			};
			if($inspect) {
				$dumper = new NodeDumper;
				echo $dumper->dump($astNode) . "\n";
				exit;
			}
		}
	}
}