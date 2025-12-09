<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a modulus of zero is provided to a modular operation.
 *
 * Modular arithmetic operations require a non-zero modulus value, as division
 * by zero is undefined mathematically. This exception prevents invalid modular
 * calculations that would result in undefined behavior.
 */
final class ModulusMustNotBeZeroException extends RuntimeException implements MathException
{
    /**
     * Creates an exception indicating a zero modulus was provided.
     *
     * Modular operations such as mod(), modPow(), and modInverse() require a
     * non-zero modulus. This factory method creates an exception with a clear
     * message when this constraint is violated.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function zero(): self
    {
        return new self('The modulus must not be zero.');
    }
}
