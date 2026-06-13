<?php

namespace Stilling\SNBTParser\Tokens;

class StringToken extends Token {
	protected string $value;

	public function getPossibleNeighbors(): array {
		return [
			CompoundCloseToken::class,
			ListCloseToken::class,
			CommaToken::class,
		];
	}

	public function satisfiesConstraints(string $token): int {
		// go through until we find a closing quote or apostrophe (store which one was used to open the string) which
		// is not escaped

		$pulledChars = "";
		$encapsulatingChar = "";

		for ($i = 0; $i < mb_strlen($token); $i++) {
			$char = mb_substr($token, $i, 1);

			if ($encapsulatingChar === "") {
				// Still looking for the opening quote or apostrophe, skip any leading whitespace
				if ($char === " ") {
					continue;
				}

				if ($char === "\"" || $char === "'") {
					$encapsulatingChar = $char;
					continue;
				} else {
					// Does not satisfy constraints
					return 0;
				}
			}

			$pulledCharsArray = mb_str_split($pulledChars);

			if ($char === $encapsulatingChar && end($pulledCharsArray) !== "\\") {
				$pulledChars = str_replace("\n", "\\n", $pulledChars, $count);
				$this->value = $pulledChars;
				return $i + 1;
			}

			$pulledChars .= $char;
		}

		return 0;
	}

	public function toJsonToken(): string {
		return "\"{$this->value}\"";
	}
}
