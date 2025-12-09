<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when the number of rounds is less than the minimum required.
 *
 * This exception occurs in algorithms that require a minimum number of iterations
 * or rounds to function correctly, such as primality testing algorithms. Providing
 * zero or a negative number of rounds would render the algorithm ineffective or
 * produce invalid results.
 *
 * The minimum required rounds ensures the algorithm can perform its intended
 * function with a baseline level of accuracy or completeness.
 *
 * @see BigInteger::isProbablePrime()
 */
final class MinimumRoundsException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for when the number of rounds is less than one.
     *
     * This validation ensures that algorithms requiring iterative operations
     * receive a valid positive number of rounds to execute. At least one round
     * is necessary for the algorithm to perform any meaningful computation.
     *
     * @return self The exception instance with an appropriate error message.
     *
     * @pure This method has no side effects and can be safely used in any context.
     */
    public static function atLeastOne(): self
    {
        return new self('The number of rounds must be at least 1.');
    }
}
