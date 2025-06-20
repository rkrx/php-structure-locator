<?php

namespace PhpLocate\Suspects;

#[ClassAttributeA()]
#[ClassAttributeB()]
class MyClass {
	use MyTrait;
	
	public function __construct() {}
	
	#[MethodAttributeA()]
	#[MethodAttributeB()]
	public function test(
		#[ParameterAttributeA()]
		#[ParameterAttributeB()]
		int $a,
		#[ParameterAttributeB()]
		string $b
	): void {}
	
	#[MethodAttributeA()]
	final public function finalMethod(): void {
	}
}