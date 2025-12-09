<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a character is not valid for the given base.
 *
 * This exception occurs when parsing a string representation of a number in a
 * specific base (e.g., binary, octal, decimal, hexadecimal) and encountering
 * a character that is not valid for that base. For example, the character 'G'
 * is invalid in hexadecimal (base 16), or '8' is invalid in octal (base 8).
 *
 * Valid characters for each base range from '0' to the digit representing base-1,
 * using digits 0-9 and letters A-Z for bases up to 36.
 *
 * @see BigInteger::fromBase()
 * @see BigInteger::parse()
 */
final class InvalidCharacterInBaseException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for when a character is invalid for the specified base.
     *
     * @param string $char The invalid character that was encountered during parsing.
     * @param int    $base The numeric base (radix) being used for parsing, typically
     *                     between 2 and 36.
     *
     * @return self The exception instance with a formatted error message identifying
     *              the invalid character and the base it was invalid for.
     *
     * @pure This method has no side effects and can be safely used in any context.
     */
    public static function fromCharAndBase(string $char, int $base): self
    {
        return new self(sprintf(
            '"%s" is not a valid character in base %d.',
            $char,
            $base,
        ));
    }
}
