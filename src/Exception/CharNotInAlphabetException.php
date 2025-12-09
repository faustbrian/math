<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function dechex;
use function ord;
use function sprintf;
use function strtoupper;

/**
 * Exception thrown when a character is not valid in the given alphabet.
 */
final class CharNotInAlphabetException extends RuntimeException implements MathException
{
    /**
     * @param string $char The failing character.
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
