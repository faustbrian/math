<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when rounding is necessary to represent the square root at the requested scale.
 */
final class SquareRootRoundingException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function roundingNecessary(): self
    {
        return new self('Rounding is necessary to represent the square root at the requested scale.');
    }
}
