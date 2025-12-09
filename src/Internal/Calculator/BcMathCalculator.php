<?php

declare(strict_types=1);

namespace Brick\Math\Internal\Calculator;

use Brick\Math\Internal\Calculator;
use Override;

use function bcadd;
use function bcdiv;
use function bcmod;
use function bcmul;
use function bcpow;
use function bcpowmod;
use function bcsqrt;
use function bcsub;

/**
 * Calculator implementation built around the bcmath library.
 *
 * This implementation uses PHP's BC Math extension to perform arbitrary precision arithmetic
 * on string representations of numbers. BC Math is a pure-integer calculator that operates
 * on strings to avoid the precision limitations of native PHP integers and floats.
 *
 * All operations use scale 0 (no decimal places) as this calculator is designed for integer
 * arithmetic only. The parent Calculator class provides additional functionality built on top
 * of these basic operations.
 *
 * @internal
 */
final readonly class BcMathCalculator extends Calculator
{
    /**
     * Adds two numbers.
     *
     * @param string $a The first number to add.
     * @param string $b The second number to add.
     *
     * @return string The sum of the two numbers.
     */
    #[Override]
    public function add(string $a, string $b): string
    {
        return bcadd($a, $b, 0);
    }

    /**
     * Subtracts two numbers.
     *
     * @param string $a The number to subtract from.
     * @param string $b The number to subtract.
     *
     * @return string The difference of the two numbers.
     */
    #[Override]
    public function sub(string $a, string $b): string
    {
        return bcsub($a, $b, 0);
    }

    /**
     * Multiplies two numbers.
     *
     * @param string $a The first number to multiply.
     * @param string $b The second number to multiply.
     *
     * @return string The product of the two numbers.
     */
    #[Override]
    public function mul(string $a, string $b): string
    {
        return bcmul($a, $b, 0);
    }

    /**
     * Returns the quotient of the division of two numbers.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return string The quotient, rounded toward zero.
     */
    #[Override]
    public function divQ(string $a, string $b): string
    {
        return bcdiv($a, $b, 0);
    }

    /**
     * Returns the remainder of the division of two numbers.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return string The remainder, with the same sign as the dividend.
     */
    #[Override]
    public function divR(string $a, string $b): string
    {
        return bcmod($a, $b, 0);
    }

    /**
     * Returns the quotient and remainder of the division of two numbers.
     *
     * This method performs both operations efficiently in a single call, which is useful
     * when both the quotient and remainder are needed.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return array{string, string} An array containing the quotient and remainder.
     */
    #[Override]
    public function divQR(string $a, string $b): array
    {
        $q = bcdiv($a, $b, 0);
        $r = bcmod($a, $b, 0);

        return [$q, $r];
    }

    /**
     * Exponentiates a number.
     *
     * Raises the base to the power of the exponent. The exponent must be within the range
     * 0 to MAX_POWER to prevent excessive computation time.
     *
     * @param string $a The base number.
     * @param int    $e The exponent, validated as an integer between 0 and MAX_POWER.
     *
     * @return string The result of raising $a to the power of $e.
     */
    #[Override]
    public function pow(string $a, int $e): string
    {
        return bcpow($a, (string) $e, 0);
    }

    /**
     * Raises a number into power with modulo.
     *
     * This method efficiently computes (base^exp) mod mod, which is useful for
     * cryptographic operations and modular arithmetic. The implementation uses
     * BC Math's built-in modular exponentiation for optimal performance.
     *
     * @param string $base The base number; must be positive or zero.
     * @param string $exp  The exponent; must be positive or zero.
     * @param string $mod  The modulus; must be strictly positive.
     *
     * @return string The result of (base^exp) mod mod.
     */
    #[Override]
    public function modPow(string $base, string $exp, string $mod): string
    {
        return bcpowmod($base, $exp, $mod, 0);
    }

    /**
     * Returns the square root of the given number, rounded down.
     *
     * The result is the largest integer x such that x² ≤ n. The input must not be negative
     * as square roots of negative numbers are not supported in this implementation.
     *
     * @param string $n The number to calculate the square root of; must not be negative.
     *
     * @return string The square root rounded down to the nearest integer.
     */
    #[Override]
    public function sqrt(string $n): string
    {
        return bcsqrt($n, 0);
    }
}
