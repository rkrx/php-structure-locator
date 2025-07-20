<?php

namespace PhpLocate\Internal\FileSystem;

class Tools {
	public static function concatPaths(string $left, string $right): string {
		if($left === '') {
			return $right;
		}
		return $left . DIRECTORY_SEPARATOR . ltrim($right, '/\\' . DIRECTORY_SEPARATOR);
	}
	
	public static function normalizePath(string $path): string {
		if($path === '/' || $path === DIRECTORY_SEPARATOR) {
			return DIRECTORY_SEPARATOR;
		}
		
		$path = strtr($path, ['\\' => '/']);
		$parts = explode('/', $path);
		$normalized = [];
		foreach ($parts as $idx => $part) {
			if ($part === '' || $part === '.') {
				if($idx === 0) {
					$normalized[] = $part;
				}
				continue;
			}
			
			if ($part === '..') {
				if (count($normalized) > 0 && end($normalized) !== '..') {
					array_pop($normalized);
				} else {
					$normalized[] = $part;
				}
			} else {
				$normalized[] = $part;
			}
		}
		
		return implode('/', $normalized);
	}
}