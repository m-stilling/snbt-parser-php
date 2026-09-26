<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\Exceptions\SNBTTagException;
use Stilling\SNBTParser\SNBTFormat;

/**
 * A compound (`{...}`) — an ordered, keyed map of tags.
 *
 * @implements \IteratorAggregate<string, Tag>
 */
class CompoundTag extends Tag implements \Countable, \IteratorAggregate {
	/**
	 * @param array<string, Tag> $entries
	 */
	public function __construct(public readonly array $entries) {
	}

	public function get(string $key): ?Tag {
		return $this->entries[$key] ?? null;
	}

	public function has(string $key): bool {
		return isset($this->entries[$key]);
	}

	/**
	 * @template T of Tag
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws SNBTTagException when the key is missing or holds another tag type
	 */
	public function getAs(string $key, string $class): Tag {
		$tag = $this->require($key);

		if (!$tag instanceof $class) {
			throw SNBTTagException::wrongType($this->location($key), SNBTTagException::shortName($class), $tag);
		}

		return $tag;
	}

	public function getCompound(string $key): CompoundTag {
		return $this->getAs($key, CompoundTag::class);
	}

	public function getList(string $key): ListTag {
		return $this->getAs($key, ListTag::class);
	}

	public function getString(string $key): string {
		return $this->getAs($key, StringTag::class)->value;
	}

	/**
	 * Accepts any integer tag: byte, short, int or long.
	 */
	public function getInt(string $key): int {
		return $this->getAs($key, IntegerTag::class)->value;
	}

	/**
	 * Accepts either floating-point tag: float or double.
	 */
	public function getFloat(string $key): float {
		return $this->getAs($key, FloatingPointTag::class)->value;
	}

	/**
	 * Accepts `true`/`false` and bytes, since Minecraft writes booleans as
	 * `1b`/`0b`. Any non-zero byte is true.
	 */
	public function getBool(string $key): bool {
		$tag = $this->require($key);

		return match (true) {
			$tag instanceof BooleanTag => $tag->value,
			$tag instanceof ByteTag => $tag->value !== 0,
			default => throw SNBTTagException::wrongType($this->location($key), "BooleanTag or ByteTag", $tag),
		};
	}

	protected function require(string $key): Tag {
		return $this->get($key) ?? throw SNBTTagException::missing($this->location($key));
	}

	protected function location(string $key): string {
		return "key \"{$key}\"";
	}

	public function count(): int {
		return count($this->entries);
	}

	/**
	 * @return \ArrayIterator<string, Tag>
	 */
	public function getIterator(): \ArrayIterator {
		return new \ArrayIterator($this->entries);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toPhp(): array {
		$result = [];

		foreach ($this->entries as $key => $tag) {
			$result[$key] = $tag->toPhp();
		}

		return $result;
	}

	protected function render(SNBTFormat $format, int $depth): string {
		if ($this->entries === []) {
			return "{}";
		}

		$parts = [];

		foreach ($this->entries as $key => $tag) {
			$parts[] = $this->serializeKey($key) . $format->keyValueSeparator() . $tag->render($format, $depth + 1);
		}

		return "{" . $format->afterOpen($depth)
			. implode($format->itemSeparator($depth), $parts)
			. $format->beforeClose($depth) . "}";
	}

	protected function serializeKey(string $key): string {
		if ($key !== "" && preg_match('/^[A-Za-z0-9_.+-]+$/', $key) === 1) {
			return $key;
		}

		return self::quote($key);
	}
}
