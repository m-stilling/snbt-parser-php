<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

class ByteTag extends IntegerTag {
	public const MIN = -128;

	public const MAX = 127;

	protected function render(SNBTFormat $format, int $depth): string {
		return $this->value . "b";
	}
}
