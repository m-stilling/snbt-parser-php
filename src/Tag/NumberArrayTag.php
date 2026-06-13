<?php

namespace Stilling\SNBTParser\Tag;

/**
 * Shared base for the three typed integer arrays (`[B;...]`, `[I;...]`,
 * `[L;...]`). The concrete subclass supplies the bracket type letter and the
 * suffix each element is written with.
 */
abstract class NumberArrayTag extends Tag {
	/**
	 * @param list<int> $values
	 */
	public function __construct(public readonly array $values) {
	}

	/**
	 * @return list<int>
	 */
	public function toPhp(): array {
		return $this->values;
	}

	public function toSnbt(): string {
		$elements = array_map(fn (int $value): string => $value . $this->elementSuffix(), $this->values);

		return "[" . $this->bracketType() . ";" . implode(",", $elements) . "]";
	}

	abstract protected function bracketType(): string;

	abstract protected function elementSuffix(): string;
}
