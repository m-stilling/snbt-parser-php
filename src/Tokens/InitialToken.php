<?php

namespace Stilling\SNBTParser\Tokens;

class InitialToken extends Token {
	public function getPossibleNeighbors(): array {
		return [
			NumberArrayToken::class,
			CompoundOpenToken::class,
			ListOpenToken::class,
			NumberToken::class,
			StringToken::class,
			BooleanToken::class,
		];
	}

	public function satisfiesConstraints(string $token): int {
		throw new \LogicException("Not implemented.");
	}

	public function toJsonToken(): string {
		throw new \LogicException("Not implemented.");
	}
}
