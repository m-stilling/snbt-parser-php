<?php

namespace Stilling\SNBTParser\Exceptions;

/**
 * Thrown when SNBT input is malformed or otherwise cannot be parsed.
 */
class SNBTParseException extends \RuntimeException implements SNBTException {
}
