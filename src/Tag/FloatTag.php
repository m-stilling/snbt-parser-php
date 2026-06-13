<?php

namespace Stilling\SNBTParser\Tag;

final class FloatTag extends FloatingPointTag {
	public function toSnbt(): string {
		return $this->formatValue() . "f";
	}
}
