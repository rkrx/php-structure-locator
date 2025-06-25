<?php

namespace PhpLocate\Suspects;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION)]
class FunctionAttributeA {
	public function __construct(
		public readonly string $name = 'FunctionAttributeA',
		public readonly string $description = 'This is a test function attribute A',
		public readonly int $version = 1,
	) {}
}