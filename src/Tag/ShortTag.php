<?php

namespace Stilling\SNBTParser\Tag;

final class ShortTag extends IntegerTag {
	public function toSnbt(): string {
		return $this->value . "s";
	}
}
