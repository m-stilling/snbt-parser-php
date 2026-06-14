<?php

namespace Stilling\SNBTParser\Tag;

/**
 * Base class for every parsed SNBT value. Unlike the v1 JSON round-trip, the
 * concrete subclass preserves the original NBT type (byte vs int, float vs
 * double, the three typed arrays, ...), which `toSnbt()` can faithfully emit.
 */
abstract class Tag {
	/**
	 * The value as a native PHP type, collapsing NBT type distinctions the same
	 * way the v1 parser did (every integer type becomes int, every floating type
	 * becomes float).
	 *
	 * @return array<mixed>|int|float|string|bool
	 */
	abstract public function toPhp(): array|int|float|string|bool;

	/**
	 * Re-serialize this tag back to SNBT, retaining its NBT type.
	 */
	abstract public function toSnbt(): string;

	/**
	 * Quote a string for SNBT output, escaping the sequences the parser decodes:
	 * the backslash, the double quote, and the \n / \r / \t control characters.
	 */
	protected static function quote(string $value): string {
		$escaped = str_replace(
			["\\", '"', "\n", "\r", "\t"],
			['\\\\', '\\"', '\\n', '\\r', '\\t'],
			$value,
		);

		return '"' . $escaped . '"';
	}
}
