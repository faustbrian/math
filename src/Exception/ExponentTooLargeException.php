<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when an exponent in scientific notation is too large to represent.
 */
final class ExponentTooLargeException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function tooLarge(): self
    {
        return new self('Exponent too large.');
    }
}
