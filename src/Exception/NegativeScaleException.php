<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a negative scale is provided to an operation that requires non-negative scale.
 *
 * Scale determines the number of decimal places in the result of mathematical operations.
 * This exception is thrown when attempting to use a negative scale value where only
 * zero or positive values are valid.
 */
final class NegativeScaleException extends RuntimeException implements MathException
{
    /**
     * Creates a new exception indicating that a negative scale was provided.
     *
     * @return self The exception instance with a descriptive message.
     *
     * @pure
     */
    public static function negative(): self
    {
        return new self('Scale cannot be negative.');
    }
}
