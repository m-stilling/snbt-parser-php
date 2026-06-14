<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\ESnbtFormat;

class FloatTag extends FloatingPointTag {
	protected function render(ESnbtFormat $format, int $depth): string {
		return $this->formatValue() . "f";
	}
}
