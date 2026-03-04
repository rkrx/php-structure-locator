<?php

namespace PhpLocate\Internal\FileSystem;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GlobMatcherTest extends TestCase {
	#[Test]
	#[DataProvider('matchDataProvider')]
	public function match(string $pattern, string $path, bool $expected): void {
		$matcher = new GlobMatcher($pattern);
		
		self::assertSame($expected, $matcher->match($path));
	}
	
	/**
	 * @return Generator<string, array{string, string, bool}>
	 */
	public static function matchDataProvider(): Generator {
		yield 'matches exact literal path' => [
			'src/Internal/FileInfo.php',
			'src/Internal/FileInfo.php',
			true
		];
		
		yield 'rejects non matching literal path' => [
			'src/Internal/FileInfo.php',
			'src/Internal/Finder.php',
			false
		];
		
		yield 'escapes regex special characters in literals' => [
			'src/Docs/C++(Draft).php',
			'src/Docs/C++(Draft).php',
			true
		];
		
		yield 'rejects different literal with escaped characters' => [
			'src/Docs/C++(Draft).php',
			'src/Docs/C--(Draft).php',
			false
		];
		
		yield 'single star matches within one path segment' => [
			'tests/Suspects/*AttributeA.php',
			'tests/Suspects/MethodAttributeA.php',
			true
		];
		
		yield 'single star does not cross directory boundaries' => [
			'tests/Suspects/*AttributeA.php',
			'tests/Suspects/Nested/MethodAttributeA.php',
			false
		];
		
		yield 'double star matches recursively for one level' => [
			'tests/**/*Attribute*.php',
			'tests/Suspects/MethodAttributeB.php',
			true
		];
		
		yield 'double star matches recursively for multiple levels' => [
			'tests/**/*Attribute*.php',
			'tests/Suspects/Nested/MethodAttributeB.php',
			true
		];
		
		yield 'double star pattern still checks the file suffix' => [
			'tests/**/*Attribute*.php',
			'tests/Suspects/Nested/MethodAttributeB.inc',
			false
		];
		
		yield 'brace alternatives match first entry' => [
			'tests/Suspects/MethodAttribute{A,B}.php',
			'tests/Suspects/MethodAttributeA.php',
			true
		];
		
		yield 'brace alternatives match second entry' => [
			'tests/Suspects/MethodAttribute{A,B}.php',
			'tests/Suspects/MethodAttributeB.php',
			true
		];
		
		yield 'brace alternatives reject values outside of set' => [
			'tests/Suspects/MethodAttribute{A,B}.php',
			'tests/Suspects/MethodAttributeC.php',
			false
		];
		
		yield 'readme include pattern matches files in root directory' => [
			'src/{*,**/*}.php',
			'src/Index.php',
			true
		];
		
		yield 'readme include pattern matches files in subdirectories' => [
			'src/{*,**/*}.php',
			'src/Internal/FileInfo.php',
			true
		];
		
		yield 'readme include pattern rejects wrong extension' => [
			'src/{*,**/*}.php',
			'src/Internal/FileInfo.txt',
			false
		];

		yield 'test paths with dashes' => [
			'src-ext/**/*Attribute*.php',
			'src-ext/tests/Suspects/MethodAttributeA.php',
			true
		];

		yield 'multiple slashes in pattern match normalized path' => [
			'tests//Suspects//MethodAttributeA.php',
			'tests/Suspects/MethodAttributeA.php',
			true
		];
		
		yield 'multiple slashes in pattern match one or more slashes in input path' => [
			'tests//Suspects//MethodAttributeA.php',
			'tests//Suspects///MethodAttributeA.php',
			true
		];
		
		yield 'quick prefix check rejects clearly unrelated paths' => [
			'tests/**/*Attribute*.php',
			'src/tests/Suspects/MethodAttributeA.php',
			false
		];
	}
}
