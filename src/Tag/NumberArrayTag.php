<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\Exceptions\SNBTTagException;
use Stilling\SNBTParser\SNBTFormat;

/**
 * Shared base for the three typed integer arrays (`[B;...]`, `[I;...]`,
 * `[L;...]`). The concrete subclass supplies the bracket type letter and the
 * suffix each element is written with.
 *
 * @implements \IteratorAggregate<int, int>
 */
abstract class NumberArrayTag extends Tag implements \Countable, \IteratorAggregate {
	/**
	 * @param list<int> $values
	 *
	 * @throws SNBTInvalidArgumentException when a value is outside the range of the element type
	 */
	public function __construct(public readonly array $values) {
		$type = $this->elementType();

		foreach ($values as $index => $value) {
			if ($value < $type::MIN || $value > $type::MAX) {
				throw new SNBTInvalidArgumentException(
					"Value {$value} at index {$index} is out of range for " . SNBTTagException::shortName(static::class) . " (" . $type::MIN . " to " . $type::MAX . ").",
				);
			}
		}
	}

	/**
	 * The integer tag type of each element, which sets the allowed range.
	 *
	 * @return class-string<IntegerTag>
	 */
	public function elementType(): string {
		return LongTag::class;
	}

	public function count(): int {
		return count($this->values);
	}

	/**
	 * @return \ArrayIterator<int, int>
	 */
	public function getIterator(): \ArrayIterator {
		return new \ArrayIterator($this->values);
	}

	/**
	 * @return list<int>
	 */
	public function toPhp(): array {
		return $this->values;
	}

	protected function render(SNBTFormat $format, int $depth, bool $escapeControlCharacters): string {
		if ($this->values === []) {
			return "[" . $this->bracketType() . ";]";
		}

		// Number arrays stay on one line; only the separators gain spaces.
		$spaced = $format !== SNBTFormat::Compact;
		$elements = array_map(fn (int $value): string => $value . $this->elementSuffix(), $this->values);

		return "[" . $this->bracketType() . ($spaced ? "; " : ";")
			. implode($spaced ? ", " : ",", $elements)
			. "]";
	}

	abstract protected function bracketType(): string;

	abstract protected function elementSuffix(): string;
}
