<?php

namespace Stilling\SNBTParser\Tokens;

class NumberArrayToken extends Token {
	protected array $value;

	public function getPossibleNeighbors(): array {
		return [
			CompoundCloseToken::class,
			ListCloseToken::class,
			CommaToken::class,
		];
	}

	public function satisfiesConstraints(string $token): int {
		$trimmedToken = mb_trim($token);

		if (
			!str_starts_with($trimmedToken, "[I;")
			&& !str_starts_with($trimmedToken, "[B;")
			&& !str_starts_with($trimmedToken, "[L;")
		) {
			return 0;
		}

		$trimmedToken = mb_trim(mb_substr($trimmedToken, 3));
		$partsString = mb_strstr($trimmedToken, "]", true);
		$trimmedToken = mb_substr(mb_strstr($trimmedToken, "]"), 1);

		if ($partsString === false) {
			return 0;
		}

		$partsString = preg_replace('/\s+/', '', $partsString);

		$this->value = $partsString === ""
			? []
			: array_map(fn ($part) => (int) $part, explode(",", $partsString));

		return mb_strlen($token) - mb_strlen($trimmedToken);
	}

	public function toJsonToken(): string {
		return json_encode($this->value);
	}
}
