<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to calculate an even root of a negative number.
 */
final class EvenRootOfNegativeException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function negative(): self
    {
        return new self('Cannot calculate an even root of a negative number.');
    }
}
