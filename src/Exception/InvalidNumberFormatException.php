<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when attempting to create a number from a string with an invalid format.
 *
 * This exception occurs when parsing a string that does not conform to the expected
 * numeric format for BigInteger, BigDecimal, or BigRational types. The string may
 * contain invalid characters, incorrect placement of signs or decimal points, or
 * other formatting issues that prevent successful parsing.
 *
 * Common causes include:
 * - Multiple decimal points in a decimal number
 * - Invalid characters mixed with digits
 * - Malformed scientific notation
 * - Improper fraction notation (for BigRational)
 *
 * @see BigInteger::of()
 * @see BigDecimal::of()
 * @see BigRational::of()
 */
final class InvalidNumberFormatException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for when a string value does not represent a valid number.
     *
     * @param string $value The string value that failed to parse as a valid number.
     *
     * @return self The exception instance with a formatted error message including
     *              the invalid value that was provided.
     *
     * @pure This method has no side effects and can be safely used in any context.
     */
    public static function fromValue(string $value): self
    {
        return new self(sprintf(
            'The given value "%s" does not represent a valid number.',
            $value,
        ));
    }
}
