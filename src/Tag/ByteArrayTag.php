<?php

namespace Stilling\SNBTParser\Tag;

class ByteArrayTag extends NumberArrayTag {
	protected function bracketType(): string {
		return "B";
	}

	protected function elementSuffix(): string {
		return "b";
	}
}
