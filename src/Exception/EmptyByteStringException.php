<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when an empty byte string is provided where data was expected.
 *
 * This exception is raised when calling BigInteger::fromBytes() with an empty byte string.
 * At least one byte is required to represent a numeric value in binary format.
 */
final class EmptyByteStringException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for an empty byte string input.
     *
     * @return self The exception instance.
     *
     * @pure
     */
    public static function empty(): self
    {
        return new self('The byte string must not be empty.');
    }
}
