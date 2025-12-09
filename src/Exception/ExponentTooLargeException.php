<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when an exponent in scientific notation is too large to represent.
 *
 * This exception occurs during parsing of numbers in scientific notation (e.g., "1.23e999999")
 * when the exponent portion exceeds the maximum representable integer value. This prevents
 * potential memory exhaustion or overflow when constructing the resulting number.
 *
 * Unlike ExponentOutOfRangeException which handles operational constraints,
 * this exception specifically handles parsing limits during number construction.
 *
 * @see BigInteger::of()
 * @see BigDecimal::of()
 */
final class ExponentTooLargeException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for when a scientific notation exponent is too large.
     *
     * @return self The exception instance with an appropriate error message.
     *
     * @pure This method has no side effects and can be safely used in any context.
     */
    public static function tooLarge(): self
    {
        return new self('Exponent too large.');
    }
}
