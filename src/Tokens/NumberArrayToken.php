<?php

namespace Stilling\SNBTParser\Tokens;

class NumberArrayToken extends Token {
	/** @var list<int> */
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
		$closePosition = mb_strpos($trimmedToken, "]");

		if ($closePosition === false) {
			return 0;
		}

		$partsString = mb_substr($trimmedToken, 0, $closePosition);
		$trimmedToken = mb_substr($trimmedToken, $closePosition + 1);

		$partsString = preg_replace('/\s+/', '', $partsString) ?? "";

		$this->value = $partsString === ""
			? []
			: array_map(static fn (string $part): int => (int) $part, explode(",", $partsString));

		return mb_strlen($token) - mb_strlen($trimmedToken);
	}

	public function toJsonToken(): string {
		return json_encode($this->value, JSON_THROW_ON_ERROR);
	}
}
