<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when an empty string is provided where a number was expected.
 */
final class EmptyNumberException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function emptyString(): self
    {
        return new self('The number cannot be empty.');
    }
}
