<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\SNBTParser;

class IntArrayTag extends NumberArrayTag {
	/**
	 * Read the array as a UUID, the way Minecraft stores them (`UUID: [I;a,b,c,d]`).
	 *
	 * @throws SNBTInvalidArgumentException when the array does not hold exactly four integers
	 */
	public function toUuid(): string {
		return SNBTParser::intsToUuid($this->values);
	}

	protected function bracketType(): string {
		return "I";
	}

	protected function elementSuffix(): string {
		return "";
	}
}
