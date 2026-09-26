<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\Exceptions\SNBTTagException;
use Stilling\SNBTParser\SNBTFormat;

/**
 * An ordered list of tags (`[...]`). NBT lists are homogeneous; this parser does
 * not enforce that, leaving validation to the caller.
 *
 * @implements \IteratorAggregate<int, Tag>
 */
class ListTag extends Tag implements \Countable, \IteratorAggregate {
	/**
	 * @param list<Tag> $items
	 */
	public function __construct(public readonly array $items) {
	}

	public function get(int $index): ?Tag {
		return $this->items[$index] ?? null;
	}

	/**
	 * @template T of Tag
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws SNBTTagException when the index is out of range or holds another tag type
	 */
	public function getAs(int $index, string $class): Tag {
		$tag = $this->require($index);

		if (!$tag instanceof $class) {
			throw SNBTTagException::wrongType($this->location($index), SNBTTagException::shortName($class), $tag);
		}

		return $tag;
	}

	public function getCompound(int $index): CompoundTag {
		return $this->getAs($index, CompoundTag::class);
	}

	public function getList(int $index): ListTag {
		return $this->getAs($index, ListTag::class);
	}

	public function getString(int $index): string {
		return $this->getAs($index, StringTag::class)->value;
	}

	/**
	 * Accepts any integer tag: byte, short, int or long.
	 */
	public function getInt(int $index): int {
		return $this->getAs($index, IntegerTag::class)->value;
	}

	/**
	 * Accepts either floating-point tag: float or double.
	 */
	public function getFloat(int $index): float {
		return $this->getAs($index, FloatingPointTag::class)->value;
	}

	/**
	 * Accepts `true`/`false` and bytes, since Minecraft writes booleans as
	 * `1b`/`0b`. Any non-zero byte is true.
	 */
	public function getBool(int $index): bool {
		$tag = $this->require($index);

		return match (true) {
			$tag instanceof BooleanTag => $tag->value,
			$tag instanceof ByteTag => $tag->value !== 0,
			default => throw SNBTTagException::wrongType($this->location($index), "BooleanTag or ByteTag", $tag),
		};
	}

	/**
	 * Return a copy with the item at $index replaced by $tag.
	 *
	 * @throws SNBTTagException when the index is out of range
	 */
	public function with(int $index, Tag $tag): ListTag {
		$this->require($index);

		$items = $this->items;
		$items[$index] = $tag;

		return new ListTag(array_values($items));
	}

	/**
	 * Return a copy with $tag added after the last item.
	 */
	public function withAppended(Tag $tag): ListTag {
		return new ListTag([ ...$this->items, $tag ]);
	}

	/**
	 * Return a copy without the item at $index; later items move down by one.
	 * An index out of range is not an error.
	 */
	public function without(int $index): ListTag {
		$items = $this->items;
		unset($items[$index]);

		return new ListTag(array_values($items));
	}

	protected function require(int $index): Tag {
		return $this->get($index) ?? throw SNBTTagException::missing($this->location($index));
	}

	protected function location(int $index): string {
		return "index {$index}";
	}

	public function count(): int {
		return count($this->items);
	}

	/**
	 * @return \ArrayIterator<int, Tag>
	 */
	public function getIterator(): \ArrayIterator {
		return new \ArrayIterator($this->items);
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
