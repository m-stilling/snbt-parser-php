<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

/**
 * An ordered list of tags (`[...]`). NBT lists are homogeneous; this parser does
 * not enforce that, leaving validation to the caller.
 */
class ListTag extends Tag {
	/**
	 * @param list<Tag> $items
	 */
	public function __construct(public readonly array $items) {
	}

	/**
	 * @return list<mixed>
	 */
	public function toPhp(): array {
		return array_map(fn (Tag $item): array|int|float|string|bool => $item->toPhp(), $this->items);
	}

	protected function render(SNBTFormat $format, int $depth): string {
		if ($this->items === []) {
			return "[]";
		}

		$items = array_map(fn (Tag $item): string => $item->render($format, $depth + 1), $this->items);

		return "[" . $format->afterOpen($depth)
			. implode($format->itemSeparator($depth), $items)
			. $format->beforeClose($depth) . "]";
	}
}
