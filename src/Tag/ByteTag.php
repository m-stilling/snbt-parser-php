<?php

namespace Stilling\SNBTParser\Tag;

class ByteTag extends IntegerTag {
	public function toSnbt(): string {
		return $this->value . "b";
	}
}
