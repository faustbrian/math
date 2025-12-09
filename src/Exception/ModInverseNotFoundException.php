<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a modular multiplicative inverse cannot be computed.
 */
final class ModInverseNotFoundException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function notFound(): self
    {
        return new self('Unable to compute the modInverse for the given modulus.');
    }
}
