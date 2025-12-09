<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when an exponent is outside the valid range.
 *
 * This exception occurs when attempting power operations with exponents
 * that exceed implementation limits or are negative where only non-negative
 * exponents are supported. The valid range is typically 0 to a maximum value
 * determined by the specific operation being performed.
 *
 * @see BigInteger::power()
 * @see BigDecimal::power()
 */
final class ExponentOutOfRangeException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for when an exponent is outside the valid range.
     *
     * @param int $exponent The exponent value that was provided and is out of range.
     * @param int $max      The maximum allowed exponent value for the operation.
     *
     * @return self The exception instance with a formatted error message indicating
     *              the provided exponent and the valid range (0 to max).
     *
     * @pure This method has no side effects and can be safely used in any context.
     */
    public static function forExponent(int $exponent, int $max): self
    {
        return new self(sprintf(
            'The exponent %d is not in the range 0 to %d.',
            $exponent,
            $max,
        ));
    }
}
