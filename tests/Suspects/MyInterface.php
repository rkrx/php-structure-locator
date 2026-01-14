<?php

namespace PhpLocate\Suspects;

#[ClassAttributeA()]
interface MyInterfaceBase {
	#[MethodAttributeA()]
	public function baseMethod(): void;
}

interface MyInterfaceChild extends MyInterfaceBase {
	public function childMethod(): void;
}

