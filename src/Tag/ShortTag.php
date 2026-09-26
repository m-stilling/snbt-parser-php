<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

class ShortTag extends IntegerTag {
	public const MIN = -32_768;

	public const MAX = 32_767;

	protected function render(SNBTFormat $format, int $depth): string {
		return $this->value . "s";
	}
}
