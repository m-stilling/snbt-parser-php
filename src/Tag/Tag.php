<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\SNBTFormat;

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
	 * Build a tag tree from native PHP values: bool becomes BooleanTag, int
	 * becomes IntTag (LongTag outside the 32-bit range), float becomes
	 * DoubleTag, string becomes StringTag, a list becomes ListTag and any other
	 * array becomes CompoundTag. An empty array is a list. Tags pass through
	 * unchanged, so a tree can mix native values and specific tag types.
	 *
	 * @throws SNBTInvalidArgumentException for any other value, such as null or an object
	 */
	public static function fromPhp(mixed $value): Tag {
		return match (true) {
			$value instanceof Tag => $value,
			is_bool($value) => new BooleanTag($value),
			is_int($value) => $value >= -2_147_483_648 && $value <= 2_147_483_647 ? new IntTag($value) : new LongTag($value),
			is_float($value) => new DoubleTag($value),
			is_string($value) => new StringTag($value),
			is_array($value) && array_is_list($value) => new ListTag(array_map(fn (mixed $item): Tag => self::fromPhp($item), $value)),
			is_array($value) => new CompoundTag(array_map(fn (mixed $item): Tag => self::fromPhp($item), $value)),
			default => throw new SNBTInvalidArgumentException("Cannot convert a value of type " . get_debug_type($value) . " to a tag."),
		};
	}

	/**
	 * Re-serialize this tag back to SNBT, retaining its NBT type.
	 */
	public function toSnbt(SNBTFormat $format = SNBTFormat::Compact): string {
		return $this->render($format, 0);
	}

	/**
	 * Render this tag at the given nesting depth. Containers thread the depth
	 * through their children so Pretty formatting can indent correctly.
	 */
	abstract protected function render(SNBTFormat $format, int $depth): string;

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
