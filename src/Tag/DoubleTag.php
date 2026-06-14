<?php

namespace Stilling\SNBTParser\Tag;

class DoubleTag extends FloatingPointTag {
	public function toSnbt(): string {
		return $this->formatValue() . "d";
	}
}
