<?php

namespace PhpLocate;

use PhpLocate\Internal\XMLNode;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase {
	private string $tempFile = '';
	
	protected function setUp(): void {
		$this->tempFile = sys_get_temp_dir() . '/php-locate-test-' . uniqid() . '.xml';
	}
	
	protected function tearDown(): void {
		if(file_exists($this->tempFile)) {
			unlink($this->tempFile);
		}
	}
	
	public function testFromFile(): void {
		// Test with non-existent file (should create new structure)
		$index = Index::fromFile($this->tempFile);
		$this->assertInstanceOf(Index::class, $index);
		
		// Create a test XML file
		$doc = new DOMDocument();
		$root = $doc->createElement('files');
		$doc->appendChild($root);
		$fileNode = $doc->createElement('file');
		$fileNode->setAttribute('path', 'test/path.php');
		$fileNode->setAttribute('mtime', '1234567890');
		$fileNode->setAttribute('hash', 'abcdef123456');
		$root->appendChild($fileNode);
		$doc->save($this->tempFile);
		
		// Test with existing file
		$index = Index::fromFile($this->tempFile);
		$this->assertInstanceOf(Index::class, $index);
		
		$paths = $index->getFilePathsAndLastModifiedDate();
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('test/path.php', $paths);
		$this->assertEquals('1234567890', $paths['test/path.php']);
	}
	
	public function testGetFilePathsAndLastModifiedDate(): void {
		$doc = new DOMDocument();
		$root = $doc->createElement('files');
		$doc->appendChild($root);
		
		// Add multiple file nodes
		for($i = 1; $i <= 3; $i++) {
			$fileNode = $doc->createElement('file');
			$fileNode->setAttribute('path', "path/to/file$i.php");
			$fileNode->setAttribute('mtime', (string) (1000000000 + $i));
			$fileNode->setAttribute('hash', "hash$i");
			$root->appendChild($fileNode);
		}
		
		$index = new Index(new XMLNode($root));
		$paths = $index->getFilePathsAndLastModifiedDate();
		
		$this->assertCount(3, $paths);
		$this->assertArrayHasKey('path/to/file1.php', $paths);
		$this->assertArrayHasKey('path/to/file2.php', $paths);
		$this->assertArrayHasKey('path/to/file3.php', $paths);
		$this->assertEquals('1000000001', $paths['path/to/file1.php']);
		$this->assertEquals('1000000002', $paths['path/to/file2.php']);
		$this->assertEquals('1000000003', $paths['path/to/file3.php']);
	}
	
	public function testAddFile(): void {
		$index = Index::fromFile($this->tempFile);
		
		// Add a new file
		$node = $index->addFile('src/Test.php', 1234567890, 'abc123');
		$this->assertInstanceOf(XMLNode::class, $node);
		
		// Verify the file was added
		$paths = $index->getFilePathsAndLastModifiedDate();
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('src/Test.php', $paths);
		
		// Update the same file
		$node = $index->addFile('src/Test.php', 1234567891, 'abc124');
		
		// Verify the file was updated, not duplicated
		$paths = $index->getFilePathsAndLastModifiedDate();
		$this->assertCount(1, $paths);
		
		// Save and reload to verify persistence
		$index->saveTo($this->tempFile);
		$newIndex = Index::fromFile($this->tempFile);
		$paths = $newIndex->getFilePathsAndLastModifiedDate();
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('src/Test.php', $paths);
	}
	
	public function testRemoveFile(): void {
		$index = Index::fromFile($this->tempFile);
		
		// Add files
		$index->addFile('src/Test1.php', 1234567890, 'abc123');
		$index->addFile('src/Test2.php', 1234567891, 'abc124');
		
		// Verify files were added
		$paths = $index->getFilePathsAndLastModifiedDate();
		$this->assertCount(2, $paths);
		
		// Remove one file
		$index->removeFile('src/Test1.php');
		
		// Verify only one file remains
		$paths = $index->getFilePathsAndLastModifiedDate();
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('src/Test2.php', $paths);
		$this->assertArrayNotHasKey('src/Test1.php', $paths);
	}
	
	public function testSaveTo(): void {
		$index = Index::fromFile($this->tempFile);
		$index->addFile('src/Test.php', 1234567890, 'abc123');
		$index->saveTo($this->tempFile);
		
		$this->assertFileExists($this->tempFile);
		
		// Verify the saved content
		$doc = new DOMDocument();
		$doc->load($this->tempFile);
		$xpath = new \DOMXPath($doc);
		$fileNodes = $xpath->query('/files/file');
		
		$this->assertEquals(1, $fileNodes->length);
		/** @var \DOMElement $fileNode */
		$fileNode = $fileNodes->item(0);
		$this->assertEquals('src/Test.php', $fileNode->getAttribute('path'));
		$this->assertEquals('1234567890', $fileNode->getAttribute('mtime'));
		$this->assertEquals('abc123', $fileNode->getAttribute('hash'));
	}
}
