<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a rational number cannot be represented as an integer without rounding.
 */
final class RationalToIntegerException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function roundingNecessary(): self
    {
        return new self('This rational number cannot be represented as an integer value without rounding.');
    }
}
