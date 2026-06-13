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
				if (mb_trim($char) === "") {
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

			// The quote only closes the string when it is not escaped. A run of
			// backslashes escapes the quote only when its length is odd, since each
			// pair collapses to a single literal backslash.
			if ($char === $encapsulatingChar && $this->countTrailingBackslashes($pulledChars) % 2 === 0) {
				$this->value = $this->unescape($pulledChars, $encapsulatingChar);
				return $i + 1;
			}

			$pulledChars .= $char;
		}

		return 0;
	}

	protected function countTrailingBackslashes(string $value): int {
		$count = 0;

		for ($i = mb_strlen($value) - 1; $i >= 0; $i--) {
			if (mb_substr($value, $i, 1) !== "\\") {
				break;
			}

			$count++;
		}

		return $count;
	}

	/**
	 * Resolve the SNBT escape sequences the tokenizer honours (a backslash before
	 * the encapsulating quote or another backslash) into their literal characters.
	 * Any other backslash is left untouched and gets escaped by json_encode().
	 */
	protected function unescape(string $value, string $encapsulatingChar): string {
		$result = "";
		$length = mb_strlen($value);

		for ($i = 0; $i < $length; $i++) {
			$char = mb_substr($value, $i, 1);

			if ($char === "\\" && $i + 1 < $length) {
				$next = mb_substr($value, $i + 1, 1);

				if ($next === "\\" || $next === $encapsulatingChar) {
					$result .= $next;
					$i++;
					continue;
				}
			}

			$result .= $char;
		}

		return $result;
	}

	public function toJsonToken(): string {
		return json_encode($this->value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
}
