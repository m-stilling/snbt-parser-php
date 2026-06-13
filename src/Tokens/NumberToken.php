<?php

namespace Stilling\SNBTParser\Tokens;

class NumberToken extends Token {
	protected int|float $value;

	public function getPossibleNeighbors(): array {
		return [
			CompoundCloseToken::class,
			ListCloseToken::class,
			CommaToken::class,
		];
	}

	public function satisfiesConstraints(string $token): int {
		$trimmedToken = mb_trim($token);

		[ $number, $tagType, $length ] = $this->parseNbtPrimitive($trimmedToken);

		if ($number === null) {
			return 0;
		}

		$this->value = $number;

		return mb_strlen($token) - mb_strlen($trimmedToken) + $length;
	}

	public function toJsonToken(): string {
		// json_encode honours serialize_precision (-1 by default), emitting the
		// shortest representation that round-trips to the same value, rather than
		// the lossy 14-digit string interpolation would produce.
		return json_encode($this->value);
	}

	public function parseNbtPrimitive(string $input): ?array {
		// Match pattern: optional minus, digits, optional decimal, optional suffix
		if (!preg_match('/^(-?\d+(?:\.\d+)?)([bBsSiIlLfFdD]?)/', $input, $matches)) {
			return null;
		}

		$value = $matches[1];
		$suffix = strtolower($matches[2]);
		$length = mb_strlen("{$value}{$suffix}");

		// TODO: Technically allows matching invalid types for the value
		//  Should be separate regexes, or one that matches all valid types
		// TODO: Also check min/max for each type
		//  Throw errors accordingly, remember tests

		return match ($suffix) {
			'b' => [ (int) $value, 'TAG_Byte', $length ],
			's' => [ (int) $value, 'TAG_Short', $length ],
			'i' => [ (int) $value, 'TAG_Int', $length ],
			'l' => [ (int) $value, 'TAG_Long', $length ],
			'f' => [ (float) $value, 'TAG_Float', $length ],
			'd' => [ (double) $value, 'TAG_Double', $length ],
			'' => match(str_contains($value, '.')) {
				true => [ (double) $value, 'TAG_Double', $length ],
				false => [ (int) $value, 'TAG_Int', $length ],
			},
			default => throw new \InvalidArgumentException("Unknown suffix: $suffix"),
		};
	}
}
