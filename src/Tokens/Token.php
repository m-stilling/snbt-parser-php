<?php

namespace Stilling\SNBTParser\Tokens;

use Stilling\SNBTParser\Exceptions\SNBTParseException;

abstract class Token {
	/**
	 * @return class-string<Token>[]
	 */
	abstract public function getPossibleNeighbors(): array;

	abstract public function satisfiesConstraints(string $token): int;

	abstract public function toJsonToken(): string;

	public function parseNextToken(string $remaining): array {
		foreach ($this->getPossibleNeighbors() as $neighborFQN) {
			$neighbor = new $neighborFQN();

			if (($offset = $neighbor->satisfiesConstraints($remaining)) > 0) {
				return [ $neighbor, mb_substr($remaining, $offset) ];
			}
		}

		$classParts = explode("\\", static::class);
		$currentTokenName = end($classParts);
		$remainingSnippetLength = 20;
		$remainingSnippet = mb_substr($remaining, 0, $remainingSnippetLength);

		if (mb_strlen($remaining) > $remainingSnippetLength) {
			$remainingSnippet .= " <snip>";
		}

		throw new SNBTParseException("No possible neighbor token found in $currentTokenName for \"$remainingSnippet\", the SNBT may be invalid or malformed.");
	}
}
