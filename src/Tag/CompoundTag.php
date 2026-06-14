<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

/**
 * A compound (`{...}`) — an ordered, keyed map of tags.
 */
class CompoundTag extends Tag {
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
