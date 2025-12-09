<?php

declare(strict_types=1);

namespace Brick\Math\Internal\Calculator;

use Brick\Math\Internal\Calculator;
use GMP;
use Override;

use function gmp_add;
use function gmp_and;
use function gmp_div_q;
use function gmp_div_qr;
use function gmp_div_r;
use function gmp_gcd;
use function gmp_init;
use function gmp_invert;
use function gmp_mul;
use function gmp_or;
use function gmp_pow;
use function gmp_powm;
use function gmp_sqrt;
use function gmp_strval;
use function gmp_sub;
use function gmp_xor;

/**
 * High-performance calculator implementation using the GMP extension.
 *
 * This implementation provides optimal performance for arbitrary-precision arithmetic
 * operations by leveraging PHP's GMP extension. GMP is significantly faster than pure
 * PHP implementations for large number calculations and should be preferred when available.
 *
 * All methods accept and return string representations of integers to avoid PHP's native
 * integer overflow limitations. Input strings must follow Calculator conventions: digits
 * only, with optional leading minus sign, no leading zeros (except for "0" itself).
 *
 * @internal
 */
final readonly class GmpCalculator extends Calculator
{
    /**
     * Adds two numbers.
     *
     * @param string $a The first operand
     * @param string $b The second operand
     * @return string The sum of the two numbers
     *
     * @pure
     */
    #[Override]
    public function add(string $a, string $b): string
    {
        return gmp_strval(gmp_add($a, $b));
    }

    /**
     * Subtracts the second number from the first.
     *
     * @param string $a The minuend
     * @param string $b The subtrahend
     * @return string The difference between the two numbers
     *
     * @pure
     */
    #[Override]
    public function sub(string $a, string $b): string
    {
        return gmp_strval(gmp_sub($a, $b));
    }

    /**
     * Multiplies two numbers.
     *
     * @param string $a The first factor
     * @param string $b The second factor
     * @return string The product of the two numbers
     *
     * @pure
     */
    #[Override]
    public function mul(string $a, string $b): string
    {
        return gmp_strval(gmp_mul($a, $b));
    }

    /**
     * Returns the quotient of dividing the first number by the second.
     *
     * @param string $a The dividend
     * @param string $b The divisor, must not be zero
     * @return string The quotient of the division
     *
     * @pure
     */
    #[Override]
    public function divQ(string $a, string $b): string
    {
        return gmp_strval(gmp_div_q($a, $b));
    }

    /**
     * Returns the remainder of dividing the first number by the second.
     *
     * @param string $a The dividend
     * @param string $b The divisor, must not be zero
     * @return string The remainder of the division
     *
     * @pure
     */
    #[Override]
    public function divR(string $a, string $b): string
    {
        return gmp_strval(gmp_div_r($a, $b));
    }

    /**
     * Returns both the quotient and remainder of division in a single operation.
     *
     * This is more efficient than calling divQ() and divR() separately when both
     * values are needed, as it performs the division only once.
     *
     * @param string $a The dividend
     * @param string $b The divisor, must not be zero
     * @return array{string, string} Array containing quotient at index 0 and remainder at index 1
     *
     * @pure
     */
    #[Override]
    public function divQR(string $a, string $b): array
    {
        [$q, $r] = gmp_div_qr($a, $b);

        /**
         * @var GMP $q
         * @var GMP $r
         */
        return [
            gmp_strval($q),
            gmp_strval($r),
        ];
    }

    /**
     * Raises a number to the specified power.
     *
     * @param string $a The base number
     * @param int $e The exponent, must be between 0 and Calculator::MAX_POWER
     * @return string The result of raising $a to the power of $e
     *
     * @pure
     */
    #[Override]
    public function pow(string $a, int $e): string
    {
        return gmp_strval(gmp_pow($a, $e));
    }

    /**
     * Computes the modular multiplicative inverse of a number.
     *
     * Returns the number y such that (x * y) % m = 1. If no such number exists
     * (when x and m are not coprime), returns null.
     *
     * @param string $x The number to find the inverse of
     * @param string $m The modulus, must be positive
     * @return string|null The modular multiplicative inverse, or null if it doesn't exist
     *
     * @pure
     */
    #[Override]
    public function modInverse(string $x, string $m): ?string
    {
        $result = gmp_invert($x, $m);

        if ($result === false) {
            return null;
        }

        return gmp_strval($result);
    }

    /**
     * Performs modular exponentiation.
     *
     * Efficiently computes (base^exp) % mod. This is significantly more efficient
     * than computing the power first and then taking the modulo, especially for
     * large exponents.
     *
     * @param string $base The base number, must be positive or zero
     * @param string $exp The exponent, must be positive or zero
     * @param string $mod The modulus, must be strictly positive
     * @return string The result of (base^exp) % mod
     *
     * @pure
     */
    #[Override]
    public function modPow(string $base, string $exp, string $mod): string
    {
        return gmp_strval(gmp_powm($base, $exp, $mod));
    }

    /**
     * Computes the greatest common divisor of two numbers.
     *
     * @param string $a The first number
     * @param string $b The second number
     * @return string The greatest common divisor, always positive or zero if both arguments are zero
     *
     * @pure
     */
    #[Override]
    public function gcd(string $a, string $b): string
    {
        return gmp_strval(gmp_gcd($a, $b));
    }

    /**
     * Converts a number from an arbitrary base to base 10.
     *
     * @param string $number The number to convert, containing only valid digits for the given base
     * @param int $base The base of the input number, must be between 2 and 36
     * @return string The number in base 10
     *
     * @pure
     */
    #[Override]
    public function fromBase(string $number, int $base): string
    {
        return gmp_strval(gmp_init($number, $base));
    }

    /**
     * Converts a base 10 number to an arbitrary base.
     *
     * @param string $number The base 10 number to convert
     * @param int $base The target base, must be between 2 and 36
     * @return string The number in the target base, lowercase
     *
     * @pure
     */
    #[Override]
    public function toBase(string $number, int $base): string
    {
        return gmp_strval($number, $base);
    }

    /**
     * Performs bitwise AND operation on two numbers.
     *
     * @param string $a The first operand
     * @param string $b The second operand
     * @return string The result of $a AND $b
     *
     * @pure
     */
    #[Override]
    public function and(string $a, string $b): string
    {
        return gmp_strval(gmp_and($a, $b));
    }

    /**
     * Performs bitwise OR operation on two numbers.
     *
     * @param string $a The first operand
     * @param string $b The second operand
     * @return string The result of $a OR $b
     *
     * @pure
     */
    #[Override]
    public function or(string $a, string $b): string
    {
        return gmp_strval(gmp_or($a, $b));
    }

    /**
     * Performs bitwise XOR operation on two numbers.
     *
     * @param string $a The first operand
     * @param string $b The second operand
     * @return string The result of $a XOR $b
     *
     * @pure
     */
    #[Override]
    public function xor(string $a, string $b): string
    {
        return gmp_strval(gmp_xor($a, $b));
    }

    /**
     * Computes the integer square root of a number.
     *
     * Returns the largest integer x such that x² ≤ n.
     *
     * @param string $n The number to compute the square root of, must not be negative
     * @return string The integer square root, rounded down
     *
     * @pure
     */
    #[Override]
    public function sqrt(string $n): string
    {
        return gmp_strval(gmp_sqrt($n));
    }
}
