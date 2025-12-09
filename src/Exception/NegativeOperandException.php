<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a negative operand is provided where non-negative operands are required.
 */
final class NegativeOperandException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function inModPow(): self
    {
        return new self('The operands cannot be negative.');
    }

    /**
     * @pure
     */
    public static function factorialOfNegative(): self
    {
        return new self('Factorial is not defined for negative numbers.');
    }

    /**
     * @pure
     */
    public static function binomialOfNegative(): self
    {
        return new self('Binomial coefficient is not defined for negative n.');
    }

    /**
     * @pure
     */
    public static function permutationsOfNegative(): self
    {
        return new self('Permutations are not defined for negative n.');
    }

    /**
     * @pure
     */
    public static function doubleFactorialOfNegative(): self
    {
        return new self('Double factorial is not defined for negative numbers.');
    }
}
