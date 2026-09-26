<?php

namespace Stilling\SNBTParser\Tag;

class ByteArrayTag extends NumberArrayTag {
	public function elementType(): string {
		return ByteTag::class;
	}

	protected function bracketType(): string {
		return "B";
	}

	protected function elementSuffix(): string {
		return "b";
	}
}
