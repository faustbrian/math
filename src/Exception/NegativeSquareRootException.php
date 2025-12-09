<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to calculate the square root of a negative number.
 */
final class NegativeSquareRootException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function negative(): self
    {
        return new self('Cannot calculate the square root of a negative number.');
    }
}
