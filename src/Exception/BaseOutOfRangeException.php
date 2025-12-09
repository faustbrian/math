<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a base is outside the valid range.
 */
final class BaseOutOfRangeException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function forBase(int $base, int $min, int $max): self
    {
        return new self(sprintf(
            'Base %d is not in range %d to %d.',
            $base,
            $min,
            $max,
        ));
    }
}
