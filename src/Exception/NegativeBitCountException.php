<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a negative bit count is provided to bitwise operations.
 *
 * Bit count parameters specify the number of bits to operate on and must be
 * non-negative integers. Operations such as bit shifting, bit masking, or bit
 * field extraction require valid positive bit counts to function correctly.
 */
final class NegativeBitCountException extends RuntimeException implements MathException
{
    /**
     * Creates an exception indicating a negative bit count was provided.
     *
     * Bitwise operations require non-negative bit count values to determine
     * the number of bits to process. This exception is thrown when a negative
     * value is provided where a valid bit count is expected.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function negative(): self
    {
        return new self('The number of bits cannot be negative.');
    }
}
