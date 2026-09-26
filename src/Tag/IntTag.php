<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\SNBTFormat;

class IntTag extends IntegerTag {
	public const MIN = -2_147_483_648;

	public const MAX = 2_147_483_647;

	protected function render(SNBTFormat $format, int $depth): string {
		return (string) $this->value;
	}
}
