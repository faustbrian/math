<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to create a rational number with a denominator of zero.
 *
 * This exception is raised when constructing a BigRational with a zero denominator, which would
 * represent an undefined mathematical operation (division by zero). Rational numbers must have
 * a non-zero denominator to be valid.
 */
final class DenominatorMustNotBeZeroException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for a zero denominator in a rational number.
     *
     * @return self The exception instance.
     *
     * @pure
     */
    public static function zero(): self
    {
        return new self('The denominator of a rational number cannot be zero.');
    }
}
