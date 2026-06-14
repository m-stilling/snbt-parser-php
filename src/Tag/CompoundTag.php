<?php

namespace Stilling\SNBTParser\Tag;

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

	public function toSnbt(): string {
		$parts = [];

		foreach ($this->entries as $key => $tag) {
			$parts[] = $this->serializeKey($key) . ":" . $tag->toSnbt();
		}

		return "{" . implode(",", $parts) . "}";
	}

	protected function serializeKey(string $key): string {
		if ($key !== "" && preg_match('/^[A-Za-z0-9_.+-]+$/', $key) === 1) {
			return $key;
		}

		return '"' . str_replace(["\\", '"'], ["\\\\", '\\"'], $key) . '"';
	}
}
