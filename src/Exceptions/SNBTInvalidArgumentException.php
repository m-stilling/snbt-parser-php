<?php

namespace Stilling\SNBTParser\Exceptions;

/**
 * Thrown when a helper or a tag constructor receives a value it cannot represent.
 */
class SNBTInvalidArgumentException extends \InvalidArgumentException implements SNBTException {
}
