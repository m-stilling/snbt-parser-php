<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

class LongTag extends IntegerTag {
	protected function render(SNBTFormat $format, int $depth, bool $escapeControlCharacters): string {
		return $this->value . "l";
	}
}
