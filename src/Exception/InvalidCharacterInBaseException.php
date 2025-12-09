<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a character is not valid for the given base.
 */
final class InvalidCharacterInBaseException extends RuntimeException implements MathException
{
    /**
     * @pure
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
