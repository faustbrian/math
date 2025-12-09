<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a division by zero occurs.
 */
final class DivisionByZeroException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function divisionByZero(): self
    {
        return new self('Division by zero.');
    }
}
