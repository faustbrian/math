<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a negative modulus is provided where a non-negative one is required.
 *
 * While some mathematical operations accept negative moduli with specific semantics,
 * certain operations in this library require strictly non-negative modulus values to
 * maintain consistent behavior and avoid ambiguous results. This exception enforces
 * that constraint for operations with this requirement.
 */
final class NegativeModulusException extends RuntimeException implements MathException
{
    /**
     * Creates an exception indicating a negative modulus was provided.
     *
     * Operations that require non-negative moduli include certain modular arithmetic
     * functions where negative modulus values would lead to ambiguous or undefined
     * behavior. This exception is thrown to enforce the non-negative constraint.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function negative(): self
    {
        return new self('Modulus must not be negative.');
    }
}
