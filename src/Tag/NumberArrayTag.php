<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\ESnbtFormat;

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

	protected function render(ESnbtFormat $format, int $depth): string {
		if ($this->values === []) {
			return "[" . $this->bracketType() . ";]";
		}

		// Number arrays stay on one line; only the separators gain spaces.
		$spaced = $format !== ESnbtFormat::Compact;
		$elements = array_map(fn (int $value): string => $value . $this->elementSuffix(), $this->values);

		return "[" . $this->bracketType() . ($spaced ? "; " : ";")
			. implode($spaced ? ", " : ",", $elements)
			. "]";
	}

	abstract protected function bracketType(): string;

	abstract protected function elementSuffix(): string;
}
