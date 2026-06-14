<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\ESnbtFormat;

class DoubleTag extends FloatingPointTag {
	protected function render(ESnbtFormat $format, int $depth): string {
		return $this->formatValue() . "d";
	}
}
