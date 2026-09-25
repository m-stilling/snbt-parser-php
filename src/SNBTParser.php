<?php

namespace Stilling\SNBTParser;

use Stilling\SNBTParser\Tag\Tag;

class SNBTParser {
	/**
	 * Parse SNBT into native PHP types. NBT type distinctions are collapsed the
	 * same way as before (integers become int, decimals become float); use
	 * {@see self::parseTyped()} when you need to keep them.
	 *
	 * @return array<mixed>|float|int|string|bool
	 */
	public static function parse(string $input): array|float|int|string|bool {
		return self::parseTyped($input)->toPhp();
	}

	/**
	 * Parse SNBT into a typed tag tree that preserves the original NBT types.
	 */
	public static function parseTyped(string $input): Tag {
		return (new Parser($input))->parse();
	}

	/**
	 * @param list<int|string> $ints
	 */
	public static function intsToUuid(array $ints): string {
		if (count($ints) !== 4) {
			throw new \InvalidArgumentException("Array must contain exactly 4 integers.");
		}

		foreach ($ints as $i) {
			if (!is_int($i)) {
				throw new \InvalidArgumentException("All elements must be integers.");
			}
		}

		$bytes = pack("NNNN", ...$ints);
		$hex = bin2hex($bytes);

		return implode("-", [
			substr($hex, 0, 8),
			substr($hex, 8, 4),
			substr($hex, 12, 4),
			substr($hex, 16, 4),
			substr($hex, 20, 12),
		]);
	}
}
