<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when an alphabet for arbitrary base conversion is too short.
 *
 * This exception is raised when attempting to use fromArbitraryBase() or toArbitraryBase()
 * with an alphabet containing fewer than 2 characters. At least 2 characters are required
 * to represent a valid base-N number system.
 */
final class AlphabetTooShortException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for an alphabet that is too short.
     *
     * @return self The exception instance.
     *
     * @pure
     */
    public static function tooShort(): self
    {
        return new self('The alphabet must contain at least 2 chars.');
    }
}
