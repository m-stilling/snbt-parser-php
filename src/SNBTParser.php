<?php

namespace Stilling\SNBTParser;

use Stilling\SNBTParser\Exceptions\SNBTParseException;
use Stilling\SNBTParser\Tokens\InitialToken;
use Stilling\SNBTParser\Tokens\Token;

class SNBTParser {
	public static function parse(string $input): array|float|int|string|object|bool {
		$json = static::readSNBT(mb_trim($input));
		$array = json_decode($json, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new SNBTParseException("SNBT is malformed, failed to decode JSON: " . json_last_error_msg());
		}

		return $array;
	}

	public static function intsToUuid(array $ints): string {
		if (count($ints) !== 4) {
			throw new \InvalidArgumentException("Array must contain exactly 4 integers.");
		}

		foreach ($ints as $i) {
			if (!is_int($i)) {
				throw new \InvalidArgumentException("All elements must be integers.");
			}
		}

		$bytes = pack("NNNN", $ints[0], $ints[1], $ints[2], $ints[3]);
		$hex = bin2hex($bytes);

		return implode("-", [
			substr($hex, 0, 8),
			substr($hex, 8, 4),
			substr($hex, 12, 4),
			substr($hex, 16, 4),
			substr($hex, 20, 12),
		]);
	}

	/**
	 * @param string $snbt
	 * @return string
	 * @throws SNBTParseException
	 */
	protected static function readSNBT(string $snbt): string {
		$json = "";
		$currentToken = new InitialToken();

		while (mb_strlen($snbt) > 0) {
			[ $nextToken, $remainingSNBT ] = $currentToken->parseNextToken($snbt);

			if (
				!isset($nextToken)
				|| !($nextToken instanceof Token)
				|| !isset($remainingSNBT)
				|| !is_string($remainingSNBT)
			) {
				throw new \LogicException("Invalid parseNextToken result");
			}

			$json .= $nextToken->toJsonToken();
			$snbt = $remainingSNBT;
			$currentToken = $nextToken;
		}

		return $json;
	}
}
