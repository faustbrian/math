<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to convert a negative number to unsigned bytes.
 */
final class NegativeByteConversionException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function negative(): self
    {
        return new self('Cannot convert a negative number to a byte string when $signed is false.');
    }
}
