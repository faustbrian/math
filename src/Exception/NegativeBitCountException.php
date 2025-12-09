<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a negative number of bits is provided.
 */
final class NegativeBitCountException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function negative(): self
    {
        return new self('The number of bits cannot be negative.');
    }
}
