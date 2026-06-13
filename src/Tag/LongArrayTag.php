<?php

namespace Stilling\SNBTParser\Tag;

final class LongArrayTag extends NumberArrayTag {
	protected function bracketType(): string {
		return "L";
	}

	protected function elementSuffix(): string {
		return "L";
	}
}
