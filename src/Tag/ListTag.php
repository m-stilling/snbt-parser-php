<?php

namespace Stilling\SNBTParser\Tag;

/**
 * An ordered list of tags (`[...]`). NBT lists are homogeneous; this parser does
 * not enforce that, leaving validation to the caller.
 */
final class ListTag extends Tag {
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

	public function toSnbt(): string {
		return "[" . implode(",", array_map(fn (Tag $item): string => $item->toSnbt(), $this->items)) . "]";
	}
}
