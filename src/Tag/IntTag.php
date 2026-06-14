<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

class IntTag extends IntegerTag {
	protected function render(SNBTFormat $format, int $depth): string {
		return (string) $this->value;
	}
}
