<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to calculate an even root of a negative number.
 *
 * Even roots (square root, 4th root, 6th root, etc.) of negative numbers
 * produce complex numbers that cannot be represented by real-number types.
 * This exception is thrown when such an operation is attempted on BigInteger
 * or BigDecimal instances.
 *
 * Odd roots (cube root, 5th root, etc.) of negative numbers are valid and
 * will not throw this exception.
 *
 * @see BigInteger::root()
 * @see BigDecimal::sqrt()
 */
final class EvenRootOfNegativeException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for when an even root of a negative number is attempted.
     *
     * @return self The exception instance with an appropriate error message.
     *
     * @pure This method has no side effects and can be safely used in any context.
     */
    public static function negative(): self
    {
        return new self('Cannot calculate an even root of a negative number.');
    }
}
