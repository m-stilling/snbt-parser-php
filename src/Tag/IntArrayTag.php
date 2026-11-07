<?php

namespace Stilling\SNBTParser\Tag;

use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\SNBTParser;

class IntArrayTag extends NumberArrayTag {
	/**
	 * Build the int array Minecraft stores a UUID as. Accepts the hyphenated
	 * form or 32 bare hex digits, in either case.
	 *
	 * @throws SNBTInvalidArgumentException when the string is not a UUID
	 */
	public static function fromUuid(string $uuid): IntArrayTag {
		return new IntArrayTag(SNBTParser::uuidToInts($uuid));
	}

	/**
	 * Read the array as a UUID, the way Minecraft stores them (`UUID: [I;a,b,c,d]`).
	 *
	 * @throws SNBTInvalidArgumentException when the array does not hold exactly four integers
	 */
	public function toUuid(): string {
		return SNBTParser::intsToUuid($this->values);
	}

	public function elementType(): string {
		return IntTag::class;
	}

	protected function bracketType(): string {
		return "I";
	}

	protected function elementSuffix(): string {
		return "";
	}
}
