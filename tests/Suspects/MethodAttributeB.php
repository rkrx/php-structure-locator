<?php

namespace PhpLocate\Suspects;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class MethodAttributeB {
	public function __construct() {}
}