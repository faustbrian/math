<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a negative bit index is provided to bit testing operations.
 *
 * Bit indices specify the position of a bit within a number, where index 0 represents
 * the least significant bit. Negative indices are invalid as bit positions must be
 * non-negative integers to correctly identify specific bits in a binary representation.
 */
final class NegativeBitIndexException extends RuntimeException implements MathException
{
    /**
     * Creates an exception indicating a negative bit index was provided.
     *
     * Bit testing operations such as testBit() require non-negative index values
     * to identify which bit position to examine. This exception is thrown when
     * a negative index is provided where a valid bit position is expected.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function negative(): self
    {
        return new self('The bit to test cannot be negative.');
    }
}
