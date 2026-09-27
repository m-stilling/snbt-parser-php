<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

class StringTag extends Tag {
	public function __construct(public readonly string $value) {
	}

	public function toPhp(): string {
		return $this->value;
	}

	protected function render(SNBTFormat $format, int $depth, bool $escapeControlCharacters): string {
		return self::quote($this->value, $escapeControlCharacters);
	}
}
