<?php

namespace PhpLocate\Internal;

class FileInfo {
	public string $relativePath = '';
	public int $mtime = 0;
	public string $path = '';
	
	public function getRelativePathname(): string {
		return $this->relativePath;
	}
	
	public function getMTime(): int {
		return $this->mtime;
	}
	
	public function getPathname(): string {
		return $this->path;
	}
}