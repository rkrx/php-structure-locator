<?php

namespace PhpLocate\Internal\ChangeSetProjection;

/**
 * @internal
 */
class ChangedFile {
	public function __construct(
		public string $absolutePath,
		public string $relativePath,
		public int $mtime,
		public string $hash,
	) {}
}