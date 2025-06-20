<?php

namespace PhpLocate\Suspects;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class MethodAttributeA {
	public function __construct() {}
}