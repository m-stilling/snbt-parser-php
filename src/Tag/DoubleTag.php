<?php

namespace Stilling\SNBTParser\Tag;

final class DoubleTag extends FloatingPointTag {
	public function toSnbt(): string {
		return $this->formatValue() . "d";
	}
}
