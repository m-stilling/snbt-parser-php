<?php

namespace Stilling\SNBTParser\Tag;

/**
 * Shared base for the two floating-point NBT types. Both hold a PHP float (a
 * 64-bit double); the concrete subclass records the original NBT type.
 */
abstract class FloatingPointTag extends Tag {
	public function __construct(public readonly float $value) {
	}

	public function toPhp(): float {
		return $this->value;
	}

	/**
	 * Format the value with enough precision to round-trip. json_encode honours
	 * serialize_precision (-1 by default) and yields the shortest exact form.
	 */
	protected function formatValue(): string {
		return json_encode($this->value, JSON_THROW_ON_ERROR);
	}
}
