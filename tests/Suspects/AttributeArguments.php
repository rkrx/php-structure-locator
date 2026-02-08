<?php

namespace PhpLocate\Suspects;

use Attribute;

const CONST_AGE = 42;

#[Attribute(Attribute::TARGET_CLASS)]
class AttributeWithArgs {
	public function __construct(
		public string $firstname,
		public int $age,
	) {}
}

#[AttributeWithArgs(firstname: '<name>', age: CONST_AGE)]
class AttributeArgTarget {}
