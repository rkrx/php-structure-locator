<?php

namespace PhpLocate\Internal\FileSystem;

use PhpLocate\Internal\FileInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FinderTest extends TestCase {
	#[Test]
	public function find(): void {
		$testPath = Tools::normalizePath(__DIR__ . '/../../..');
		$finder = new Finder();
		$finder->setWorkingDirectory($testPath);
		$finder->addInclude('tests/**/*Attribute*.php');
		$finder->addExclude('tests/**/Class*.php');
		$files = iterator_to_array($finder->find(reuseFileInfo: false), false);
		$files = array_map(static fn(FileInfo $file) => $file->relativePath, $files);
		
		self::assertCount(5, $files);
		self::assertContains('tests/Suspects/MethodAttributeB.php', $files);
		self::assertNotContains('tests/Suspects/ClassAttributeB.php', $files);
	}
}
