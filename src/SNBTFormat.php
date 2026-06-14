<?php

namespace Stilling\SNBTParser;

/**
 * Controls how {@see \Stilling\SNBTParser\Tag\Tag::toSnbt()} formats its output:
 *
 *   Compact -> {a:1,b:[1,2]}
 *   Spaced  -> {a: 1, b: [1, 2]}
 *   Pretty  -> multi-line, indented with four spaces
 */
enum SNBTFormat {
	case Compact;
	case Spaced;
	case Pretty;

	/**
	 * Separator between a key and its value.
	 */
	public function keyValueSeparator(): string {
		return $this === self::Compact ? ":" : ": ";
	}

	/**
	 * Separator between consecutive entries/items of a container at $depth.
	 */
	public function itemSeparator(int $depth): string {
		return match ($this) {
			self::Compact => ",",
			self::Spaced => ", ",
			self::Pretty => ",\n" . $this->indentation($depth + 1),
		};
	}

	/**
	 * Text after an opening bracket, before the first entry/item.
	 */
	public function afterOpen(int $depth): string {
		return $this === self::Pretty ? "\n" . $this->indentation($depth + 1) : "";
	}

	/**
	 * Text before a closing bracket, after the last entry/item.
	 */
	public function beforeClose(int $depth): string {
		return $this === self::Pretty ? "\n" . $this->indentation($depth) : "";
	}

	public function indentation(int $depth): string {
		return $this === self::Pretty ? str_repeat("    ", $depth) : "";
	}
}
