<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when attempting to create a number from a string with an invalid format.
 *
 * This exception is thrown when parsing numeric strings that don't match the expected
 * format for decimal numbers. The string must contain only digits, an optional leading
 * sign, and an optional decimal point with valid positioning.
 *
 * @deprecated Use InvalidNumberFormatException or CharNotInAlphabetException instead.
 */
final class NumberFormatException extends RuntimeException implements MathException
{
    /**
     * Creates a new exception indicating that a string value has an invalid numeric format.
     *
     * @param string $value The invalid value that was attempted to be parsed as a number.
     *
     * @return self The exception instance with the invalid value in the message.
     *
     * @pure
     *
     * @deprecated Use InvalidNumberFormatException::fromValue() instead.
     */
    public static function invalidFormat(string $value): self
    {
        return new self(sprintf(
            'The given value "%s" does not represent a valid number.',
            $value,
        ));
    }
}
