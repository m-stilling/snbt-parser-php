<?php

namespace Stilling\SNBTParser\Tag;

final class ByteArrayTag extends NumberArrayTag {
	protected function bracketType(): string {
		return "B";
	}

	protected function elementSuffix(): string {
		return "B";
	}
}
