<?php

namespace Stilling\SNBTParser\Exceptions;

/**
 * Thrown when SNBT input is malformed or otherwise cannot be parsed.
 *
 * The parser sets `position` (0-based byte offset), `lineNumber` and
 * `columnNumber` (both 1-based; the column counts bytes). They are null only
 * when the exception is constructed without them.
 */
class SNBTParseException extends \RuntimeException implements SNBTException {
	public function __construct(
		string $message = "",
		public readonly ?int $position = null,
		public readonly ?int $lineNumber = null,
		public readonly ?int $columnNumber = null,
		?\Throwable $previous = null,
	) {
		parent::__construct($message, 0, $previous);
	}

	/**
	 * Build an exception for the given byte offset in $input, with a short
	 * excerpt of the input from that offset in the message.
	 */
	public static function at(string $message, string $input, int $position): self {
		$before = substr($input, 0, $position);
		$lineStart = strrpos($before, "\n");
		$line = substr_count($before, "\n") + 1;
		$column = $position - ($lineStart === false ? 0 : $lineStart + 1) + 1;
		$snippet = substr($input, $position, 20);

		return new self("{$message} at position {$position} near \"{$snippet}\".", $position, $line, $column);
	}
}
