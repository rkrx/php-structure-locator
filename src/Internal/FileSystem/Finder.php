<?php

namespace PhpLocate\Internal\FileSystem;

use Generator;
use PhpLocate\Internal\FileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Traversable;

class Finder {
	/** @var GlobMatcher[] */
	private array $includes = [];
	/** @var GlobMatcher[] */
	private array $excludes = [];
	
	public function __construct(private string $workingDirectory = '.') {}
	
	public function setWorkingDirectory(string $workingDirectory): void {
		$this->workingDirectory = Tools::normalizePath($workingDirectory);
	}
	
	public function addInclude(string $pattern): void{
		$matcher = new GlobMatcher($pattern);
		$this->includes[] = $matcher;
	}
	
	public function addExclude(string $pattern): void{
		$matcher = new GlobMatcher($pattern);
		$this->excludes[] = $matcher;
	}
	
	/**
	 * @return Generator<FileInfo>
	 */
	public function find(bool $reuseFileInfo = true): Generator {
		$currentDir = getcwd();
		if($currentDir !== false) {
			chdir($this->workingDirectory);
		}
		try {
			$dirIterator = new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS);
			
			/** @var Traversable<SplFileInfo> $iterator */
			$iterator = new RecursiveIteratorIterator($dirIterator);
			
			$fi = new FileInfo();
			foreach ($iterator as $fileInfo) {
				if(!$reuseFileInfo) {
					$fi = new FileInfo();
				}
				if ($fileInfo->isFile()) {
					$path = $fileInfo->getPathname();
					
					$path = strtr($path, ['\\' => '/']);
					
					if(str_starts_with($path, './')) {
						$path = substr($path, 2);
					}
					
					if(!$this->matchesInclude($path)) {
						continue;
					}
					
					if($this->matchesExclude($path)) {
						continue;
					}
					
					$fi->relativePath = $path;
					$fi->mtime = $fileInfo->getMTime();
					$fi->path = Tools::concatPaths($this->workingDirectory, $path);
					yield $fi;
				}
			}
		} finally {
			if($currentDir !== false) {
				chdir($currentDir);
			}
		}
	}
	
	private function matchesInclude(string $path): bool {
		foreach($this->includes as $include) {
			if($include->match($path)) {
				return true;
			}
		}
		return false;
	}
	
	private function matchesExclude(string $path): bool {
		foreach($this->excludes as $exclude) {
			if($exclude->match($path)) {
				return true;
			}
		}
		return false;
	}
}