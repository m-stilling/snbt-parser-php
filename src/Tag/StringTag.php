<?php

namespace Stilling\SNBTParser\Tag;

class StringTag extends Tag {
	public function __construct(public readonly string $value) {
	}

	public function toPhp(): string {
		return $this->value;
	}

	public function toSnbt(): string {
		return '"' . str_replace(["\\", '"'], ["\\\\", '\\"'], $this->value) . '"';
	}
}
