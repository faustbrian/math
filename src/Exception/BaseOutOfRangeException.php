<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a numeric base is outside the valid range.
 *
 * This exception is raised when calling methods like fromBase() or toBase() with a base
 * that falls outside the acceptable range (typically 2 to 36).
 */
final class BaseOutOfRangeException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for a base outside the valid range.
     *
     * @param int $base The invalid base that was provided.
     * @param int $min  The minimum valid base value.
     * @param int $max  The maximum valid base value.
     *
     * @return self The exception instance with a descriptive message.
     *
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
