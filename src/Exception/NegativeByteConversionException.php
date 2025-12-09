<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to convert a negative number to unsigned bytes.
 *
 * Byte string conversion can produce either signed or unsigned representations.
 * When the $signed parameter is false, only non-negative numbers can be converted
 * to byte strings, as unsigned byte representations cannot encode negative values.
 * Use signed conversion mode to properly represent negative numbers in byte format.
 */
final class NegativeByteConversionException extends RuntimeException implements MathException
{
    /**
     * Creates an exception indicating unsigned byte conversion was attempted on a negative number.
     *
     * This exception is thrown when toBytes() is called with $signed = false on a
     * negative number. Unsigned byte representations can only encode non-negative
     * values. To convert negative numbers, use $signed = true to enable two's
     * complement encoding.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function negative(): self
    {
        return new self('Cannot convert a negative number to a byte string when $signed is false.');
    }
}
