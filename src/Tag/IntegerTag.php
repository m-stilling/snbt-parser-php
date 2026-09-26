<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\Exceptions\SNBTTagException;

/**
 * Shared base for the four signed-integer NBT types. They all hold a PHP int;
 * the concrete subclass records which NBT type the value was written as.
 */
abstract class IntegerTag extends Tag {
	/**
	 * The smallest value the NBT type can hold.
	 */
	public const MIN = PHP_INT_MIN;

	/**
	 * The largest value the NBT type can hold.
	 */
	public const MAX = PHP_INT_MAX;

	/**
	 * @throws SNBTInvalidArgumentException when the value is outside the range of the NBT type
	 */
	public function __construct(public readonly int $value) {
		if ($value < static::MIN || $value > static::MAX) {
			throw new SNBTInvalidArgumentException(
				"Value {$value} is out of range for " . SNBTTagException::shortName(static::class) . " (" . static::MIN . " to " . static::MAX . ").",
			);
		}
	}

	public function toPhp(): int {
		return $this->value;
	}
}
