<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when attempting to create a number from a string with an invalid format.
 */
final class InvalidNumberFormatException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function fromValue(string $value): self
    {
        return new self(sprintf(
            'The given value "%s" does not represent a valid number.',
            $value,
        ));
    }
}
