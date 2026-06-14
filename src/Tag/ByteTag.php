<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

class ByteTag extends IntegerTag {
	protected function render(SNBTFormat $format, int $depth): string {
		return $this->value . "b";
	}
}
