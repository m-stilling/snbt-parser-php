<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\ESnbtFormat;

class LongTag extends IntegerTag {
	protected function render(ESnbtFormat $format, int $depth): string {
		return $this->value . "l";
	}
}
