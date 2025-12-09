<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\AlphabetTooShortException;
use Brick\Math\Exception\BaseOutOfRangeException;
use Brick\Math\Exception\CharNotInAlphabetException;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\EmptyByteStringException;
use Brick\Math\Exception\EmptyNumberException;
use Brick\Math\Exception\EvenRootOfNegativeException;
use Brick\Math\Exception\ExponentOutOfRangeException;
use Brick\Math\Exception\IntegerOverflowException;
use Brick\Math\Exception\InvalidCharacterInBaseException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\MinGreaterThanMaxException;
use Brick\Math\Exception\MinimumRoundsException;
use Brick\Math\Exception\ModInverseNotFoundException;
use Brick\Math\Exception\ModulusMustNotBeZeroException;
use Brick\Math\Exception\NegativeArbitraryBaseException;
use Brick\Math\Exception\NegativeBitCountException;
use Brick\Math\Exception\NegativeBitIndexException;
use Brick\Math\Exception\NegativeByteConversionException;
use Brick\Math\Exception\NegativeModulusException;
use Brick\Math\Exception\NegativeNumberException;
use Brick\Math\Exception\NegativeOperandException;
use Brick\Math\Exception\NegativePrimeSearchException;
use Brick\Math\Exception\NegativeSquareRootException;
use Brick\Math\Exception\NonPositiveRootException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\UnserializeCalledException;
use Brick\Math\Internal\Calculator;
use Brick\Math\Internal\CalculatorRegistry;
use InvalidArgumentException;
use LogicException;
use Override;

use function assert;
use function bin2hex;
use function chr;
use function filter_var;
use function hex2bin;
use function in_array;
use function intdiv;
use function ltrim;
use function ord;
use function preg_match;
use function preg_quote;
use function random_bytes;
use function sprintf;
use function str_repeat;
use function strlen;
use function strtolower;
use function substr;

use const FILTER_VALIDATE_INT;

/**
 * An arbitrary-size integer.
 *
 * All methods accepting a number as a parameter accept either a BigInteger instance,
 * an integer, or a string representing an arbitrary size integer.
 */
final readonly class BigInteger extends BigNumber
{
    /**
     * The value, as a string of digits with optional leading minus sign.
     *
     * No leading zeros must be present.
     * No leading minus sign must be present if the number is zero.
     */
    private string $value;

    /**
     * Protected constructor. Use a factory method to obtain an instance.
     *
     * @param string $value A string of digits, with optional leading minus sign.
     *
     * @pure
     */
    protected function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Creates a number from a string in a given base.
     *
     * The string can optionally be prefixed with the `+` or `-` sign.
     *
     * Bases greater than 36 are not supported by this method, as there is no clear consensus on which of the lowercase
     * or uppercase characters should come first. Instead, this method accepts any base up to 36, and does not
     * differentiate lowercase and uppercase characters, which are considered equal.
     *
     * For bases greater than 36, and/or custom alphabets, use the fromArbitraryBase() method.
     *
     * @param string $number The number to convert, in the given base.
     * @param int    $base   The base of the number, between 2 and 36.
     *
     * @throws NumberFormatException    If the number is empty, or contains invalid chars for the given base.
     * @throws InvalidArgumentException If the base is out of range.
     *
     * @pure
     */
    public static function fromBase(string $number, int $base): BigInteger
    {
        if ($number === '') {
            throw EmptyNumberException::emptyString();
        }

        if ($base < 2 || $base > 36) {
            throw BaseOutOfRangeException::forBase($base, 2, 36);
        }

        if ($number[0] === '-') {
            $sign = '-';
            $number = substr($number, 1);
        } elseif ($number[0] === '+') {
            $sign = '';
            $number = substr($number, 1);
        } else {
            $sign = '';
        }

        if ($number === '') {
            throw EmptyNumberException::emptyString();
        }

        $number = ltrim($number, '0');

        if ($number === '') {
            // The result will be the same in any base, avoid further calculation.
            return BigInteger::zero();
        }

        if ($number === '1') {
            // The result will be the same in any base, avoid further calculation.
            return new BigInteger($sign . '1');
        }

        $pattern = '/[^' . substr(Calculator::ALPHABET, 0, $base) . ']/';

        if (preg_match($pattern, strtolower($number), $matches) === 1) {
            throw InvalidCharacterInBaseException::fromCharAndBase($matches[0], $base);
        }

        if ($base === 10) {
            // The number is usable as is, avoid further calculation.
            return new BigInteger($sign . $number);
        }

        $result = CalculatorRegistry::get()->fromBase($number, $base);

        return new BigInteger($sign . $result);
    }

    /**
     * Parses a string containing an integer in an arbitrary base, using a custom alphabet.
     *
     * Because this method accepts an alphabet with any character, including dash, it does not handle negative numbers.
     *
     * @param string $number   The number to parse.
     * @param string $alphabet The alphabet, for example '01' for base 2, or '01234567' for base 8.
     *
     * @throws NumberFormatException    If the given number is empty or contains invalid chars for the given alphabet.
     * @throws InvalidArgumentException If the alphabet does not contain at least 2 chars.
     *
     * @pure
     */
    public static function fromArbitraryBase(string $number, string $alphabet): BigInteger
    {
        if ($number === '') {
            throw EmptyNumberException::emptyString();
        }

        $base = strlen($alphabet);

        if ($base < 2) {
            throw AlphabetTooShortException::tooShort();
        }

        $pattern = '/[^' . preg_quote($alphabet, '/') . ']/';

        if (preg_match($pattern, $number, $matches) === 1) {
            throw CharNotInAlphabetException::fromChar($matches[0]);
        }

        $number = CalculatorRegistry::get()->fromArbitraryBase($number, $alphabet, $base);

        return new BigInteger($number);
    }

    /**
     * Translates a string of bytes containing the binary representation of a BigInteger into a BigInteger.
     *
     * The input string is assumed to be in big-endian byte-order: the most significant byte is in the zeroth element.
     *
     * If `$signed` is true, the input is assumed to be in two's-complement representation, and the leading bit is
     * interpreted as a sign bit. If `$signed` is false, the input is interpreted as an unsigned number, and the
     * resulting BigInteger will always be positive or zero.
     *
     * This method can be used to retrieve a number exported by `toBytes()`, as long as the `$signed` flags match.
     *
     * @param string $value  The byte string.
     * @param bool   $signed Whether to interpret as a signed number in two's-complement representation with a leading
     *                       sign bit.
     *
     * @throws NumberFormatException If the string is empty.
     *
     * @pure
     */
    public static function fromBytes(string $value, bool $signed = true): BigInteger
    {
        if ($value === '') {
            throw EmptyByteStringException::empty();
        }

        $twosComplement = false;

        if ($signed) {
            $x = ord($value[0]);

            if (($twosComplement = ($x >= 0x80))) {
                $value = ~$value;
            }
        }

        $number = self::fromBase(bin2hex($value), 16);

        if ($twosComplement) {
            return $number->plus(1)->negated();
        }

        return $number;
    }

    /**
     * Generates a pseudo-random number in the range 0 to 2^numBits - 1.
     *
     * Using the default random bytes generator, this method is suitable for cryptographic use.
     *
     * @param int                          $numBits              The number of bits.
     * @param (callable(int): string)|null $randomBytesGenerator A function that accepts a number of bytes, and returns
     *                                                           a string of random bytes of the given length. Defaults
     *                                                           to the `random_bytes()` function.
     *
     * @throws InvalidArgumentException If $numBits is negative.
     */
    public static function randomBits(int $numBits, ?callable $randomBytesGenerator = null): BigInteger
    {
        if ($numBits < 0) {
            throw NegativeBitCountException::negative();
        }

        if ($numBits === 0) {
            return BigInteger::zero();
        }

        if ($randomBytesGenerator === null) {
            $randomBytesGenerator = random_bytes(...);
        }

        /** @var int<1, max> $byteLength */
        $byteLength = intdiv($numBits - 1, 8) + 1;

        $extraBits = ($byteLength * 8 - $numBits);
        $bitmask = chr(0xFF >> $extraBits);

        $randomBytes = $randomBytesGenerator($byteLength);
        $randomBytes[0] = $randomBytes[0] & $bitmask;

        return self::fromBytes($randomBytes, false);
    }

    /**
     * Generates a pseudo-random number between `$min` and `$max`.
     *
     * Using the default random bytes generator, this method is suitable for cryptographic use.
     *
     * @param BigNumber|int|float|string   $min                  The lower bound. Must be convertible to a BigInteger.
     * @param BigNumber|int|float|string   $max                  The upper bound. Must be convertible to a BigInteger.
     * @param (callable(int): string)|null $randomBytesGenerator A function that accepts a number of bytes, and returns
     *                                                           a string of random bytes of the given length. Defaults
     *                                                           to the `random_bytes()` function.
     *
     * @throws MathException If one of the parameters cannot be converted to a BigInteger,
     *                       or `$min` is greater than `$max`.
     */
    public static function randomRange(
        BigNumber|int|float|string $min,
        BigNumber|int|float|string $max,
        ?callable $randomBytesGenerator = null,
    ): BigInteger {
        $min = BigInteger::of($min);
        $max = BigInteger::of($max);

        if ($min->isGreaterThan($max)) {
            throw MinGreaterThanMaxException::inRandomRange();
        }

        if ($min->isEqualTo($max)) {
            return $min;
        }

        $diff = $max->minus($min);
        $bitLength = $diff->getBitLength();

        // try until the number is in range (50% to 100% chance of success)
        do {
            $randomNumber = self::randomBits($bitLength, $randomBytesGenerator);
        } while ($randomNumber->isGreaterThan($diff));

        return $randomNumber->plus($min);
    }

    /**
     * Generates a random probable prime BigInteger with the specified bit length.
     *
     * Using the default random bytes generator, this method is suitable for cryptographic use.
     *
     * @param int                          $numBits              The bit length of the prime to generate (must be >= 2).
     * @param int                          $certainty            The number of Miller-Rabin rounds. Defaults to 25,
     *                                                           which gives a false positive probability < 10^(-15).
     * @param (callable(int): string)|null $randomBytesGenerator A function that accepts a number of bytes, and returns
     *                                                           a string of random bytes of the given length. Defaults
     *                                                           to the `random_bytes()` function.
     *
     * @throws InvalidArgumentException If $numBits is less than 2 or $certainty is less than 1.
     */
    public static function randomPrime(int $numBits, int $certainty = 25, ?callable $randomBytesGenerator = null): BigInteger
    {
        if ($numBits < 2) {
            throw new InvalidArgumentException('The number of bits must be at least 2.');
        }

        if ($certainty < 1) {
            throw MinimumRoundsException::atLeastOne();
        }

        // Special case: 2 bits can only produce 2 or 3
        if ($numBits === 2) {
            $random = self::randomBits(1, $randomBytesGenerator);

            return $random->isZero() ? BigInteger::of(2) : BigInteger::of(3);
        }

        // Generate random odd numbers with the highest bit set until we find a prime
        for (; ;) {
            // Generate a random number with exactly numBits bits (high bit set)
            $candidate = self::randomBits($numBits, $randomBytesGenerator);

            // Set the high bit to ensure we have exactly numBits bits
            $candidate = $candidate->withBitSet($numBits - 1);

            // Set the low bit to make it odd
            $candidate = $candidate->withBitSet(0);

            // Test for primality
            if ($candidate->isPrime($certainty)) {
                return $candidate;
            }
        }
    }

    /**
     * Returns a BigInteger representing zero.
     *
     * @pure
     */
    public static function zero(): BigInteger
    {
        /** @var BigInteger|null $zero */
        static $zero;

        if ($zero === null) {
            $zero = new BigInteger('0');
        }

        return $zero;
    }

    /**
     * Returns a BigInteger representing one.
     *
     * @pure
     */
    public static function one(): BigInteger
    {
        /** @var BigInteger|null $one */
        static $one;

        if ($one === null) {
            $one = new BigInteger('1');
        }

        return $one;
    }

    /**
     * Returns a BigInteger representing ten.
     *
     * @pure
     */
    public static function ten(): BigInteger
    {
        /** @var BigInteger|null $ten */
        static $ten;

        if ($ten === null) {
            $ten = new BigInteger('10');
        }

        return $ten;
    }

    /**
     * @pure
     */
    public static function gcdMultiple(BigInteger $a, BigInteger ...$n): BigInteger
    {
        $result = $a;

        foreach ($n as $next) {
            $result = $result->gcd($next);

            if ($result->isEqualTo(1)) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * @param BigNumber|int|float|string $that The number to add. Must be convertible to a BigInteger.
     *
     * @throws MathException If the number is not valid, or is not convertible to a BigInteger.
     *
     * @pure
     */
    public function plus(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            return $this;
        }

        if ($this->value === '0') {
            return $that;
        }

        $value = CalculatorRegistry::get()->add($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * @param BigNumber|int|float|string $that The number to subtract. Must be convertible to a BigInteger.
     *
     * @throws MathException If the number is not valid, or is not convertible to a BigInteger.
     *
     * @pure
     */
    public function minus(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            return $this;
        }

        $value = CalculatorRegistry::get()->sub($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the product of this number and the given one.
     *
     * @param BigNumber|int|float|string $that The multiplier. Must be convertible to a BigInteger.
     *
     * @throws MathException If the multiplier is not a valid number, or is not convertible to a BigInteger.
     *
     * @pure
     */
    public function multipliedBy(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '1') {
            return $this;
        }

        if ($this->value === '1') {
            return $that;
        }

        $value = CalculatorRegistry::get()->mul($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the result of the division of this number by the given one.
     *
     * @param BigNumber|int|float|string $that         The divisor. Must be convertible to a BigInteger.
     * @param RoundingMode               $roundingMode An optional rounding mode, defaults to UNNECESSARY.
     *
     * @throws MathException If the divisor is not a valid number, is not convertible to a BigInteger, is zero,
     *                       or RoundingMode::UNNECESSARY is used and the remainder is not zero.
     *
     * @pure
     */
    public function dividedBy(BigNumber|int|float|string $that, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '1') {
            return $this;
        }

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        $result = CalculatorRegistry::get()->divRound($this->value, $that->value, $roundingMode);

        return new BigInteger($result);
    }

    /**
     * Limits (clamps) this number between the given minimum and maximum values.
     *
     * If the number is lower than $min, returns a copy of $min.
     * If the number is greater than $max, returns a copy of $max.
     * Otherwise, returns this number unchanged.
     *
     * @param BigNumber|int|float|string $min The minimum. Must be convertible to a BigInteger.
     * @param BigNumber|int|float|string $max The maximum. Must be convertible to a BigInteger.
     *
     * @throws MathException If min/max are not convertible to a BigInteger.
     */
    public function clamp(BigNumber|int|float|string $min, BigNumber|int|float|string $max): BigInteger
    {
        if ($this->isLessThan($min)) {
            return BigInteger::of($min);
        } elseif ($this->isGreaterThan($max)) {
            return BigInteger::of($max);
        }

        return $this;
    }

    /**
     * Returns this number exponentiated to the given value.
     *
     * @throws InvalidArgumentException If the exponent is not in the range 0 to 1,000,000.
     *
     * @pure
     */
    public function power(int $exponent): BigInteger
    {
        if ($exponent === 0) {
            return BigInteger::one();
        }

        if ($exponent === 1) {
            return $this;
        }

        if ($exponent < 0 || $exponent > Calculator::MAX_POWER) {
            throw ExponentOutOfRangeException::forExponent($exponent, Calculator::MAX_POWER);
        }

        return new BigInteger(CalculatorRegistry::get()->pow($this->value, $exponent));
    }

    /**
     * Returns the factorial of this number (n!).
     *
     * The factorial is defined as n! = n × (n-1) × (n-2) × ... × 2 × 1.
     * By convention, 0! = 1.
     *
     * @throws NegativeOperandException If this number is negative.
     *
     * @pure
     */
    public function factorial(): BigInteger
    {
        if ($this->isNegative()) {
            throw NegativeOperandException::factorialOfNegative();
        }

        if ($this->isZero() || $this->isEqualTo(1)) {
            return BigInteger::one();
        }

        // Use iterative multiplication for better performance
        $result = BigInteger::one();
        $n = $this->toInt();

        // For very large n, check if it fits in int first
        if ($this->isGreaterThan(PHP_INT_MAX)) {
            // Fall back to BigInteger iteration for huge numbers
            $current = BigInteger::of(2);
            while ($current->isLessThanOrEqualTo($this)) {
                $result = $result->multipliedBy($current);
                $current = $current->plus(1);
            }

            return $result;
        }

        for ($i = 2; $i <= $n; $i++) {
            $result = $result->multipliedBy($i);
        }

        return $result;
    }

    /**
     * Returns the binomial coefficient "n choose k" (C(n,k)).
     *
     * The binomial coefficient is defined as n! / (k! × (n-k)!).
     * Returns 0 if k > n or k < 0.
     *
     * @param int $k The number of elements to choose.
     *
     * @throws NegativeOperandException If this number (n) is negative.
     *
     * @pure
     */
    public function binomial(int $k): BigInteger
    {
        if ($this->isNegative()) {
            throw NegativeOperandException::binomialOfNegative();
        }

        if ($k < 0) {
            return BigInteger::zero();
        }

        // Convert to int for comparison, handle BigInteger case
        if ($this->isGreaterThan(PHP_INT_MAX)) {
            // For very large n, use BigInteger arithmetic
            $n = $this;
            $kBig = BigInteger::of($k);

            if ($kBig->isGreaterThan($n)) {
                return BigInteger::zero();
            }

            // Use symmetry: C(n,k) = C(n, n-k), choose smaller k
            $nMinusK = $n->minus($kBig);
            if ($kBig->isGreaterThan($nMinusK)) {
                $kBig = $nMinusK;
                $k = $kBig->toInt();
            }

            if ($k === 0) {
                return BigInteger::one();
            }

            // Calculate using multiplicative formula: n × (n-1) × ... × (n-k+1) / k!
            $result = BigInteger::one();
            for ($i = 0; $i < $k; $i++) {
                $result = $result->multipliedBy($n->minus($i));
                $result = $result->quotient($i + 1);
            }

            return $result;
        }

        $n = $this->toInt();

        if ($k > $n) {
            return BigInteger::zero();
        }

        // Use symmetry: C(n,k) = C(n, n-k), choose smaller k
        if ($k > $n - $k) {
            $k = $n - $k;
        }

        if ($k === 0) {
            return BigInteger::one();
        }

        // Calculate using multiplicative formula: n × (n-1) × ... × (n-k+1) / k!
        $result = BigInteger::one();
        for ($i = 0; $i < $k; $i++) {
            $result = $result->multipliedBy($n - $i);
            $result = $result->quotient($i + 1);
        }

        return $result;
    }

    /**
     * Returns the number of permutations P(n, k) = n! / (n-k)!
     *
     * This is the number of ways to arrange k items from n distinct items.
     * Returns 0 if k > n or k < 0.
     *
     * @param int $k The number of elements to arrange.
     *
     * @throws NegativeOperandException If this number (n) is negative.
     *
     * @pure
     */
    public function permutations(int $k): BigInteger
    {
        if ($this->isNegative()) {
            throw NegativeOperandException::permutationsOfNegative();
        }

        if ($k < 0) {
            return BigInteger::zero();
        }

        if ($k === 0) {
            return BigInteger::one();
        }

        // For very large n, use BigInteger arithmetic
        if ($this->isGreaterThan(PHP_INT_MAX)) {
            $n = $this;
            $kBig = BigInteger::of($k);

            if ($kBig->isGreaterThan($n)) {
                return BigInteger::zero();
            }

            // P(n,k) = n × (n-1) × ... × (n-k+1)
            $result = BigInteger::one();
            for ($i = 0; $i < $k; $i++) {
                $result = $result->multipliedBy($n->minus($i));
            }

            return $result;
        }

        $n = $this->toInt();

        if ($k > $n) {
            return BigInteger::zero();
        }

        // P(n,k) = n × (n-1) × ... × (n-k+1)
        $result = BigInteger::one();
        for ($i = 0; $i < $k; $i++) {
            $result = $result->multipliedBy($n - $i);
        }

        return $result;
    }

    /**
     * Returns the double factorial of this number (n!!).
     *
     * The double factorial is the product of all integers from 1 to n that have the same parity as n.
     * - For odd n: n!! = n × (n-2) × (n-4) × ... × 3 × 1
     * - For even n: n!! = n × (n-2) × (n-4) × ... × 4 × 2
     * - By convention, 0!! = 1 and (-1)!! = 1
     *
     * @throws NegativeOperandException If this number is negative (except -1).
     *
     * @pure
     */
    public function doubleFactorial(): BigInteger
    {
        // Special case: (-1)!! = 1 by convention
        if ($this->isEqualTo(-1)) {
            return BigInteger::one();
        }

        if ($this->isNegative()) {
            throw NegativeOperandException::doubleFactorialOfNegative();
        }

        if ($this->isZero() || $this->isEqualTo(1)) {
            return BigInteger::one();
        }

        // For very large n, use BigInteger arithmetic
        if ($this->isGreaterThan(PHP_INT_MAX)) {
            $result = BigInteger::one();
            $current = $this;
            while ($current->isGreaterThan(1)) {
                $result = $result->multipliedBy($current);
                $current = $current->minus(2);
            }

            return $result;
        }

        $n = $this->toInt();
        $result = BigInteger::one();

        for ($i = $n; $i > 1; $i -= 2) {
            $result = $result->multipliedBy($i);
        }

        return $result;
    }

    /**
     * Returns the quotient of the division of this number by the given one.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @throws DivisionByZeroException If the divisor is zero.
     *
     * @pure
     */
    public function quotient(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '1') {
            return $this;
        }

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        $quotient = CalculatorRegistry::get()->divQ($this->value, $that->value);

        return new BigInteger($quotient);
    }

    /**
     * Returns the remainder of the division of this number by the given one.
     *
     * The remainder, when non-zero, has the same sign as the dividend.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @throws DivisionByZeroException If the divisor is zero.
     *
     * @pure
     */
    public function remainder(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '1') {
            return BigInteger::zero();
        }

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        $remainder = CalculatorRegistry::get()->divR($this->value, $that->value);

        return new BigInteger($remainder);
    }

    /**
     * Returns the quotient and remainder of the division of this number by the given one.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return array{BigInteger, BigInteger} An array containing the quotient and the remainder.
     *
     * @throws DivisionByZeroException If the divisor is zero.
     *
     * @pure
     */
    public function quotientAndRemainder(BigNumber|int|float|string $that): array
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        [$quotient, $remainder] = CalculatorRegistry::get()->divQR($this->value, $that->value);

        return [
            new BigInteger($quotient),
            new BigInteger($remainder),
        ];
    }

    /**
     * Returns the modulo of this number and the given one.
     *
     * The modulo operation yields the same result as the remainder operation when both operands are of the same sign,
     * and may differ when signs are different.
     *
     * The result of the modulo operation, when non-zero, has the same sign as the divisor.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @throws DivisionByZeroException If the divisor is zero.
     *
     * @pure
     */
    public function mod(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            throw ModulusMustNotBeZeroException::zero();
        }

        $value = CalculatorRegistry::get()->mod($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the modular multiplicative inverse of this BigInteger modulo $m.
     *
     * @throws DivisionByZeroException If $m is zero.
     * @throws NegativeNumberException If $m is negative.
     * @throws MathException           If this BigInteger has no multiplicative inverse mod m (that is, this BigInteger
     *                                 is not relatively prime to m).
     *
     * @pure
     */
    public function modInverse(BigInteger $m): BigInteger
    {
        if ($m->value === '0') {
            throw ModulusMustNotBeZeroException::zero();
        }

        if ($m->isNegative()) {
            throw NegativeModulusException::negative();
        }

        if ($m->value === '1') {
            return BigInteger::zero();
        }

        $value = CalculatorRegistry::get()->modInverse($this->value, $m->value);

        if ($value === null) {
            throw ModInverseNotFoundException::notFound();
        }

        return new BigInteger($value);
    }

    /**
     * Returns this number raised into power with modulo.
     *
     * This operation only works on positive numbers.
     *
     * @param BigNumber|int|float|string $exp The exponent. Must be positive or zero.
     * @param BigNumber|int|float|string $mod The modulus. Must be strictly positive.
     *
     * @throws NegativeNumberException If any of the operands is negative.
     * @throws DivisionByZeroException If the modulus is zero.
     *
     * @pure
     */
    public function modPow(BigNumber|int|float|string $exp, BigNumber|int|float|string $mod): BigInteger
    {
        $exp = BigInteger::of($exp);
        $mod = BigInteger::of($mod);

        if ($this->isNegative() || $exp->isNegative() || $mod->isNegative()) {
            throw NegativeOperandException::inModPow();
        }

        if ($mod->isZero()) {
            throw ModulusMustNotBeZeroException::zero();
        }

        $result = CalculatorRegistry::get()->modPow($this->value, $exp->value, $mod->value);

        return new BigInteger($result);
    }

    /**
     * Returns the greatest common divisor of this number and the given one.
     *
     * The GCD is always positive, unless both operands are zero, in which case it is zero.
     *
     * @param BigNumber|int|float|string $that The operand. Must be convertible to an integer number.
     *
     * @pure
     */
    public function gcd(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '0' && $this->value[0] !== '-') {
            return $this;
        }

        if ($this->value === '0' && $that->value[0] !== '-') {
            return $that;
        }

        $value = CalculatorRegistry::get()->gcd($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the least common multiple of this number and the given one.
     *
     * The LCM is always positive, unless either operand is zero, in which case it is zero.
     *
     * @param BigNumber|int|float|string $that The operand. Must be convertible to an integer number.
     *
     * @pure
     */
    public function lcm(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        if ($this->value === '0' || $that->value === '0') {
            return BigInteger::zero();
        }

        // LCM(a, b) = |a * b| / GCD(a, b)
        $product = $this->abs()->multipliedBy($that->abs());
        $gcd = $this->gcd($that);

        return $product->quotient($gcd);
    }

    /**
     * Returns the integer square root number of this number, rounded down.
     *
     * The result is the largest x such that x² ≤ n.
     *
     * @throws NegativeNumberException If this number is negative.
     *
     * @pure
     */
    public function sqrt(): BigInteger
    {
        if ($this->value[0] === '-') {
            throw NegativeSquareRootException::negative();
        }

        $value = CalculatorRegistry::get()->sqrt($this->value);

        return new BigInteger($value);
    }

    /**
     * Returns the integer nth root of this number, rounded down.
     *
     * The result is the largest x such that x^n ≤ this.
     *
     * @param int $n The root to calculate (must be positive).
     *
     * @throws InvalidArgumentException If the root is not positive.
     * @throws NegativeNumberException  If this number is negative and the root is even.
     *
     * @pure
     */
    public function nthRoot(int $n): BigInteger
    {
        if ($n < 1) {
            throw NonPositiveRootException::notPositive();
        }

        if ($n === 1) {
            return $this;
        }

        if ($n === 2) {
            return $this->sqrt();
        }

        $isNegative = $this->value[0] === '-';

        if ($isNegative && $n % 2 === 0) {
            throw EvenRootOfNegativeException::negative();
        }

        $value = $isNegative ? substr($this->value, 1) : $this->value;

        if ($value === '0' || $value === '1') {
            return $this;
        }

        // Newton's method: x = ((n-1) * x + a / x^(n-1)) / n
        $nMinus1 = $n - 1;
        $nBig = new BigInteger((string) $n);
        $nMinus1Big = new BigInteger((string) $nMinus1);
        $a = new BigInteger($value);

        // Initial guess: 2^(ceil(bitLength / n))
        $bitLength = $a->getBitLength();
        $x = BigInteger::of(2)->power((int) ceil($bitLength / $n));

        $decreased = false;

        for (; ;) {
            // x^(n-1)
            $xPowNMinus1 = $x->power($nMinus1);
            // (n-1) * x + a / x^(n-1)
            $nx = $nMinus1Big->multipliedBy($x)->plus($a->quotient($xPowNMinus1))->quotient($nBig);

            if ($x->isEqualTo($nx) || ($nx->isGreaterThan($x) && $decreased)) {
                break;
            }

            $decreased = $nx->isLessThan($x);
            $x = $nx;
        }

        return $isNegative ? $x->negated() : $x;
    }

    /**
     * Checks if this number is probably prime.
     *
     * This method uses the Miller-Rabin primality test with a number of rounds
     * appropriate for the bit length of the number. For numbers less than 3,317,044,064,679,887,385,961,981,
     * the test is deterministic. For larger numbers, the probability of a false positive
     * is less than 4^(-$rounds).
     *
     * @param int $rounds The number of Miller-Rabin rounds to perform for large numbers.
     *                    Defaults to 25, which gives a false positive probability < 10^(-15).
     *
     * @throws InvalidArgumentException If rounds is less than 1.
     */
    public function isPrime(int $rounds = 25): bool
    {
        if ($rounds < 1) {
            throw MinimumRoundsException::atLeastOne();
        }

        // Negative numbers, 0, and 1 are not prime
        if ($this->isNegativeOrZero() || $this->isOne()) {
            return false;
        }

        // 2 and 3 are prime
        if ($this->isEqualTo(2) || $this->isEqualTo(3)) {
            return true;
        }

        // Even numbers > 2 are not prime
        if ($this->isEven()) {
            return false;
        }

        // Check small primes first for efficiency
        $smallPrimes = [3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83, 89, 97];
        foreach ($smallPrimes as $prime) {
            if ($this->isEqualTo($prime)) {
                return true;
            }
            if ($this->remainder($prime)->isZero()) {
                return false;
            }
        }

        // Miller-Rabin primality test
        // n - 1 = 2^s * d where d is odd
        $nMinusOne = $this->minus(1);
        $d = $nMinusOne;
        $s = 0;

        while ($d->isEven()) {
            $d = $d->quotient(2);
            $s++;
        }

        // Deterministic witnesses for numbers up to specific bounds
        $bitLength = $this->getBitLength();
        if ($bitLength <= 64) {
            // For numbers < 3,317,044,064,679,887,385,961,981, these witnesses are deterministic
            $witnesses = [2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37];
        } else {
            // For larger numbers, use random witnesses
            $witnesses = [];
            for ($i = 0; $i < $rounds; $i++) {
                $witnesses[] = BigInteger::randomRange(2, $nMinusOne->minus(1));
            }
        }

        foreach ($witnesses as $a) {
            $a = $a instanceof BigInteger ? $a : BigInteger::of($a);

            // Skip if witness >= n - 1
            if ($a->isGreaterThanOrEqualTo($nMinusOne)) {
                continue;
            }

            $x = $a->modPow($d, $this);

            if ($x->isOne() || $x->isEqualTo($nMinusOne)) {
                continue;
            }

            $composite = true;
            for ($r = 1; $r < $s; $r++) {
                $x = $x->modPow(2, $this);
                if ($x->isEqualTo($nMinusOne)) {
                    $composite = false;
                    break;
                }
                if ($x->isOne()) {
                    return false;
                }
            }

            if ($composite) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the next probable prime greater than this number.
     *
     * @param int $rounds The number of Miller-Rabin rounds for primality testing.
     *
     * @throws NegativeNumberException If this number is negative.
     */
    public function nextPrime(int $rounds = 25): BigInteger
    {
        if ($this->isNegative()) {
            throw NegativePrimeSearchException::negative();
        }

        // Start from this + 1, or 2 if this < 2
        $candidate = $this->isLessThan(2) ? BigInteger::of(2) : $this->plus(1);

        // Make sure candidate is odd (except for 2)
        if ($candidate->isGreaterThan(2) && $candidate->isEven()) {
            $candidate = $candidate->plus(1);
        }

        while (! $candidate->isPrime($rounds)) {
            $candidate = $candidate->isEqualTo(2) ? BigInteger::of(3) : $candidate->plus(2);
        }

        return $candidate;
    }

    /**
     * Computes the Jacobi symbol (this/n).
     *
     * The Jacobi symbol is a generalization of the Legendre symbol to all odd integers n > 0.
     * Returns:
     *   0 if gcd(this, n) > 1
     *   1 or -1 otherwise
     *
     * @param BigNumber|int|float|string $n Must be a positive odd integer.
     *
     * @throws InvalidArgumentException If n is not a positive odd integer.
     */
    public function jacobi(BigNumber|int|float|string $n): int
    {
        $n = BigInteger::of($n);

        if ($n->isNegativeOrZero() || $n->isEven()) {
            throw new InvalidArgumentException('The Jacobi symbol is only defined for positive odd integers.');
        }

        // Handle special case where n = 1
        if ($n->isOne()) {
            return 1;
        }

        // Reduce a mod n to be in range [0, n-1]
        $a = $this->mod($n);

        $result = 1;

        while (! $a->isZero()) {
            // Remove factors of 2 from a
            while ($a->isEven()) {
                $a = $a->quotient(2);

                // (2/n) = (-1)^((n²-1)/8) = 1 if n ≡ ±1 (mod 8), -1 if n ≡ ±3 (mod 8)
                $nMod8 = $n->mod(8)->toInt();
                if ($nMod8 === 3 || $nMod8 === 5) {
                    $result = -$result;
                }
            }

            // Swap a and n (quadratic reciprocity)
            [$a, $n] = [$n, $a];

            // (a/n)(n/a) = (-1)^((a-1)/2 * (n-1)/2)
            // Both are odd at this point
            $aMod4 = $a->mod(4)->toInt();
            $nMod4 = $n->mod(4)->toInt();
            if ($aMod4 === 3 && $nMod4 === 3) {
                $result = -$result;
            }

            $a = $a->mod($n);
        }

        return $n->isOne() ? $result : 0;
    }

    /**
     * Computes the Legendre symbol (this/p).
     *
     * The Legendre symbol is defined for odd prime p:
     *   0 if this ≡ 0 (mod p)
     *   1 if this is a quadratic residue mod p
     *  -1 if this is a quadratic non-residue mod p
     *
     * Note: This method does NOT verify that p is actually prime.
     * If p is not prime, the result is the Jacobi symbol instead.
     *
     * @param BigNumber|int|float|string $p Must be an odd prime.
     *
     * @throws InvalidArgumentException If p is not a positive odd integer.
     */
    public function legendre(BigNumber|int|float|string $p): int
    {
        // The Legendre symbol is a special case of the Jacobi symbol when p is prime
        return $this->jacobi($p);
    }

    /**
     * Returns the absolute value of this number.
     *
     * @pure
     */
    public function abs(): BigInteger
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Returns the inverse of this number.
     *
     * @pure
     */
    public function negated(): BigInteger
    {
        return new BigInteger(CalculatorRegistry::get()->neg($this->value));
    }

    /**
     * Returns the integer bitwise-and combined with another integer.
     *
     * This method returns a negative BigInteger if and only if both operands are negative.
     *
     * @param BigNumber|int|float|string $that The operand. Must be convertible to an integer number.
     *
     * @pure
     */
    public function and(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        return new BigInteger(CalculatorRegistry::get()->and($this->value, $that->value));
    }

    /**
     * Returns the integer bitwise-or combined with another integer.
     *
     * This method returns a negative BigInteger if and only if either of the operands is negative.
     *
     * @param BigNumber|int|float|string $that The operand. Must be convertible to an integer number.
     *
     * @pure
     */
    public function or(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        return new BigInteger(CalculatorRegistry::get()->or($this->value, $that->value));
    }

    /**
     * Returns the integer bitwise-xor combined with another integer.
     *
     * This method returns a negative BigInteger if and only if exactly one of the operands is negative.
     *
     * @param BigNumber|int|float|string $that The operand. Must be convertible to an integer number.
     *
     * @pure
     */
    public function xor(BigNumber|int|float|string $that): BigInteger
    {
        $that = BigInteger::of($that);

        return new BigInteger(CalculatorRegistry::get()->xor($this->value, $that->value));
    }

    /**
     * Returns the bitwise-not of this BigInteger.
     *
     * @pure
     */
    public function not(): BigInteger
    {
        return $this->negated()->minus(1);
    }

    /**
     * Returns the integer left shifted by a given number of bits.
     *
     * @pure
     */
    public function shiftedLeft(int $distance): BigInteger
    {
        if ($distance === 0) {
            return $this;
        }

        if ($distance < 0) {
            return $this->shiftedRight(-$distance);
        }

        return $this->multipliedBy(BigInteger::of(2)->power($distance));
    }

    /**
     * Returns the integer right shifted by a given number of bits.
     *
     * @pure
     */
    public function shiftedRight(int $distance): BigInteger
    {
        if ($distance === 0) {
            return $this;
        }

        if ($distance < 0) {
            return $this->shiftedLeft(-$distance);
        }

        $operand = BigInteger::of(2)->power($distance);

        if ($this->isPositiveOrZero()) {
            return $this->quotient($operand);
        }

        return $this->dividedBy($operand, RoundingMode::UP);
    }

    /**
     * Returns the number of bits in the minimal two's-complement representation of this BigInteger, excluding a sign bit.
     *
     * For positive BigIntegers, this is equivalent to the number of bits in the ordinary binary representation.
     * Computes (ceil(log2(this < 0 ? -this : this+1))).
     *
     * @pure
     */
    public function getBitLength(): int
    {
        if ($this->value === '0') {
            return 0;
        }

        if ($this->isNegative()) {
            return $this->abs()->minus(1)->getBitLength();
        }

        return strlen($this->toBase(2));
    }

    /**
     * Returns the index of the rightmost (lowest-order) one bit in this BigInteger.
     *
     * Returns -1 if this BigInteger contains no one bits.
     *
     * @pure
     */
    public function getLowestSetBit(): int
    {
        $n = $this;
        $bitLength = $this->getBitLength();

        for ($i = 0; $i <= $bitLength; $i++) {
            if ($n->isOdd()) {
                return $i;
            }

            $n = $n->shiftedRight(1);
        }

        return -1;
    }

    /**
     * Returns whether this number is even.
     *
     * @pure
     */
    public function isEven(): bool
    {
        return in_array($this->value[-1], ['0', '2', '4', '6', '8'], true);
    }

    /**
     * Returns whether this number is odd.
     *
     * @pure
     */
    public function isOdd(): bool
    {
        return in_array($this->value[-1], ['1', '3', '5', '7', '9'], true);
    }

    /**
     * Returns true if and only if the designated bit is set.
     *
     * Computes ((this & (1<<n)) != 0).
     *
     * @param int $n The bit to test, 0-based.
     *
     * @throws InvalidArgumentException If the bit to test is negative.
     *
     * @pure
     */
    public function testBit(int $n): bool
    {
        if ($n < 0) {
            throw NegativeBitIndexException::negative();
        }

        return $this->shiftedRight($n)->isOdd();
    }

    /**
     * Returns the number of bits set to 1 in the two's complement representation.
     *
     * For positive numbers, this is the number of 1 bits in the binary representation.
     * For negative numbers, this returns the number of 1 bits in the two's complement form,
     * which is effectively infinite for a true two's complement representation.
     * To handle this, we return the bit count of the absolute value for negative numbers
     * as a practical measure.
     *
     * @pure
     */
    public function getBitCount(): int
    {
        if ($this->isZero()) {
            return 0;
        }

        // For negative numbers, we'll count the bits in the absolute value
        // (A true two's complement representation would have infinite 1s)
        $binary = $this->abs()->toBase(2);

        return substr_count($binary, '1');
    }

    /**
     * Returns a BigInteger whose value is equivalent to this BigInteger with the designated bit set.
     *
     * Computes (this | (1<<n)).
     *
     * @param int $n The bit to set, 0-based.
     *
     * @throws InvalidArgumentException If the bit index is negative.
     *
     * @pure
     */
    public function withBitSet(int $n): BigInteger
    {
        if ($n < 0) {
            throw NegativeBitIndexException::negative();
        }

        if ($this->testBit($n)) {
            return $this;
        }

        $mask = BigInteger::one()->shiftedLeft($n);

        return $this->or($mask);
    }

    /**
     * Returns a BigInteger whose value is equivalent to this BigInteger with the designated bit cleared.
     *
     * Computes (this & ~(1<<n)).
     *
     * @param int $n The bit to clear, 0-based.
     *
     * @throws InvalidArgumentException If the bit index is negative.
     *
     * @pure
     */
    public function withBitCleared(int $n): BigInteger
    {
        if ($n < 0) {
            throw NegativeBitIndexException::negative();
        }

        if (! $this->testBit($n)) {
            return $this;
        }

        $mask = BigInteger::one()->shiftedLeft($n);

        return $this->xor($mask);
    }

    /**
     * Returns a BigInteger whose value is equivalent to this BigInteger with the designated bit flipped.
     *
     * Computes (this ^ (1<<n)).
     *
     * @param int $n The bit to flip, 0-based.
     *
     * @throws InvalidArgumentException If the bit index is negative.
     *
     * @pure
     */
    public function withBitFlipped(int $n): BigInteger
    {
        if ($n < 0) {
            throw NegativeBitIndexException::negative();
        }

        $mask = BigInteger::one()->shiftedLeft($n);

        return $this->xor($mask);
    }

    #[Override]
    public function compareTo(BigNumber|int|float|string $that): int
    {
        $that = BigNumber::of($that);

        if ($that instanceof BigInteger) {
            return CalculatorRegistry::get()->cmp($this->value, $that->value);
        }

        return -$that->compareTo($this);
    }

    #[Override]
    public function getSign(): int
    {
        return ($this->value === '0') ? 0 : (($this->value[0] === '-') ? -1 : 1);
    }

    #[Override]
    public function toBigInteger(): BigInteger
    {
        return $this;
    }

    #[Override]
    public function toBigDecimal(): BigDecimal
    {
        return self::newBigDecimal($this->value);
    }

    #[Override]
    public function toBigRational(): BigRational
    {
        return self::newBigRational($this, BigInteger::one(), false);
    }

    #[Override]
    public function toScale(int $scale, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): BigDecimal
    {
        return $this->toBigDecimal()->toScale($scale, $roundingMode);
    }

    #[Override]
    public function toInt(): int
    {
        $intValue = filter_var($this->value, FILTER_VALIDATE_INT);

        if ($intValue === false) {
            throw IntegerOverflowException::toIntOverflow($this);
        }

        return $intValue;
    }

    #[Override]
    public function toFloat(): float
    {
        return (float) $this->value;
    }

    /**
     * Returns a string representation of this number in the given base.
     *
     * The output will always be lowercase for bases greater than 10.
     *
     * @throws InvalidArgumentException If the base is out of range.
     *
     * @pure
     */
    public function toBase(int $base): string
    {
        if ($base === 10) {
            return $this->value;
        }

        if ($base < 2 || $base > 36) {
            throw BaseOutOfRangeException::forBase($base, 2, 36);
        }

        return CalculatorRegistry::get()->toBase($this->value, $base);
    }

    /**
     * Returns a string representation of this number in an arbitrary base with a custom alphabet.
     *
     * Because this method accepts an alphabet with any character, including dash, it does not handle negative numbers;
     * a NegativeNumberException will be thrown when attempting to call this method on a negative number.
     *
     * @param string $alphabet The alphabet, for example '01' for base 2, or '01234567' for base 8.
     *
     * @throws NegativeNumberException  If this number is negative.
     * @throws InvalidArgumentException If the given alphabet does not contain at least 2 chars.
     *
     * @pure
     */
    public function toArbitraryBase(string $alphabet): string
    {
        $base = strlen($alphabet);

        if ($base < 2) {
            throw AlphabetTooShortException::tooShort();
        }

        if ($this->value[0] === '-') {
            throw NegativeArbitraryBaseException::negative();
        }

        return CalculatorRegistry::get()->toArbitraryBase($this->value, $alphabet, $base);
    }

    /**
     * Returns a string of bytes containing the binary representation of this BigInteger.
     *
     * The string is in big-endian byte-order: the most significant byte is in the zeroth element.
     *
     * If `$signed` is true, the output will be in two's-complement representation, and a sign bit will be prepended to
     * the output. If `$signed` is false, no sign bit will be prepended, and this method will throw an exception if the
     * number is negative.
     *
     * The string will contain the minimum number of bytes required to represent this BigInteger, including a sign bit
     * if `$signed` is true.
     *
     * This representation is compatible with the `fromBytes()` factory method, as long as the `$signed` flags match.
     *
     * @param bool $signed Whether to output a signed number in two's-complement representation with a leading sign bit.
     *
     * @throws NegativeNumberException If $signed is false, and the number is negative.
     *
     * @pure
     */
    public function toBytes(bool $signed = true): string
    {
        if (! $signed && $this->isNegative()) {
            throw NegativeByteConversionException::negative();
        }

        $hex = $this->abs()->toBase(16);

        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        $baseHexLength = strlen($hex);

        if ($signed) {
            if ($this->isNegative()) {
                $bin = hex2bin($hex);
                assert($bin !== false);

                $hex = bin2hex(~$bin);
                $hex = self::fromBase($hex, 16)->plus(1)->toBase(16);

                $hexLength = strlen($hex);

                if ($hexLength < $baseHexLength) {
                    $hex = str_repeat('0', $baseHexLength - $hexLength) . $hex;
                }

                if ($hex[0] < '8') {
                    $hex = 'FF' . $hex;
                }
            } else {
                if ($hex[0] >= '8') {
                    $hex = '00' . $hex;
                }
            }
        }

        $result = hex2bin($hex);
        assert($result !== false);

        return $result;
    }

    /**
     * @return numeric-string
     */
    #[Override]
    public function __toString(): string
    {
        /** @var numeric-string */
        return $this->value;
    }

    /**
     * This method is required for serializing the object and SHOULD NOT be accessed directly.
     *
     * @internal
     *
     * @return array{value: string}
     */
    public function __serialize(): array
    {
        return ['value' => $this->value];
    }

    /**
     * This method is only here to allow unserializing the object and cannot be accessed directly.
     *
     * @internal
     *
     * @param array{value: string} $data
     *
     * @throws LogicException
     */
    public function __unserialize(array $data): void
    {
        /** @phpstan-ignore isset.initializedProperty */
        if (isset($this->value)) {
            throw UnserializeCalledException::calledDirectly();
        }

        /** @phpstan-ignore deadCode.unreachable */
        $this->value = $data['value'];
    }

    #[Override]
    protected static function from(BigNumber $number): static
    {
        return $number->toBigInteger();
    }
}
