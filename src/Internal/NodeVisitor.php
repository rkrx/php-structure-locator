<?php

namespace PhpLocate\Internal;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeDumper;
use PhpParser\NodeVisitorAbstract;

class NodeVisitor extends NodeVisitorAbstract {
	//private $onExit = null;
	
	//public function enterNode(Node $astNode) {
	//}
	
	public function leaveNode(Node $astNode) {
		if($astNode instanceof Namespace_) {
			//foreach($astNode->stmts as $stmt) {
			//	$this->interpreteAst($stmt, $node, $childCtx);
			//}
		} elseif($astNode instanceof Use_) {
			// Do nothing for use statements in this context
		} elseif($astNode instanceof Class_) {
			$classNode = $node->addChild('class', [
				'fqname' => (string) $astNode->namespacedName,
				'final' => $astNode->isFinal() ? 'true' : 'false',
				'abstract' => $astNode->isAbstract() ? 'true' : 'false',
			]);
			foreach($astNode->stmts as $stmt) {
				$this->interpreteAst($stmt, $classNode, $ctx);
			}
		} else {
			$dumper = new NodeDumper;
			echo $dumper->dump($astNode) . "\n";
		}
		
		return null;
	}
}