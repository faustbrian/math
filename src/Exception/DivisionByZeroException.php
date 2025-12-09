<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to divide by zero.
 *
 * This exception is raised when performing division, modulo, or related operations where the divisor
 * is zero. Division by zero is undefined in mathematics and would produce an infinite or invalid result.
 * Applies to operations like dividedBy(), quotient(), remainder(), mod(), and related methods.
 */
final class DivisionByZeroException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for a division by zero error.
     *
     * @return self The exception instance.
     *
     * @pure
     */
    public static function divisionByZero(): self
    {
        return new self('Division by zero.');
    }
}
