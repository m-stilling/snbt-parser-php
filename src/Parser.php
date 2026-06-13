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
use Stilling\SNBTParser\Tag\ListTag;
use Stilling\SNBTParser\Tag\LongArrayTag;
use Stilling\SNBTParser\Tag\LongTag;
use Stilling\SNBTParser\Tag\NumberArrayTag;
use Stilling\SNBTParser\Tag\ShortTag;
use Stilling\SNBTParser\Tag\StringTag;
use Stilling\SNBTParser\Tag\Tag;

/**
 * A single-pass, recursive-descent SNBT parser that builds a typed tag tree
 * directly from the input — no JSON round-trip. Structural characters are all
 * ASCII, so the input is scanned byte by byte; multibyte string contents pass
 * through untouched (UTF-8 continuation bytes never collide with the ASCII
 * delimiters), which is why no mbstring functions are needed.
 */
final class Parser {
	private int $position = 0;

	private readonly int $length;

	public function __construct(private readonly string $input) {
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

	private function parseValue(): Tag {
		$this->skipWhitespace();

		$char = $this->currentOrFail("a value");

		return match (true) {
			$char === "{" => $this->parseCompound(),
			$char === "[" => $this->parseListOrArray(),
			$char === '"' || $char === "'" => new StringTag($this->readQuotedString()),
			default => $this->parseLiteral(),
		};
	}

	private function parseCompound(): CompoundTag {
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

	private function parseKey(): string {
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

	private function parseListOrArray(): Tag {
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
	private function detectTypedArray(): ?string {
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

	private function parseTypedArray(string $type): NumberArrayTag {
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
			$values[] = $this->parseArrayElement();
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

	private function parseArrayElement(): int {
		$start = $this->position;

		while (!$this->eof() && $this->isLiteralChar($this->input[$this->position])) {
			$this->position++;
		}

		if ($this->position === $start) {
			throw $this->error("Expected an array element");
		}

		// The literal may carry a type suffix (e.g. "1b"); the int cast stops at it.
		return (int) substr($this->input, $start, $this->position - $start);
	}

	/**
	 * @param list<int> $values
	 */
	private function makeArrayTag(string $type, array $values): NumberArrayTag {
		return match ($type) {
			"B" => new ByteArrayTag($values),
			"I" => new IntArrayTag($values),
			default => new LongArrayTag($values),
		};
	}

	private function parseList(): ListTag {
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

	private function parseLiteral(): Tag {
		$start = $this->position;

		while (!$this->eof() && $this->isLiteralChar($this->input[$this->position])) {
			$this->position++;
		}

		if ($this->position === $start) {
			throw $this->error("Unexpected character");
		}

		return $this->classifyLiteral(substr($this->input, $start, $this->position - $start));
	}

	private function classifyLiteral(string $literal): Tag {
		if ($literal === "true") {
			return new BooleanTag(true);
		}

		if ($literal === "false") {
			return new BooleanTag(false);
		}

		if (preg_match('/^([+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?)([bBsSiIlLfFdD]?)$/', $literal, $matches) === 1) {
			$mantissa = $matches[1];
			$suffix = strtolower($matches[2]);

			return match ($suffix) {
				"b" => new ByteTag((int) $mantissa),
				"s" => new ShortTag((int) $mantissa),
				"i" => new IntTag((int) $mantissa),
				"l" => new LongTag((int) $mantissa),
				"f" => new FloatTag((float) $mantissa),
				"d" => new DoubleTag((float) $mantissa),
				default => $this->classifyUnsuffixedNumber($mantissa),
			};
		}

		// Anything else is an unquoted string.
		return new StringTag($literal);
	}

	private function classifyUnsuffixedNumber(string $mantissa): Tag {
		if (str_contains($mantissa, ".") || str_contains($mantissa, "e") || str_contains($mantissa, "E")) {
			return new DoubleTag((float) $mantissa);
		}

		return new IntTag((int) $mantissa);
	}

	private function readQuotedString(): string {
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

	private function readEscape(string $quote): string {
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

	private function isLiteralChar(string $char): bool {
		return $char === "_"
			|| $char === "."
			|| $char === "+"
			|| $char === "-"
			|| ctype_alnum($char);
	}

	private function isWhitespace(string $char): bool {
		return $char === " " || $char === "\t" || $char === "\n" || $char === "\r";
	}

	private function skipWhitespace(): void {
		while (!$this->eof() && $this->isWhitespace($this->input[$this->position])) {
			$this->position++;
		}
	}

	private function eof(): bool {
		return $this->position >= $this->length;
	}

	private function currentIs(string $char): bool {
		return !$this->eof() && $this->input[$this->position] === $char;
	}

	private function currentOrFail(string $expected): string {
		if ($this->eof()) {
			throw $this->error("Expected {$expected} but reached the end of the input");
		}

		return $this->input[$this->position];
	}

	private function expect(string $char): void {
		if (!$this->currentIs($char)) {
			throw $this->error("Expected '{$char}'");
		}

		$this->position++;
	}

	private function error(string $message): SNBTParseException {
		$snippet = substr($this->input, $this->position, 20);

		return new SNBTParseException("{$message} at position {$this->position} near \"{$snippet}\".");
	}
}
