<?php

namespace PhpLocate\Internal\FileSystem;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ToolsTest extends TestCase {
	#[Test]
	#[DataProvider('dataProvider')]
	public function normalizePath(string $input, string $expected): void {
		$path = Tools::normalizePath($input);
		self::assertEquals($expected, $path);
	}
	
	/**
	 * @return Generator<array{string, string}>
	 */
	public static function dataProvider(): Generator {
		yield [
			'C:\\Users\\User\\Documents\\..\\PhpLocate',
			'C:/Users/User/PhpLocate'
		];
		
		yield [
			'/var/www/html/../PhpLocate',
			'/var/www/PhpLocate'
		];
		
		yield [
			'C:\\Users\\User\\Documents\\PhpLocate',
			'C:/Users/User/Documents/PhpLocate'
		];
		
		yield [
			'C:\\Users\\User\\Documents\\\\PhpLocate',
			'C:/Users/User/Documents/PhpLocate'
		];
		
		yield [
			'C:\\Users\\User\\Documents/PhpLocate',
			'C:/Users/User/Documents/PhpLocate'
		];
		
		yield ['/var/www/html//PhpLocate', '/var/www/html/PhpLocate'];
		yield ['/var/www/html/./PhpLocate', '/var/www/html/PhpLocate'];
		yield ['/var/www/html/.././PhpLocate', '/var/www/PhpLocate'];
		yield ['', ''];
		yield ['.', '.'];
		yield ['/', '/'];
	}
}
