<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when rounding is necessary to represent the square root at the requested scale.
 *
 * This exception occurs when calculating square roots that produce irrational results which
 * cannot be exactly represented at the specified scale. For example, sqrt(2) produces
 * 1.41421356... which cannot be represented exactly with a finite number of decimal places,
 * requiring rounding if no rounding mode is specified.
 */
final class SquareRootRoundingException extends RuntimeException implements MathException
{
    /**
     * Creates a new exception indicating that rounding would be required for the square root.
     *
     * @return self The exception instance with a descriptive message.
     *
     * @pure
     */
    public static function roundingNecessary(): self
    {
        return new self('Rounding is necessary to represent the square root at the requested scale.');
    }
}
