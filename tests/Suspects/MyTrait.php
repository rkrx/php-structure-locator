<?php

namespace PhpLocate\Suspects;

trait MyTrait {
	public int $traitProp;

	#[MethodAttributeA()]
	public function traitMethod(): void {
	}
}
