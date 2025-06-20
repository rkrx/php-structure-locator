<?php

namespace PhpLocate\Suspects;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ClassAttributeB {
	public function __construct() {}
}