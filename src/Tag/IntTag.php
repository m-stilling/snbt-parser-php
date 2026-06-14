<?php

namespace Stilling\SNBTParser\Tag;

class IntTag extends IntegerTag {
	public function toSnbt(): string {
		return (string) $this->value;
	}
}
