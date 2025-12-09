<?php

declare(strict_types=1);

namespace Brick\Math\Internal\Calculator;

use Brick\Math\Internal\Calculator;
use Override;

use function assert;
use function in_array;
use function intdiv;
use function is_int;
use function ltrim;
use function str_pad;
use function str_repeat;
use function strcmp;
use function strlen;
use function substr;

use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * Pure PHP calculator implementation using no extensions.
 *
 * This fallback implementation provides arbitrary-precision arithmetic using only native
 * PHP code when neither GMP nor BCMath extensions are available. It processes large numbers
 * by breaking them into smaller chunks that fit within PHP's native integer limits.
 *
 * Performance characteristics:
 * - Significantly slower than GMP or BCMath for large numbers
 * - Optimized for 32-bit and 64-bit architectures
 * - Uses block-based processing to avoid integer overflow
 * - Automatically detects platform capabilities at construction
 *
 * @internal
 */
final readonly class NativeCalculator extends Calculator
{
    /**
     * The maximum number of digits the platform can process without overflow.
     *
     * For addition, subtraction, and division, this is the maximum number of digits
     * per operand. For multiplication, this represents the maximum sum of the lengths
     * of both operands to prevent overflow during intermediate calculations.
     *
     * An extra digit is reserved to hold carry values (maximum 1) without overflow.
     * 32-bit platforms: 9 digits (max value 1,999,999,999)
     * 64-bit platforms: 18 digits (max value 1,999,999,999,999,999,999)
     */
    private int $maxDigits;

    /**
     * Initializes the calculator by detecting platform capabilities.
     *
     * Automatically determines the maximum safe digit count based on PHP_INT_SIZE
     * to ensure calculations never overflow PHP's native integer limits.
     *
     * @pure
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->maxDigits = match (PHP_INT_SIZE) {
            4 => 9,
            8 => 18,
        };
    }

    /**
     * Adds two numbers using native PHP arithmetic with overflow handling.
     *
     * Attempts fast path using native PHP addition for small numbers. For larger
     * numbers that would overflow, falls back to block-based addition algorithm.
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
        /**
         * @var numeric-string $a
         * @var numeric-string $b
         */
        $result = $a + $b;

        if (is_int($result)) {
            return (string) $result;
        }

        if ($a === '0') {
            return $b;
        }

        if ($b === '0') {
            return $a;
        }

        [$aNeg, $bNeg, $aDig, $bDig] = $this->init($a, $b);

        $result = $aNeg === $bNeg ? $this->doAdd($aDig, $bDig) : $this->doSub($aDig, $bDig);

        if ($aNeg) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * Subtracts the second number from the first.
     *
     * Implemented as addition with the negation of the second operand to reuse
     * the optimized addition logic.
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
        return $this->add($a, $this->neg($b));
    }

    /**
     * Multiplies two numbers using block-based multiplication.
     *
     * Attempts fast path using native PHP multiplication for small numbers. For
     * larger numbers, breaks operands into blocks and uses grade-school multiplication
     * algorithm to prevent overflow.
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
        /**
         * @var numeric-string $a
         * @var numeric-string $b
         */
        $result = $a * $b;

        if (is_int($result)) {
            return (string) $result;
        }

        if ($a === '0' || $b === '0') {
            return '0';
        }

        if ($a === '1') {
            return $b;
        }

        if ($b === '1') {
            return $a;
        }

        if ($a === '-1') {
            return $this->neg($b);
        }

        if ($b === '-1') {
            return $this->neg($a);
        }

        [$aNeg, $bNeg, $aDig, $bDig] = $this->init($a, $b);

        $result = $this->doMul($aDig, $bDig);

        if ($aNeg !== $bNeg) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * Returns the quotient of division.
     *
     * Delegates to divQR() and returns only the quotient component.
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
        return $this->divQR($a, $b)[0];
    }

    /**
     * Returns the remainder of division.
     *
     * Delegates to divQR() and returns only the remainder component.
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
        return $this->divQR($a, $b)[1];
    }

    /**
     * Returns both quotient and remainder of division.
     *
     * Uses optimized native integer division for small numbers. For larger numbers,
     * implements long division algorithm with block-based processing to avoid overflow.
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
        if ($a === '0') {
            return ['0', '0'];
        }

        if ($a === $b) {
            return ['1', '0'];
        }

        if ($b === '1') {
            return [$a, '0'];
        }

        if ($b === '-1') {
            return [$this->neg($a), '0'];
        }

        /** @var numeric-string $a */
        $na = $a * 1; // cast to number

        if (is_int($na)) {
            /** @var numeric-string $b */
            $nb = $b * 1;

            if (is_int($nb)) {
                // the only division that may overflow is PHP_INT_MIN / -1,
                // which cannot happen here as we've already handled a divisor of -1 above.
                $q = intdiv($na, $nb);
                $r = $na % $nb;

                return [
                    (string) $q,
                    (string) $r,
                ];
            }
        }

        [$aNeg, $bNeg, $aDig, $bDig] = $this->init($a, $b);

        [$q, $r] = $this->doDiv($aDig, $bDig);

        if ($aNeg !== $bNeg) {
            $q = $this->neg($q);
        }

        if ($aNeg) {
            $r = $this->neg($r);
        }

        return [$q, $r];
    }

    /**
     * Raises a number to the specified power using exponentiation by squaring.
     *
     * Uses recursive binary exponentiation algorithm for efficiency. Time complexity
     * is O(log e) multiplications rather than O(e) for naive repeated multiplication.
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
        if ($e === 0) {
            return '1';
        }

        if ($e === 1) {
            return $a;
        }

        $odd = $e % 2;
        $e -= $odd;

        $aa = $this->mul($a, $a);

        $result = $this->pow($aa, $e / 2);

        if ($odd === 1) {
            $result = $this->mul($result, $a);
        }

        return $result;
    }

    /**
     * Performs modular exponentiation using iterative algorithm.
     *
     * Efficiently computes (base^exp) % mod without calculating the full power first.
     * Uses binary representation of exponent to minimize operations. Handles special
     * cases where mod is 1 to avoid algorithm edge cases.
     *
     * Algorithm adapted from: https://www.geeksforgeeks.org/modular-exponentiation-power-in-modular-arithmetic/
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
        // special case: the algorithm below fails with 0 power 0 mod 1 (returns 1 instead of 0)
        if ($base === '0' && $exp === '0' && $mod === '1') {
            return '0';
        }

        // special case: the algorithm below fails with power 0 mod 1 (returns 1 instead of 0)
        if ($exp === '0' && $mod === '1') {
            return '0';
        }

        $x = $base;

        $res = '1';

        // numbers are positive, so we can use remainder instead of modulo
        $x = $this->divR($x, $mod);

        while ($exp !== '0') {
            if (in_array($exp[-1], ['1', '3', '5', '7', '9'])) { // odd
                $res = $this->divR($this->mul($res, $x), $mod);
            }

            $exp = $this->divQ($exp, '2');
            $x = $this->divR($this->mul($x, $x), $mod);
        }

        return $res;
    }

    /**
     * Computes the integer square root using Newton's method.
     *
     * Returns the largest integer x such that x² ≤ n. Uses iterative Newton-Raphson
     * approximation for efficiency. Initial guess is based on the number of digits
     * to accelerate convergence.
     *
     * Algorithm adapted from: https://cp-algorithms.com/num_methods/roots_newton.html
     *
     * @param string $n The number to compute the square root of, must not be negative
     * @return string The integer square root, rounded down
     *
     * @pure
     */
    #[Override]
    public function sqrt(string $n): string
    {
        if ($n === '0') {
            return '0';
        }

        // initial approximation
        $x = str_repeat('9', intdiv(strlen($n), 2) ?: 1);

        $decreased = false;

        for (; ;) {
            $nx = $this->divQ($this->add($x, $this->divQ($n, $x)), '2');

            if ($x === $nx || $this->cmp($nx, $x) > 0 && $decreased) {
                break;
            }

            $decreased = $this->cmp($nx, $x) < 0;
            $x = $nx;
        }

        return $x;
    }

    /**
     * Performs block-based addition of two non-negative integers.
     *
     * Processes numbers in blocks of maxDigits size to prevent overflow. Handles
     * carry propagation across blocks and properly pads results to maintain alignment.
     *
     * @param string $a First operand, must be digits only (no sign)
     * @param string $b Second operand, must be digits only (no sign)
     * @return string The sum of the two numbers
     *
     * @pure
     */
    private function doAdd(string $a, string $b): string
    {
        [$a, $b, $length] = $this->pad($a, $b);

        $carry = 0;
        $result = '';

        for ($i = $length - $this->maxDigits; ; $i -= $this->maxDigits) {
            $blockLength = $this->maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                $i = 0;
            }

            /** @var numeric-string $blockA */
            $blockA = substr($a, $i, $blockLength);

            /** @var numeric-string $blockB */
            $blockB = substr($b, $i, $blockLength);

            $sum = (string) ($blockA + $blockB + $carry);
            $sumLength = strlen($sum);

            if ($sumLength > $blockLength) {
                $sum = substr($sum, 1);
                $carry = 1;
            } else {
                if ($sumLength < $blockLength) {
                    $sum = str_repeat('0', $blockLength - $sumLength) . $sum;
                }
                $carry = 0;
            }

            $result = $sum . $result;

            if ($i === 0) {
                break;
            }
        }

        if ($carry === 1) {
            $result = '1' . $result;
        }

        return $result;
    }

    /**
     * Performs block-based subtraction of two non-negative integers.
     *
     * Always ensures positive result by subtracting smaller from larger. Uses
     * complement arithmetic for borrow handling across blocks. Strips leading
     * zeros from result.
     *
     * @param string $a First operand, must be digits only (no sign)
     * @param string $b Second operand, must be digits only (no sign)
     * @return string The absolute difference between the two numbers
     *
     * @pure
     */
    private function doSub(string $a, string $b): string
    {
        if ($a === $b) {
            return '0';
        }

        // Ensure that we always subtract to a positive result: biggest minus smallest.
        $cmp = $this->doCmp($a, $b);

        $invert = ($cmp === -1);

        if ($invert) {
            $c = $a;
            $a = $b;
            $b = $c;
        }

        [$a, $b, $length] = $this->pad($a, $b);

        $carry = 0;
        $result = '';

        $complement = 10 ** $this->maxDigits;

        for ($i = $length - $this->maxDigits; ; $i -= $this->maxDigits) {
            $blockLength = $this->maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                $i = 0;
            }

            /** @var numeric-string $blockA */
            $blockA = substr($a, $i, $blockLength);

            /** @var numeric-string $blockB */
            $blockB = substr($b, $i, $blockLength);

            $sum = $blockA - $blockB - $carry;

            if ($sum < 0) {
                $sum += $complement;
                $carry = 1;
            } else {
                $carry = 0;
            }

            $sum = (string) $sum;
            $sumLength = strlen($sum);

            if ($sumLength < $blockLength) {
                $sum = str_repeat('0', $blockLength - $sumLength) . $sum;
            }

            $result = $sum . $result;

            if ($i === 0) {
                break;
            }
        }

        // Carry cannot be 1 when the loop ends, as a > b
        assert($carry === 0);

        $result = ltrim($result, '0');

        if ($invert) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * Performs block-based multiplication using grade-school algorithm.
     *
     * Breaks operands into blocks of half maxDigits to ensure products don't overflow.
     * Uses nested loops to multiply each block pair and accumulates partial results.
     * Similar to long multiplication taught in school, but in base 10^blockSize.
     *
     * @param string $a First factor, must be digits only (no sign)
     * @param string $b Second factor, must be digits only (no sign)
     * @return string The product of the two numbers
     *
     * @pure
     */
    private function doMul(string $a, string $b): string
    {
        $x = strlen($a);
        $y = strlen($b);

        $maxDigits = intdiv($this->maxDigits, 2);
        $complement = 10 ** $maxDigits;

        $result = '0';

        for ($i = $x - $maxDigits; ; $i -= $maxDigits) {
            $blockALength = $maxDigits;

            if ($i < 0) {
                $blockALength += $i;
                $i = 0;
            }

            $blockA = (int) substr($a, $i, $blockALength);

            $line = '';
            $carry = 0;

            for ($j = $y - $maxDigits; ; $j -= $maxDigits) {
                $blockBLength = $maxDigits;

                if ($j < 0) {
                    $blockBLength += $j;
                    $j = 0;
                }

                $blockB = (int) substr($b, $j, $blockBLength);

                $mul = $blockA * $blockB + $carry;
                $value = $mul % $complement;
                $carry = ($mul - $value) / $complement;

                $value = (string) $value;
                $value = str_pad($value, $maxDigits, '0', STR_PAD_LEFT);

                $line = $value . $line;

                if ($j === 0) {
                    break;
                }
            }

            if ($carry !== 0) {
                $line = $carry . $line;
            }

            $line = ltrim($line, '0');

            if ($line !== '') {
                $line .= str_repeat('0', $x - $blockALength - $i);
                $result = $this->add($result, $line);
            }

            if ($i === 0) {
                break;
            }
        }

        return $result;
    }

    /**
     * Performs long division of two non-negative integers.
     *
     * Implements optimized long division algorithm. For small remainders that fit
     * in native integers, uses fast integer division. Otherwise, uses focus-based
     * approach similar to manual long division by repeatedly subtracting divisor
     * multiples from dividend.
     *
     * @param string $a The dividend, must be digits only (no sign)
     * @param string $b The divisor, must be digits only (no sign), must not be zero
     * @return array{string, string} Array containing quotient and remainder
     *
     * @pure
     */
    private function doDiv(string $a, string $b): array
    {
        $cmp = $this->doCmp($a, $b);

        if ($cmp === -1) {
            return ['0', $a];
        }

        $x = strlen($a);
        $y = strlen($b);

        // we now know that a >= b && x >= y

        $q = '0'; // quotient
        $r = $a; // remainder
        $z = $y; // focus length, always $y or $y+1

        /** @var numeric-string $b */
        $nb = $b * 1; // cast to number
        // performance optimization in cases where the remainder will never cause int overflow
        if (is_int(($nb - 1) * 10 + 9)) {
            $r = (int) substr($a, 0, $z - 1);

            for ($i = $z - 1; $i < $x; $i++) {
                $n = $r * 10 + (int) $a[$i];
                /** @var int $nb */
                $q .= intdiv($n, $nb);
                $r = $n % $nb;
            }

            return [ltrim($q, '0') ?: '0', (string) $r];
        }

        for (; ;) {
            $focus = substr($a, 0, $z);

            $cmp = $this->doCmp($focus, $b);

            if ($cmp === -1) {
                if ($z === $x) { // remainder < dividend
                    break;
                }

                $z++;
            }

            $zeros = str_repeat('0', $x - $z);

            $q = $this->add($q, '1' . $zeros);
            $a = $this->sub($a, $b . $zeros);

            $r = $a;

            if ($r === '0') { // remainder == 0
                break;
            }

            $x = strlen($a);

            if ($x < $y) { // remainder < dividend
                break;
            }

            $z = $y;
        }

        return [$q, $r];
    }

    /**
     * Compares two non-negative integers.
     *
     * First compares by length, then by lexicographic string comparison for equal lengths.
     * This works because both numbers have no leading zeros.
     *
     * @param string $a First number, must be digits only (no sign)
     * @param string $b Second number, must be digits only (no sign)
     * @return -1|0|1 Returns -1 if $a < $b, 0 if equal, 1 if $a > $b
     *
     * @pure
     */
    private function doCmp(string $a, string $b): int
    {
        $x = strlen($a);
        $y = strlen($b);

        $cmp = $x <=> $y;

        if ($cmp !== 0) {
            return $cmp;
        }

        return strcmp($a, $b) <=> 0; // enforce -1|0|1
    }

    /**
     * Pads numbers with leading zeros to match lengths.
     *
     * Ensures both numbers have the same length by padding the shorter one with
     * leading zeros. This simplifies block-based arithmetic by allowing aligned
     * block processing. Input numbers must not have signs.
     *
     * @param string $a First number, must be digits only (no sign)
     * @param string $b Second number, must be digits only (no sign)
     * @return array{string, string, int} Padded $a, padded $b, and their common length
     *
     * @pure
     */
    private function pad(string $a, string $b): array
    {
        $x = strlen($a);
        $y = strlen($b);

        if ($x > $y) {
            $b = str_repeat('0', $x - $y) . $b;

            return [$a, $b, $x];
        }

        if ($x < $y) {
            $a = str_repeat('0', $y - $x) . $a;

            return [$a, $b, $y];
        }

        return [$a, $b, $x];
    }
}
