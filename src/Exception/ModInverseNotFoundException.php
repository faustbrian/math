<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a modular multiplicative inverse cannot be computed.
 *
 * A modular multiplicative inverse of a number 'a' modulo 'm' exists only when
 * 'a' and 'm' are coprime (i.e., their greatest common divisor is 1). This
 * exception is thrown when attempting to compute modInverse() for numbers that
 * do not meet this requirement.
 *
 * @see https://en.wikipedia.org/wiki/Modular_multiplicative_inverse
 */
final class ModInverseNotFoundException extends RuntimeException implements MathException
{
    /**
     * Creates an exception indicating the modular inverse could not be computed.
     *
     * This occurs when the given number and modulus are not coprime, meaning
     * no multiplicative inverse exists in the specified modular arithmetic system.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function notFound(): self
    {
        return new self('Unable to compute the modInverse for the given modulus.');
    }
}
