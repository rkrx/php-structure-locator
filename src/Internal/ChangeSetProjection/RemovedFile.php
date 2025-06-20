<?php

namespace PhpLocate\Internal\ChangeSetProjection;

/**
 * @internal
 */
class RemovedFile {
	public function __construct(
		public string $relativePath
	) {}
}