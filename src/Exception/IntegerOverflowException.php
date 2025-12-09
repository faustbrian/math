<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use Brick\Math\BigInteger;
use RuntimeException;

use function sprintf;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Exception thrown when an integer overflow occurs.
 *
 * This exception is thrown when attempting to convert a BigInteger value
 * to a native PHP integer (int) and the value exceeds the representable range
 * of PHP_INT_MIN to PHP_INT_MAX. BigInteger can represent arbitrarily large
 * numbers, but native PHP integers have fixed size limits based on the platform.
 *
 * @see BigInteger::toInt()
 */
final class IntegerOverflowException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for when a BigInteger cannot be converted to a native integer.
     *
     * This method constructs an informative error message that includes the actual
     * BigInteger value that caused the overflow and the valid range for PHP integers
     * on the current platform (PHP_INT_MIN to PHP_INT_MAX).
     *
     * @param BigInteger $value The BigInteger value that exceeds the native integer range.
     *
     * @return self The exception instance with a formatted error message showing the
     *              value and the valid integer range.
     *
     * @pure This method has no side effects and can be safely used in any context.
     */
    public static function toIntOverflow(BigInteger $value): self
    {
        $message = '%s is out of range %d to %d and cannot be represented as an integer.';

        return new self(sprintf($message, (string) $value, PHP_INT_MIN, PHP_INT_MAX));
    }
}
