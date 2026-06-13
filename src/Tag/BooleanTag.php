<?php

namespace Stilling\SNBTParser\Tag;

/**
 * SNBT's `true`/`false`. NBT stores these as bytes, but keeping a distinct tag
 * lets `toSnbt()` round-trip the boolean spelling rather than emitting 1b/0b.
 */
final class BooleanTag extends Tag {
	public function __construct(public readonly bool $value) {
	}

	public function toPhp(): bool {
		return $this->value;
	}

	public function toSnbt(): string {
		return $this->value ? "true" : "false";
	}
}
