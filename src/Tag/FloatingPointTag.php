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
		$formatted = json_encode($this->value, JSON_THROW_ON_ERROR);

		// json_encode drops the fraction of a whole number (20.0 -> "20"); SNBT
		// floats and doubles keep a decimal point so the value still reads as one.
		if (!str_contains($formatted, ".") && !str_contains($formatted, "e") && !str_contains($formatted, "E")) {
			$formatted .= ".0";
		}

		return $formatted;
	}
}
