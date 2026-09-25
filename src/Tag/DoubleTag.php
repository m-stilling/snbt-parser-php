<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

class DoubleTag extends FloatingPointTag {
	protected function render(SNBTFormat $format, int $depth): string {
		return $this->formatValue() . "d";
	}
}
