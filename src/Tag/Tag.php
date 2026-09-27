<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\SNBTFormat;

/**
 * Base class for every SNBT value. The concrete subclass records the NBT type
 * (byte vs int, float vs double, the three typed arrays, ...), which
 * `toSnbt()` writes back out.
 */
abstract class Tag {
	/**
	 * The value as a native PHP type. Every integer type becomes int and every
	 * floating-point type becomes float.
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
			is_int($value) => $value >= IntTag::MIN && $value <= IntTag::MAX ? new IntTag($value) : new LongTag($value),
			is_float($value) => new DoubleTag($value),
			is_string($value) => new StringTag($value),
			is_array($value) && array_is_list($value) => new ListTag(array_map(fn (mixed $item): Tag => self::fromPhp($item), $value)),
			is_array($value) => new CompoundTag(array_map(fn (mixed $item): Tag => self::fromPhp($item), $value)),
			default => throw new SNBTInvalidArgumentException("Cannot convert a value of type " . get_debug_type($value) . " to a tag."),
		};
	}

	/**
	 * Re-serialize this tag back to SNBT, retaining its NBT type.
	 *
	 * By default control characters inside strings stay raw, which every
	 * Minecraft version reads. With $escapeControlCharacters they are written as
	 * escapes, so a string never breaks the output across lines; versions before
	 * 1.21.5 reject those escapes.
	 */
	public function toSnbt(SNBTFormat $format = SNBTFormat::Compact, bool $escapeControlCharacters = false): string {
		return $this->render($format, 0, $escapeControlCharacters);
	}

	/**
	 * Render this tag at the given nesting depth. Containers thread the depth
	 * through their children so Pretty formatting can indent correctly.
	 */
	abstract protected function render(SNBTFormat $format, int $depth, bool $escapeControlCharacters): string;

	/**
	 * Quote a string for SNBT output. The backslash and the double quote are
	 * always escaped. Control characters stay raw unless $escapeControlCharacters
	 * is set.
	 */
	protected static function quote(string $value, bool $escapeControlCharacters): string {
		$escapes = ["\\" => "\\\\", '"' => "\\\""];

		if ($escapeControlCharacters) {
			$escapes += self::controlCharacterEscapes();
		}

		return '"' . strtr($value, $escapes) . '"';
	}

	/**
	 * The escapes that 1.21.5 and later write for control characters: \n, \r
	 * and \t, and a two-digit hex escape for the rest of U+0000-U+001F and U+007F.
	 *
	 * @return array<string, string>
	 */
	protected static function controlCharacterEscapes(): array {
		$escapes = [];

		foreach ([...range(0x00, 0x1F), 0x7F] as $code) {
			$escapes[chr($code)] = sprintf("\\x%02x", $code);
		}

		return array_replace($escapes, ["\n" => "\\n", "\r" => "\\r", "\t" => "\\t"]);
	}
}
