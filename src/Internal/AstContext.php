<?php

namespace PhpLocate\Internal;

class AstContext {
	public function __construct(
		public ?string $namespace = null,
	) {}
	
	public function withNamespace(string $namespace): self {
		$newContext = clone $this;
		$newContext->namespace = $namespace;
		return $newContext;
		
	}
}