<?php

namespace PhpLocate;

use DOMDocument;
use PhpLocate\Builder\TypeToNodeService;
use PhpLocate\Internal\XMLNode;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PHPDiscoveryServiceStaticVariableTest extends TestCase {
	#[Test]
	#[RunInSeparateProcess]
	public function staticLocalVariableDoesNotAbortDiscovery(): void {
		$doc = new DOMDocument();
		$doc->appendChild($doc->createElement('files'));
		$node = new XMLNode($doc->documentElement); // @phpstan-ignore-line
		$fileNode = $node->getFirstNode('/files')->addChild('file');

		$service = new PHPDiscoveryService(new TypeToNodeService());
		$service->discoverInFile(__DIR__ . '/Suspects/StaticLocalVariable.php', $fileNode);

		self::assertSame(
			['container', 'discoveredAfterStaticLocalVariable'],
			$fileNode->getStrings('//function/@name'),
		);
	}
}
