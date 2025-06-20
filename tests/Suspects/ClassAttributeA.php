<?php

namespace PhpLocate\Suspects;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ClassAttributeA {
	public function __construct() {}
}