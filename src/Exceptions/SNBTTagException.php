<?php

namespace Stilling\SNBTParser\Exceptions;

use Stilling\SNBTParser\Tag\Tag;

/**
 * Thrown by the typed getters on compounds and lists when the requested entry
 * is missing or holds a different tag type than the one asked for.
 */
class SNBTTagException extends \UnexpectedValueException implements SNBTException {
	public static function missing(string $location): self {
		return new self("No tag at {$location}.");
	}

	public static function wrongType(string $location, string $expected, Tag $actual): self {
		return new self("Expected {$expected} at {$location}, found " . self::shortName($actual::class) . ".");
	}

	public static function shortName(string $class): string {
		$position = strrpos($class, "\\");

		return $position === false ? $class : substr($class, $position + 1);
	}
}
