<?php

namespace Stilling\SNBTParser\Tag;

final class IntTag extends IntegerTag {
	public function toSnbt(): string {
		return (string) $this->value;
	}
}
