<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when an exponent is outside the valid range.
 */
final class ExponentOutOfRangeException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function forExponent(int $exponent, int $max): self
    {
        return new self(sprintf(
            'The exponent %d is not in the range 0 to %d.',
            $exponent,
            $max,
        ));
    }
}
