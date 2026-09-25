<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

/**
 * SNBT's `true`/`false`. NBT stores these as bytes, but keeping a distinct tag
 * lets `toSnbt()` round-trip the boolean spelling rather than emitting 1b/0b.
 */
class BooleanTag extends Tag {
	public function __construct(public readonly bool $value) {
	}

	public function toPhp(): bool {
		return $this->value;
	}

	protected function render(SNBTFormat $format, int $depth): string {
		return $this->value ? "true" : "false";
	}
}
