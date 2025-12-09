<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function dechex;
use function ord;
use function sprintf;
use function strtoupper;

/**
 * Exception thrown when a character in a number string is not valid for the given alphabet.
 *
 * This exception is raised during arbitrary base conversion when a character in the input string
 * does not exist in the provided alphabet. The error message includes the problematic character
 * in a readable format (quoted for printable chars, hex code for non-printable).
 */
final class CharNotInAlphabetException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for an invalid character in an alphabet.
     *
     * Formats non-printable characters (ASCII < 32 or > 126) as hexadecimal codes for clarity.
     *
     * @param string $char The invalid character found in the input string.
     *
     * @return self The exception instance with a formatted message.
     *
     * @pure
     */
    public static function fromChar(string $char): self
    {
        $ord = ord($char);

        if ($ord < 32 || $ord > 126) {
            $char = strtoupper(dechex($ord));

            if ($ord < 10) {
                $char = '0' . $char;
            }
        } else {
            $char = '"' . $char . '"';
        }

        return new self(sprintf('Char %s is not a valid character in the given alphabet.', $char));
    }
}
