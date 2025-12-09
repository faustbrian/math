<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to find the next prime of a negative number.
 */
final class NegativePrimeSearchException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function negative(): self
    {
        return new self('Cannot find next prime of a negative number.');
    }
}
