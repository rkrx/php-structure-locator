<?php

namespace PhpLocate;

use PhpLocate\Internal\RuntimeDOMException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class IndexTest extends TestCase {
	private string $tempFile = '';
	
	protected function setUp(): void {
		$this->tempFile = sys_get_temp_dir() . '/php-locate-test-' . microtime(true) . '.xml';
		
		$files = (new Finder())
			->in(__DIR__ . '/..')
			->filter(fn(SplFileInfo $item) => str_starts_with($item->getRelativePathname(), 'tests/Suspects/'))
			->filter(fn(SplFileInfo $item) => $item->isFile())
			->filter(fn(SplFileInfo $item) => str_ends_with($item->getBasename(), '.php'));
		
		$service = new UpdateIndexService(new NullLogger());
		$service->updateIndex(indexPath: $this->tempFile, files: $files);
		
		#echo file_get_contents($this->tempFile);
	}
	
	protected function tearDown(): void {
		if(file_exists($this->tempFile)) {
			unlink($this->tempFile);
		}
	}
	
	#[Test]
	public function getStrings(): void {
		$index = Index::fromFile($this->tempFile);
		self::assertEquals(UpdateIndexService::INDEX_VERSION, $index->getFirstString('/files/@version'));
		$allFiles = $index->getStrings('/files/file[class/@name="PhpLocate\\Suspects\\MyClass"]/@path');
		self::assertEquals(['tests/Suspects/MyClass.php'], $allFiles);
	}
	
	#[Test]
	public function findClassAttribute(): void {
		$index = Index::fromFile($this->tempFile);
		$this->assertInstanceOf(Index::class, $index);
		
		$path = $index->getFirstString('/files/file[class[@name="PhpLocate\\Suspects\\MyClass"]/attribute[@name="PhpLocate\\Suspects\\ClassAttributeA"]]/@path');
		self::assertEquals('tests/Suspects/MyClass.php', $path);
		
		$path = $index->getFirstString('/files/file[class[@name="PhpLocate\\Suspects\\MyClass"]/attribute[@name="NonExistent"]]/@path', '-');
		self::assertEquals('-', $path);
		
		$this->expectException(RuntimeDOMException::class);
		$index->getFirstString('/files/file[class[@name="PhpLocate\\Suspects\\MyClass"]/attribute[@name="NonExistent"]]/@path');
	}
	
	public function testFindMethodAttribute(): void {
		$index = Index::fromFile($this->tempFile);
		$this->assertInstanceOf(Index::class, $index);
		
		$path = $index->getFirstString('/files/file[class/method/attribute[@name="PhpLocate\\Suspects\\MethodAttributeA"]]/@path');
		self::assertEquals('tests/Suspects/MyClass.php', $path);
		
		$path = $index->getFirstString('/files/file[class/method/attribute[@name="PhpLocate\\Suspects\\MethodAttributeC"]]/@path', '-');
		self::assertEquals('-', $path);
		
		$this->expectException(RuntimeDOMException::class);
		$index->getFirstString('/files/file[class/method/attribute[@name="PhpLocate\\Suspects\\MethodAttributeC"]]/@path');
	}

	#[Test]
	public function mergesTraitMethodsIntoClasses(): void {
		$index = Index::fromFile($this->tempFile);

		$path = $index->getFirstString('/files/file[class[@name="PhpLocate\\Suspects\\MyClass"]/method[@name="traitMethod"]]/@path');
		self::assertEquals('tests/Suspects/MyClass.php', $path);
	}

	#[Test]
	public function mergesTraitPropertiesIntoClasses(): void {
		$index = Index::fromFile($this->tempFile);

		$path = $index->getFirstString('/files/file[class[@name="PhpLocate\\Suspects\\MyClass"]/property[@name="traitProp"]]/@path');
		self::assertEquals('tests/Suspects/MyClass.php', $path);
	}
}
