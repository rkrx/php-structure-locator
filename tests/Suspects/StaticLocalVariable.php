<?php

namespace PhpLocate\Suspects;

function container(): ?object {
	/** @var object|null $container */
	static $container = null;

	return $container;
}

function discoveredAfterStaticLocalVariable(): void {}
