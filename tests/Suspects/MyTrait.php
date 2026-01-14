<?php

namespace PhpLocate\Suspects;

trait MyTrait {
	public int $traitProp = 0;

	#[MethodAttributeA()]
	public function traitMethod(): void {
	}
}
