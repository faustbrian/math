<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a number cannot be represented at the requested scale without rounding.
 *
 * This exception occurs when an exact decimal operation would produce more decimal places
 * than the requested scale allows, and no rounding mode has been specified. For example,
 * dividing 1 by 3 with a scale of 2 produces 0.333... which cannot be exactly represented
 * as 0.33 without rounding.
 */
final class RoundingNecessaryException extends RuntimeException implements MathException
{
    /**
     * Creates a new exception indicating that rounding would be required for the operation.
     *
     * @return self The exception instance with a descriptive message.
     *
     * @pure
     */
    public static function roundingNecessary(): self
    {
        return new self('Rounding is necessary to represent the result of the operation at this scale.');
    }
}
