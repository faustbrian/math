<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when attempting to create a number from a string with an invalid format.
 *
 * @deprecated Use InvalidNumberFormatException or CharNotInAlphabetException instead.
 */
final class NumberFormatException extends RuntimeException implements MathException
{
    /**
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
