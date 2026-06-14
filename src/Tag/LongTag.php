<?php

namespace Stilling\SNBTParser\Tag;

class LongTag extends IntegerTag {
	public function toSnbt(): string {
		return $this->value . "l";
	}
}
