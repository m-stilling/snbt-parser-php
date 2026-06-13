<?php

namespace Stilling\SNBTParser\Exceptions;

/**
 * Marker interface implemented by every exception this package throws, so
 * consumers can catch all of them with a single catch block.
 */
interface SNBTException extends \Throwable {
}
