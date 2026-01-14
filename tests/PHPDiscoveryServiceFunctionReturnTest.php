<?php

namespace PhpLocate;

use DOMDocument;
use PhpLocate\Builder\TypeToNodeService;
use PhpLocate\Internal\XMLNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PHPDiscoveryServiceFunctionReturnTest extends TestCase {
	private ?XMLNode $node = null;

	public function setUp(): void {
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$doc->appendChild($doc->createElement('files'));
		$node ??= new XMLNode($doc->documentElement); // @phpstan-ignore-line
		$fileNode = $node->getFirstNode('/files')->addChild('file');

		$service = new PHPDiscoveryService(new TypeToNodeService());
		$service->discoverInFile(__DIR__ . '/Suspects/functions.php', $fileNode);

		$this->node = $fileNode;
	}

	#[Test]
	public function returnNodeIsChildOfFunction(): void {
		$t = fn(string $xpath) => $this->node?->getFirstString($xpath);

		self::assertEquals('test', $t('//function/@name'));
		self::assertEquals('bool', $t('//function/return/const[@name="bool"]/@name'));
		self::assertEquals('int', $t('//function/return/const[@name="int"]/@name'));
		self::assertEquals('float', $t('//function/return/const[@name="float"]/@name'));
		self::assertEquals('string', $t('//function/return/const[@name="string"]/@name'));
	}
}

