<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\ESnbtFormat;

class IntTag extends IntegerTag {
	protected function render(ESnbtFormat $format, int $depth): string {
		return (string) $this->value;
	}
}
