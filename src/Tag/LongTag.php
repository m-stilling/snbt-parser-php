<?php

namespace Stilling\SNBTParser\Tag;

final class LongTag extends IntegerTag {
	public function toSnbt(): string {
		return $this->value . "l";
	}
}
