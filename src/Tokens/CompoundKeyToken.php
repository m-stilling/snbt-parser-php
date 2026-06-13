<?php

namespace Stilling\SNBTParser\Tokens;

class CompoundKeyToken extends Token {
	protected string $key;

	public function getPossibleNeighbors(): array {
		return [
			NumberArrayToken::class,
			CompoundOpenToken::class,
			ListOpenToken::class,
			StringToken::class,
			NumberToken::class,
			BooleanToken::class,
		];
	}

	public function satisfiesConstraints(string $token): int {
		preg_match("/^ *(\"([^\"]+)\"|([a-zA-Z0-9_.+-]+)):/", $token, $matches);

		if (!isset($matches[2])) {
			return 0;
		}

		$this->key = $matches[2] ?: $matches[3];

		return mb_strlen($matches[0]);
	}

	public function toJsonToken(): string {
		return "\"{$this->key}\":";
	}
}
