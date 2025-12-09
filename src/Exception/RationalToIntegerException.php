<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a rational number cannot be represented as an integer without rounding.
 *
 * This exception occurs when attempting to convert a rational number (fraction) to an integer
 * when the denominator does not evenly divide the numerator. For example, attempting to convert
 * 1/3 or 5/2 to an integer would require rounding, which triggers this exception when exact
 * conversion is required.
 */
final class RationalToIntegerException extends RuntimeException implements MathException
{
    /**
     * Creates a new exception indicating that integer conversion would require rounding.
     *
     * @return self The exception instance with a descriptive message.
     *
     * @pure
     */
    public static function roundingNecessary(): self
    {
        return new self('This rational number cannot be represented as an integer value without rounding.');
    }
}
