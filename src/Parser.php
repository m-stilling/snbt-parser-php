<?php

namespace Stilling\SNBTParser;

use Stilling\SNBTParser\Exceptions\SNBTParseException;
use Stilling\SNBTParser\Tag\BooleanTag;
use Stilling\SNBTParser\Tag\ByteArrayTag;
use Stilling\SNBTParser\Tag\ByteTag;
use Stilling\SNBTParser\Tag\CompoundTag;
use Stilling\SNBTParser\Tag\DoubleTag;
use Stilling\SNBTParser\Tag\FloatTag;
use Stilling\SNBTParser\Tag\IntArrayTag;
use Stilling\SNBTParser\Tag\IntTag;
use Stilling\SNBTParser\Tag\IntegerTag;
use Stilling\SNBTParser\Tag\ListTag;
use Stilling\SNBTParser\Tag\LongArrayTag;
use Stilling\SNBTParser\Tag\LongTag;
use Stilling\SNBTParser\Tag\NumberArrayTag;
use Stilling\SNBTParser\Tag\ShortTag;
use Stilling\SNBTParser\Tag\StringTag;
use Stilling\SNBTParser\Tag\Tag;

/**
 * A single-pass, recursive-descent SNBT parser that builds a typed tag tree
 * directly from the input. Structural characters are all ASCII, so the input
 * is scanned byte by byte; multibyte string contents pass through untouched
 * (UTF-8 continuation bytes never collide with the ASCII delimiters), which is
 * why no mbstring functions are needed.
 */
class Parser {
	protected int $position = 0;

	protected readonly int $length;

	public function __construct(protected readonly string $input) {
		$this->length = strlen($input);
	}

	public function parse(): Tag {
		$tag = $this->parseValue();
		$this->skipWhitespace();

		if (!$this->eof()) {
			throw $this->error("Unexpected trailing data");
		}

		return $tag;
	}

	protected function parseValue(): Tag {
		$this->skipWhitespace();

		$char = $this->currentOrFail("a value");

		return match (true) {
			$char === "{" => $this->parseCompound(),
			$char === "[" => $this->parseListOrArray(),
			$char === '"' || $char === "'" => new StringTag($this->readQuotedString()),
			default => $this->parseLiteral(),
		};
	}

	protected function parseCompound(): CompoundTag {
		$this->position++; // consume "{"
		$entries = [];

		$this->skipWhitespace();

		if ($this->currentIs("}")) {
			$this->position++;

			return new CompoundTag($entries);
		}

		while (true) {
			$this->skipWhitespace();
			$key = $this->parseKey();
			$this->skipWhitespace();
			$this->expect(":");
			$entries[$key] = $this->parseValue();
			$this->skipWhitespace();

			$char = $this->currentOrFail("',' or '}'");

			if ($char === ",") {
				$this->position++;

				continue;
			}

			if ($char === "}") {
				$this->position++;

				break;
			}

			throw $this->error("Expected ',' or '}'");
		}

		return new CompoundTag($entries);
	}

	protected function parseKey(): string {
		$char = $this->currentOrFail("a key");

		if ($char === '"' || $char === "'") {
			return $this->readQuotedString();
		}

		$start = $this->position;

		while (!$this->eof() && $this->isLiteralChar($this->input[$this->position])) {
			$this->position++;
		}

		if ($this->position === $start) {
			throw $this->error("Expected a compound key");
		}

		return substr($this->input, $start, $this->position - $start);
	}

	protected function parseListOrArray(): Tag {
		$arrayType = $this->detectTypedArray();

		if ($arrayType !== null) {
			return $this->parseTypedArray($arrayType);
		}

		return $this->parseList();
	}

	/**
	 * Returns the array type letter (B/I/L) when the bracket opens a typed
	 * number array, or null for a regular list. The trailing ";" is what
	 * distinguishes `[I; ...]` from a list such as `[I, J]`.
	 */
	protected function detectTypedArray(): ?string {
		$index = $this->position + 1;

		while ($index < $this->length && $this->isWhitespace($this->input[$index])) {
			$index++;
		}

		if ($index + 1 >= $this->length || $this->input[$index + 1] !== ";") {
			return null;
		}

		$letter = $this->input[$index];

		return ($letter === "B" || $letter === "I" || $letter === "L") ? $letter : null;
	}

	protected function parseTypedArray(string $type): NumberArrayTag {
		$this->position++; // consume "["
		$this->skipWhitespace();
		$this->position++; // consume the type letter
		$this->expect(";");

		$values = [];

		$this->skipWhitespace();

		if ($this->currentIs("]")) {
			$this->position++;

			return $this->makeArrayTag($type, $values);
		}

		while (true) {
			$this->skipWhitespace();
			$values[] = $this->parseArrayElement($type);
			$this->skipWhitespace();

			$char = $this->currentOrFail("',' or ']'");

			if ($char === ",") {
				$this->position++;

				continue;
			}

			if ($char === "]") {
				$this->position++;

				break;
			}

			throw $this->error("Expected ',' or ']'");
		}

		return $this->makeArrayTag($type, $values);
	}

	protected function parseArrayElement(string $type): int {
		$start = $this->position;

		while (!$this->eof() && $this->isLiteralChar($this->input[$this->position])) {
			$this->position++;
		}

		if ($this->position === $start) {
			throw $this->error("Expected an array element");
		}

		$end = $this->position;
		$this->position = $start;
		$literal = substr($this->input, $start, $end - $start);

		// Vanilla accepts the boolean keywords inside byte arrays, as bytes.
		if ($type === "B" && ($literal === "true" || $literal === "false")) {
			$this->position = $end;

			return $literal === "true" ? 1 : 0;
		}

		$elementType = match ($type) {
			"B" => ByteTag::class,
			"I" => IntTag::class,
			default => LongTag::class,
		};

		// An element without a type suffix takes the type of the array. Any
		// integer suffix is accepted, as long as the value fits the array type.
		// Decimals and float suffixes are not integers and throw.
		$tag = $this->classifyNumber($literal, $elementType);

		if (!$tag instanceof IntegerTag) {
			throw $this->error("Invalid {$type} array element \"{$literal}\"");
		}

		if ($tag->value < $elementType::MIN || $tag->value > $elementType::MAX) {
			throw $this->outOfRange($literal, $elementType, false);
		}

		$this->position = $end;

		return $tag->value;
	}

	/**
	 * @param list<int> $values
	 */
	protected function makeArrayTag(string $type, array $values): NumberArrayTag {
		return match ($type) {
			"B" => new ByteArrayTag($values),
			"I" => new IntArrayTag($values),
			default => new LongArrayTag($values),
		};
	}

	protected function parseList(): ListTag {
		$this->position++; // consume "["
		$items = [];

		$this->skipWhitespace();

		if ($this->currentIs("]")) {
			$this->position++;

			return new ListTag($items);
		}

		while (true) {
			$items[] = $this->parseValue();
			$this->skipWhitespace();

			$char = $this->currentOrFail("',' or ']'");

			if ($char === ",") {
				$this->position++;

				continue;
			}

			if ($char === "]") {
				$this->position++;

				break;
			}

			throw $this->error("Expected ',' or ']'");
		}

		return new ListTag($items);
	}

	protected function parseLiteral(): Tag {
		$start = $this->position;

		while (!$this->eof() && $this->isLiteralChar($this->input[$this->position])) {
			$this->position++;
		}

		if ($this->position === $start) {
			throw $this->error("Unexpected character");
		}

		$end = $this->position;
		$this->position = $start;
		$tag = $this->classifyLiteral(substr($this->input, $start, $end - $start));
		$this->position = $end;

		return $tag;
	}

	protected function classifyLiteral(string $literal): Tag {
		if ($literal === "true") {
			return new BooleanTag(true);
		}

		if ($literal === "false") {
			return new BooleanTag(false);
		}

		// Anything that is not a number is an unquoted string.
		return $this->classifyNumber($literal, IntTag::class) ?? new StringTag($literal);
	}

	/**
	 * Classify a literal as a number, or return null when it is not one.
	 * $defaultType is the integer type of an integer without a type suffix.
	 *
	 * Integers are decimal, hexadecimal (`0x`) or binary (`0b`), with `_`
	 * between digits. The type suffix (b/s/i/l) can carry a signedness prefix:
	 * `u` for unsigned or `s` for signed. Without one, decimal integers are
	 * signed and hexadecimal and binary integers are unsigned. An unsigned
	 * value is stored as the signed value with the same bits (240ub is -16b).
	 *
	 * @param class-string<IntegerTag> $defaultType
	 */
	protected function classifyNumber(string $literal, string $defaultType): ?Tag {
		// `b` is a hex digit, so a hex byte needs a signedness prefix (0x11ub).
		if (preg_match('/^([+-]?)0x([0-9a-f]+(?:_+[0-9a-f]+)*)(?:([su]?)([bsil]))?$/i', $literal, $matches) === 1) {
			return $this->integerTag($literal, $matches, 16, $defaultType);
		}

		if (preg_match('/^([+-]?)0b([01]+(?:_+[01]+)*)(?:([su]?)([bsil]))?$/i', $literal, $matches) === 1) {
			return $this->integerTag($literal, $matches, 2, $defaultType);
		}

		$digits = '\d+(?:_+\d+)*';
		$pattern = "/^([+-]?)(?:({$digits})(\\.(?:{$digits})?)?|(\\.{$digits}))(e[+-]?{$digits})?(?:([su]?)([bsil])|([fd]))?$/i";

		if (preg_match($pattern, $literal, $matches) !== 1) {
			return null;
		}

		[, $sign, $whole, $fraction, $bareFraction, $exponent, $signedness, $integerType, $floatType] = array_pad($matches, 9, "");
		$isDecimal = $fraction !== "" || $bareFraction !== "" || $exponent !== "";
		$mantissa = str_replace("_", "", $sign . $whole . $fraction . $bareFraction . $exponent);

		if ($floatType !== "") {
			return strtolower($floatType) === "f"
				? new FloatTag($this->floatLiteral($literal, $mantissa))
				: new DoubleTag($this->floatLiteral($literal, $mantissa));
		}

		if ($isDecimal) {
			if ($integerType !== "") {
				throw $this->error("Invalid integer \"{$literal}\"");
			}

			return new DoubleTag($this->floatLiteral($literal, $mantissa));
		}

		return $this->integerTag($literal, [ "", $sign, $whole, $signedness, $integerType ], 10, $defaultType);
	}

	/**
	 * @param array<int, string> $parts sign, digits, signedness and type suffix at indexes 1 to 4
	 * @param class-string<IntegerTag> $defaultType
	 */
	protected function integerTag(string $literal, array $parts, int $base, string $defaultType): IntegerTag {
		[, $sign, $digits, $signedness, $suffix] = array_pad($parts, 5, "");

		$type = match (strtolower($suffix)) {
			"b" => ByteTag::class,
			"s" => ShortTag::class,
			"i" => IntTag::class,
			"l" => LongTag::class,
			default => $defaultType,
		};

		$unsigned = $signedness === "" ? $base !== 10 : strtolower($signedness) === "u";
		$value = $this->integerValue($literal, $sign === "-", $digits, $base, $unsigned, $type);

		return match ($type) {
			ByteTag::class => new ByteTag($value),
			ShortTag::class => new ShortTag($value),
			IntTag::class => new IntTag($value),
			default => new LongTag($value),
		};
	}

	/**
	 * Convert integer digits in $base to the value of $type, checking the
	 * signed or unsigned range. An unsigned value is returned as the signed
	 * value with the same bits.
	 *
	 * @param class-string<IntegerTag> $type
	 */
	protected function integerValue(string $literal, bool $negative, string $digits, int $base, bool $unsigned, string $type): int {
		$bits = $this->bitsOf($type);

		// Accumulate the magnitude as an unsigned 64-bit number in two 32-bit
		// halves, so no intermediate value leaves the range of a PHP int.
		$high = 0;
		$low = 0;

		foreach (str_split(str_replace("_", "", $digits)) as $digit) {
			$low = $low * $base + (int) hexdec($digit);
			$high = $high * $base + ($low >> 32);
			$low &= 0xFFFFFFFF;

			if ($high > 0xFFFFFFFF) {
				throw $this->outOfRange($literal, $type, $unsigned);
			}
		}

		if ($unsigned) {
			$fits = $negative
				? ($high | $low) === 0
				: $bits === 64 || ($high === 0 && $low < 1 << $bits);

			if (!$fits) {
				throw $this->outOfRange($literal, $type, true);
			}

			if ($bits === 64) {
				return ($high << 32) | $low;
			}

			return $low >= 1 << ($bits - 1) ? $low - (1 << $bits) : $low;
		}

		$fits = $bits === 64
			? $high < 0x80000000 || ($negative && $high === 0x80000000 && $low === 0)
			: $high === 0 && ($negative ? $low <= 1 << ($bits - 1) : $low < 1 << ($bits - 1));

		if (!$fits) {
			throw $this->outOfRange($literal, $type, false);
		}

		$magnitude = ($high << 32) | $low;

		if (!$negative) {
			return $magnitude;
		}

		return $magnitude === PHP_INT_MIN ? PHP_INT_MIN : -$magnitude;
	}

	/**
	 * @param class-string<IntegerTag> $type
	 */
	protected function bitsOf(string $type): int {
		return match ($type) {
			ByteTag::class => 8,
			ShortTag::class => 16,
			IntTag::class => 32,
			default => 64,
		};
	}

	/**
	 * @param class-string<IntegerTag> $type
	 */
	protected function outOfRange(string $literal, string $type, bool $unsigned): SNBTParseException {
		$range = $unsigned
			? "0 to " . match ($this->bitsOf($type)) {
				8 => "255",
				16 => "65535",
				32 => "4294967295",
				default => "18446744073709551615",
			}
			: $type::MIN . " to " . $type::MAX;

		return $this->error("Integer \"{$literal}\" is out of range ({$range})");
	}

	protected function floatLiteral(string $literal, string $mantissa): float {
		$value = (float) $mantissa;

		if (!is_finite($value)) {
			throw $this->error("Number \"{$literal}\" is out of range");
		}

		return $value;
	}

	protected function readQuotedString(): string {
		$quote = $this->input[$this->position];
		$this->position++; // consume opening quote
		$result = "";

		while (true) {
			if ($this->eof()) {
				throw $this->error("Unterminated string");
			}

			$char = $this->input[$this->position];
			$this->position++;

			if ($char === "\\") {
				$result .= $this->readEscape($quote);

				continue;
			}

			if ($char === $quote) {
				return $result;
			}

			$result .= $char;
		}
	}

	protected function readEscape(string $quote): string {
		if ($this->eof()) {
			throw $this->error("Unterminated escape sequence");
		}

		$char = $this->input[$this->position];
		$this->position++;

		return match ($char) {
			"\\" => "\\",
			$quote => $quote,
			"n" => "\n",
			"r" => "\r",
			"t" => "\t",
			default => throw $this->error("Invalid escape sequence \"\\{$char}\""),
		};
	}

	protected function isLiteralChar(string $char): bool {
		return $char === "_"
			|| $char === "."
			|| $char === "+"
			|| $char === "-"
			|| ($char >= "0" && $char <= "9")
			|| ($char >= "A" && $char <= "Z")
			|| ($char >= "a" && $char <= "z");
	}

	protected function isWhitespace(string $char): bool {
		return $char === " " || $char === "\t" || $char === "\n" || $char === "\r";
	}

	protected function skipWhitespace(): void {
		while (!$this->eof() && $this->isWhitespace($this->input[$this->position])) {
			$this->position++;
		}
	}

	protected function eof(): bool {
		return $this->position >= $this->length;
	}

	protected function currentIs(string $char): bool {
		return !$this->eof() && $this->input[$this->position] === $char;
	}

	protected function currentOrFail(string $expected): string {
		if ($this->eof()) {
			throw $this->error("Expected {$expected} but reached the end of the input");
		}

		return $this->input[$this->position];
	}

	protected function expect(string $char): void {
		if (!$this->currentIs($char)) {
			throw $this->error("Expected '{$char}'");
		}

		$this->position++;
	}

	protected function error(string $message): SNBTParseException {
		return SNBTParseException::at($message, $this->input, $this->position);
	}
}
