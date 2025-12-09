<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when an alphabet does not contain at least 2 characters.
 */
final class AlphabetTooShortException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function tooShort(): self
    {
        return new self('The alphabet must contain at least 2 chars.');
    }
}
