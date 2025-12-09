<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a negative modulus is provided where a non-negative one is required.
 */
final class NegativeModulusException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function negative(): self
    {
        return new self('Modulus must not be negative.');
    }
}
