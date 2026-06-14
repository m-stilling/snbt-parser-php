<?php

namespace Stilling\SNBTParser\Tag;

class FloatTag extends FloatingPointTag {
	public function toSnbt(): string {
		return $this->formatValue() . "f";
	}
}
