<?php

namespace PhpLocate\Suspects;

#[FunctionAttributeA()]
function test(
	#[ParameterAttributeA()]
	int $min,
	#[ParameterAttributeB()]
	int $max,
): bool|int|float|string {
	return match(random_int($min, $max)) {
		1 => true,
		2 => 42,
		3 => 3.14,
		4 => 'Hello, World!',
		default => false,
	};
}