<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to convert a negative number to an arbitrary base.
 *
 * The toArbitraryBase() method only supports non-negative integers, as arbitrary
 * base representations (such as base-62 encoding) are typically defined for positive
 * values. Negative numbers require special handling with sign prefixes which is not
 * supported by the standard conversion algorithm.
 */
final class NegativeArbitraryBaseException extends RuntimeException implements MathException
{
    /**
     * Creates an exception indicating a negative number was provided for base conversion.
     *
     * Arbitrary base conversion algorithms work with non-negative integers. When a
     * negative value is provided to toArbitraryBase(), this exception is thrown to
     * prevent invalid conversion attempts.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function negative(): self
    {
        return new self('toArbitraryBase() does not support negative numbers.');
    }
}
