<?php

namespace PhpLocate\Internal;

use Generator;
use PhpLocate\Index;
use PhpLocate\Internal\ChangeSetProjection\ChangedFile;
use PhpLocate\Internal\ChangeSetProjection\NewFile;
use PhpLocate\Internal\ChangeSetProjection\RemovedFile;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 */
class ChangeSetProjectionService {
	/**
	 * @param iterable<FileInfo|SplFileInfo> $items
	 * @return Generator<NewFile|RemovedFile|ChangedFile>
	 */
	public function findChanged(iterable $items, Index $index): Generator {
		$files = [];
		$lookup = [];
		foreach($items as $file) {
			if($file instanceof SplFileInfo) {
				$files[$file->getRelativePathname()] = $file->getMTime();
				$lookup[$file->getRelativePathname()] = $file->getPathname();
			} else {
				$files[$file->getRelativePathname()] = $file->getMTime();
				$lookup[$file->getRelativePathname()] = $file->getPathname();
			}
		}
		
		$indexedFiles = $index->getFilePathsAndLastModifiedDate();
		
		$missingFiles = array_diff_key($indexedFiles, $files);
		foreach($missingFiles as $file => $mtime) {
			yield new RemovedFile(relativePath: $file);
		}
		
		$newFiles = array_diff_key($files, $indexedFiles);
		foreach($newFiles as $file => $mtime) {
			$path = $lookup[$file];
			yield new NewFile(
				absolutePath: $path,
				relativePath: $file,
				mtime: (int) filemtime($path),
				hash: (string) md5_file($path)
			);
		}
		
		$intersection = array_intersect_key($indexedFiles, $files);
		$changedFiles = array_diff_assoc($intersection, $files);
		foreach($changedFiles as $file => $mtime) {
			$path = $lookup[$file];
			
			$indexHash = $index->getFirstString(sprintf('/files/file[@path="%s"]/@hash', strtr($file, ['\\' => '/'])), $mtime);
			$fileHash = md5_file($path);
			if($indexHash === $fileHash) {
				continue;
			}
			
			yield new ChangedFile(
				absolutePath: $path,
				relativePath: $file,
				mtime: (int) filemtime($path),
				hash: (string) md5_file($path)
			);
		}
	}
}