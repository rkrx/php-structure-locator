<?php

namespace PhpLocate;

use DOMDocument;
use PhpLocate\Builder\TypeToNodeService;
use PhpLocate\Internal\XMLNode;
use PhpLocate\Suspects\ClassAttributeA;
use PhpLocate\Suspects\MethodAttributeA;
use PhpLocate\Suspects\MyClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PHPDiscoveryServiceTest extends TestCase {
	private ?XMLNode $node = null;
	
	public function setUp(): void {
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$doc->appendChild($doc->createElement('files'));
		$node ??= new XMLNode($doc->documentElement); // @phpstan-ignore-line
		$fileNode = $node->getFirstNode('/files', require: true)->addChild('file');
		
		$service = new PHPDiscoveryService(new TypeToNodeService());
		$service->discoverInFile(__DIR__ . '/Suspects/MyClass.php', $fileNode);
		
		$this->node = $fileNode;
	}
	
	#[Test]
	public function test(): void {
		$t = fn(string $xpath) => $this->node?->getFirstString($xpath);
		self::assertEquals(MyClass::class, $t('//class[attribute/@name = "' . ClassAttributeA::class . '"]/@name'));
		self::assertEquals('test', $t('//class/method[attribute/@name = "' . MethodAttributeA::class . '"]/@name'));
	}
}