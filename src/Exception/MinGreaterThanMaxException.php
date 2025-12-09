<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when $min is greater than $max in a range operation.
 *
 * This exception occurs when attempting to generate a random number within a range
 * where the minimum value is greater than the maximum value. The range bounds must
 * satisfy the constraint: min <= max.
 *
 * This validation ensures logical correctness for operations that require a valid
 * numeric range, such as random number generation between two bounds.
 *
 * @see BigInteger::randomRange()
 */
final class MinGreaterThanMaxException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for when minimum is greater than maximum in a random range.
     *
     * This method is specifically used for random range generation operations where
     * the caller provided a minimum value that exceeds the maximum value, which
     * violates the mathematical constraint of a valid range.
     *
     * @return self The exception instance with an appropriate error message.
     *
     * @pure This method has no side effects and can be safely used in any context.
     */
    public static function inRandomRange(): self
    {
        return new self('$min cannot be greater than $max.');
    }
}
