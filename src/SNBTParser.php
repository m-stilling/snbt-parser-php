<?php

namespace Stilling\SNBTParser;

use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\Exceptions\SNBTParseException;
use Stilling\SNBTParser\Exceptions\SNBTTagException;
use Stilling\SNBTParser\Tag\CompoundTag;
use Stilling\SNBTParser\Tag\IntArrayTag;
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
	 * Parse SNBT whose root must be a compound, such as the output of
	 * `data get entity` or `data get block`.
	 *
	 * @throws SNBTParseException when the input is malformed or its root is not a compound
	 */
	public static function parseCompound(string $input): CompoundTag {
		$tag = self::parseTyped($input);

		if (!$tag instanceof CompoundTag) {
			throw new SNBTParseException("Expected a compound at the root, found " . SNBTTagException::shortName($tag::class) . ".");
		}

		return $tag;
	}

	/**
	 * @param list<int|string> $ints
	 */
	public static function intsToUuid(array $ints): string {
		if (count($ints) !== 4) {
			throw new SNBTInvalidArgumentException("Array must contain exactly 4 integers.");
		}

		foreach ($ints as $i) {
			if (!is_int($i)) {
				throw new SNBTInvalidArgumentException("All elements must be integers.");
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

	/**
	 * Inverse of {@see self::intsToUuid()}: turn a UUID string into the four
	 * signed 32-bit integers Minecraft stores it as, ready for an `IntArrayTag`.
	 * Accepts the canonical hyphenated form as well as 32 bare hex digits, in
	 * either case.
	 *
	 * @return list<int>
	 */
	public static function uuidToInts(string $uuid): array {
		$hex = str_replace("-", "", $uuid);

		if (!preg_match('/^[0-9a-fA-F]{32}$/', $hex)) {
			throw new SNBTInvalidArgumentException("Invalid UUID: {$uuid}");
		}

		$bytes = hex2bin($hex);
		$unsigned = $bytes === false ? false : unpack("N4", $bytes);
		if ($unsigned === false) {
			throw new SNBTInvalidArgumentException("Invalid UUID: {$uuid}");
		}

		$ints = [];
		foreach ($unsigned as $value) {
			// unpack("N") yields unsigned values; wrap them back into the signed 32-bit range.
			$ints[] = $value >= 0x80000000 ? $value - 0x100000000 : $value;
		}

		return $ints;
	}

	/**
	 * Turn a UUID string into the SNBT int array Minecraft stores it as, e.g.
	 * `[I;110787060,1156138790,-1514210135,238594805]`.
	 */
	public static function uuidToSnbt(string $uuid, SNBTFormat $format = SNBTFormat::Compact): string {
		return (new IntArrayTag(self::uuidToInts($uuid)))->toSnbt($format);
	}
}
