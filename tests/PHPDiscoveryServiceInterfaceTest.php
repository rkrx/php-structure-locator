<?php

namespace PhpLocate;

use DOMDocument;
use PhpLocate\Builder\TypeToNodeService;
use PhpLocate\Internal\XMLNode;
use PhpLocate\Suspects\ClassAttributeA;
use PhpLocate\Suspects\MethodAttributeA;
use PhpLocate\Suspects\MyInterfaceBase;
use PhpLocate\Suspects\MyInterfaceChild;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PHPDiscoveryServiceInterfaceTest extends TestCase {
	private ?XMLNode $node = null;

	public function setUp(): void {
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$doc->appendChild($doc->createElement('files'));
		$node ??= new XMLNode($doc->documentElement); // @phpstan-ignore-line
		$fileNode = $node->getFirstNode('/files')->addChild('file');

		$service = new PHPDiscoveryService(new TypeToNodeService());
		$service->discoverInFile(__DIR__ . '/Suspects/MyInterface.php', $fileNode);

		$this->node = $fileNode;
	}

	#[Test]
	public function discoversInterfaces(): void {
		$t = fn(string $xpath) => $this->node?->getFirstString($xpath);

		self::assertEquals(
			MyInterfaceBase::class,
			$t('//interface[attribute/@name = "' . ClassAttributeA::class . '"]/@name')
		);

		self::assertEquals(
			'baseMethod',
			$t('//interface[@name = "' . MyInterfaceBase::class . '"]/method[attribute/@name = "' . MethodAttributeA::class . '"]/@name')
		);

		self::assertEquals(
			MyInterfaceBase::class,
			$t('//interface[@name = "' . MyInterfaceChild::class . '"]/extends/@name')
		);
	}
}
