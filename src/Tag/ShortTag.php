<?php

namespace Stilling\SNBTParser\Tag;

class ShortTag extends IntegerTag {
	public function toSnbt(): string {
		return $this->value . "s";
	}
}
