<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a negative operand is provided to operations requiring non-negative values.
 *
 * Various mathematical operations in number theory and combinatorics are only defined
 * for non-negative integers. This exception is thrown when negative values are provided
 * to functions such as factorial, permutations, binomial coefficients, or modular
 * exponentiation with specific constraints.
 */
final class NegativeOperandException extends RuntimeException implements MathException
{
    /**
     * Creates an exception for negative operands in modular exponentiation.
     *
     * The modPow() operation requires non-negative base and exponent values when
     * working with certain modular arithmetic constraints. This factory method
     * creates an exception when negative operands violate these requirements.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function inModPow(): self
    {
        return new self('The operands cannot be negative.');
    }

    /**
     * Creates an exception for factorial of a negative number.
     *
     * The factorial function is only defined for non-negative integers. Computing
     * factorial of a negative number is mathematically undefined and results in
     * this exception being thrown.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function factorialOfNegative(): self
    {
        return new self('Factorial is not defined for negative numbers.');
    }

    /**
     * Creates an exception for binomial coefficient with negative n.
     *
     * The binomial coefficient C(n, k) requires n to be a non-negative integer.
     * While some mathematical extensions define binomial coefficients for negative
     * values, this implementation restricts n to non-negative integers.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function binomialOfNegative(): self
    {
        return new self('Binomial coefficient is not defined for negative n.');
    }

    /**
     * Creates an exception for permutations with negative n.
     *
     * The permutation function P(n, k) requires n to be a non-negative integer
     * representing the total number of items. Negative values for n are
     * mathematically undefined and trigger this exception.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function permutationsOfNegative(): self
    {
        return new self('Permutations are not defined for negative n.');
    }

    /**
     * Creates an exception for double factorial of a negative number.
     *
     * The double factorial function (n!!) is only defined for non-negative integers.
     * Double factorial computes the product of every other integer (e.g., 7!! = 7×5×3×1),
     * and this operation is undefined for negative values.
     *
     * @return self The exception instance with a descriptive error message.
     *
     * @pure This method has no side effects and always returns a new instance.
     */
    public static function doubleFactorialOfNegative(): self
    {
        return new self('Double factorial is not defined for negative numbers.');
    }
}
