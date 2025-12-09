<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to find the next prime number starting from a negative value.
 *
 * Prime numbers are defined only for positive integers greater than 1. Operations
 * that search for the next prime number, such as nextPrime(), require a starting
 * point of at least -1 (which would return 2, the first prime). Attempting to find
 * primes from negative starting points is mathematically undefined.
 */
final class NegativePrimeSearchException extends RuntimeException implements MathException
{
    /**
     * Creates an exception indicating a prime search was attempted from a negative number.
     *
     * Prime number search operations require a non-negative starting point. This
     * exception is thrown when attempting to find the next prime number beginning
     * from a negative value, as this operation is undefined in number theory.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function negative(): self
    {
        return new self('Cannot find next prime of a negative number.');
    }
}
