<?php

namespace PhpLocate\Internal\FileSystem;

class GlobMatcher {
	private string $pattern;
	private string $prefix;
	
	public function __construct(string $pattern) {
		$pattern = preg_quote($pattern, '#');
		$pattern = strtr($pattern, ['\*\*' => '.*', '\*' => '[^/]*', '\{' => '(?:', '\}' => ')', ',' => '|']);
		$pattern = (string) preg_replace('{/+}', '/+', $pattern); // Replaces all // with / in the pattern and matches one or more slashes in the path.
		$this->pattern = $pattern;
		$this->prefix = (string) preg_replace('{^([^.*+?(|]+).*?$}', '$1', $pattern);
	}
	
	public function match(string $path): bool {
		if($this->prefix !== '' && !str_starts_with($path, $this->prefix)) {
			return false;
		}
		return (bool) preg_match("#^{$this->pattern}$#", $path);
	}
}