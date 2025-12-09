<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to calculate the square root of a negative number.
 *
 * Square root operations are only defined for non-negative real numbers in this library.
 * This exception is thrown when attempting to compute the square root of a negative value,
 * which would require complex number support that is not available in this context.
 */
final class NegativeSquareRootException extends RuntimeException implements MathException
{
    /**
     * Creates a new exception indicating that square root of a negative number was attempted.
     *
     * @return self The exception instance with a descriptive message.
     *
     * @pure
     */
    public static function negative(): self
    {
        return new self('Cannot calculate the square root of a negative number.');
    }
}
