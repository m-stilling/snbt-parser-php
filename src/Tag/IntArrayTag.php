<?php

namespace Stilling\SNBTParser\Tag;

final class IntArrayTag extends NumberArrayTag {
	protected function bracketType(): string {
		return "I";
	}

	protected function elementSuffix(): string {
		return "";
	}
}
