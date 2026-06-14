<?php

namespace Stilling\SNBTParser\Tag;

/**
 * Shared base for the four signed-integer NBT types. They all hold a PHP int;
 * the concrete subclass records which NBT type the value was written as.
 */
abstract class IntegerTag extends Tag {
	public function __construct(public readonly int $value) {
	}

	public function toPhp(): int {
		return $this->value;
	}
}
