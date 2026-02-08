<?php

namespace PhpLocate;

use DOMDocument;
use PhpLocate\Builder\TypeToNodeService;
use PhpLocate\Internal\XMLNode;
use PhpLocate\Suspects\AttributeArgTarget;
use PhpLocate\Suspects\AttributeWithArgs;
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
		$fileNode = $node->getFirstNode('/files')->addChild('file');
		
		$service = new PHPDiscoveryService(new TypeToNodeService());
		$service->discoverInFile(__DIR__ . '/Suspects/MyClass.php', $fileNode);
		
		$this->node = $fileNode;
	}
	
	#[Test]
	public function test(): void {
		$t = fn(string $xpath, string ...$args) => $this->node?->getFirstString(vsprintf($xpath, $args));
		self::assertEquals(MyClass::class, $t('//class[attribute/@name = "%s"]/@name', ClassAttributeA::class));
		self::assertEquals('test', $t('//class/method[attribute/@name = "%s"]/@name', MethodAttributeA::class));
		self::assertEquals('void', $t('//class/method[@name="test"]/return/const/@name'));
	}

	#[Test]
	public function testAttributeArguments(): void {
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$doc->appendChild($doc->createElement('files'));
		$node = new XMLNode($doc->documentElement); // @phpstan-ignore-line
		$fileNode = $node->getFirstNode('/files')->addChild('file');
		$service = new PHPDiscoveryService(new TypeToNodeService());
		$service->discoverInFile(__DIR__ . '/Suspects/AttributeArguments.php', $fileNode);

		self::assertEquals('<name>', $fileNode->getFirstString(sprintf('//class[@name="%s"]/attribute[@name="%s"]/prop[@name="firstname"]/value', AttributeArgTarget::class, AttributeWithArgs::class)));
		self::assertTrue($fileNode->has(sprintf('//class[@name="%s"]/attribute[@name="%s"]/prop[@name="age"]', AttributeArgTarget::class, AttributeWithArgs::class)));
		self::assertFalse($fileNode->has(sprintf('//class[@name="%s"]/attribute[@name="%s"]/prop[@name="age"]/value', AttributeArgTarget::class, AttributeWithArgs::class)));
	}
}
