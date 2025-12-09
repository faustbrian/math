<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when an empty byte string is provided where bytes were expected.
 */
final class EmptyByteStringException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function empty(): self
    {
        return new self('The byte string must not be empty.');
    }
}
