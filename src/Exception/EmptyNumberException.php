<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when an empty string is provided where a number was expected.
 *
 * This exception indicates that parsing or construction of a numeric type
 * failed because the input string was empty. Numeric types require at least
 * one character to represent a valid value.
 *
 * @see BigInteger::of()
 * @see BigDecimal::of()
 * @see BigRational::of()
 */
final class EmptyNumberException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for when an empty string was provided.
     *
     * @return self The exception instance with an appropriate error message.
     *
     * @pure This method has no side effects and can be safely used in any context.
     */
    public static function emptyString(): self
    {
        return new self('The number cannot be empty.');
    }
}
