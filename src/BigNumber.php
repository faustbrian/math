<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\DenominatorMustNotBeZeroException;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\ExponentTooLargeException;
use Brick\Math\Exception\InvalidNumberFormatException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NoValuesProvidedException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use JsonSerializable;
use Override;
use Stringable;

use function array_shift;
use function assert;
use function filter_var;
use function is_float;
use function is_int;
use function is_nan;
use function is_null;
use function ltrim;
use function preg_match;
use function str_contains;
use function str_repeat;
use function strlen;
use function substr;

use const FILTER_VALIDATE_INT;

use const PREG_UNMATCHED_AS_NULL;

/**
 * Abstract base class for arbitrary-precision numeric types.
 *
 * This sealed class provides the foundation for three concrete number types: BigInteger (whole numbers),
 * BigDecimal (decimal numbers with scale), and BigRational (fractions with numerator/denominator).
 *
 * BigNumber provides factory methods, comparison operations, and type conversion methods shared across
 * all numeric types. The of() method intelligently parses input and returns the most appropriate type:
 * integers become BigInteger, decimals become BigDecimal, and fractions become BigRational.
 *
 * ```php
 * $int = BigNumber::of(42);           // BigInteger
 * $decimal = BigNumber::of('3.14');   // BigDecimal
 * $fraction = BigNumber::of('1/3');   // BigRational
 * $min = BigNumber::min(5, 3, 9);     // Returns 3 as BigInteger
 * ```
 *
 * This class is sealed and should not be extended in userland code. Protected methods are internal
 * and may change in any version without notice.
 *
 * @phpstan-sealed BigInteger|BigDecimal|BigRational
 */
abstract readonly class BigNumber implements JsonSerializable, Stringable
{
    /**
     * The regular expression used to parse integer or decimal numbers.
     */
    private const PARSE_REGEXP_NUMERICAL =
        '/^' .
        '(?<sign>[\-\+])?' .
        '(?<integral>[0-9]+)?' .
        '(?<point>\.)?' .
        '(?<fractional>[0-9]+)?' .
        '(?:[eE](?<exponent>[\-\+]?[0-9]+))?' .
        '$/';

    /**
     * The regular expression used to parse rational numbers.
     */
    private const PARSE_REGEXP_RATIONAL =
        '/^' .
        '(?<sign>[\-\+])?' .
        '(?<numerator>[0-9]+)' .
        '\/?' .
        '(?<denominator>[0-9]+)' .
        '$/';

    /**
     * Creates a BigNumber instance from the given value using intelligent type detection.
     *
     * When called on BigNumber, automatically selects the most appropriate concrete type:
     * - BigNumber instances: returned unchanged
     * - Native integers: become BigInteger
     * - Floats: converted to string and parsed as BigDecimal
     * - Strings with "/": parsed as BigRational (e.g., "1/3")
     * - Strings with "." or "e": parsed as BigDecimal (e.g., "3.14", "1e5")
     * - Numeric strings: parsed as BigInteger (e.g., "42", "-123")
     *
     * When called on a concrete subclass (BigInteger, BigDecimal, or BigRational), the value is
     * converted to that specific type. If conversion requires rounding, an exception is thrown.
     *
     * ```php
     * BigNumber::of(42);           // BigInteger
     * BigNumber::of('3.14');       // BigDecimal
     * BigNumber::of('22/7');       // BigRational
     * BigDecimal::of('1/2');       // BigDecimal: 0.5 (exact conversion)
     * BigInteger::of('1/2');       // Throws: requires rounding
     * ```
     *
     * @param BigNumber|int|float|string $value The value to convert. Accepts BigNumber instances,
     *                                          native numeric types, or string representations.
     *
     * @return static A BigNumber instance of the appropriate concrete type.
     *
     * @throws \Brick\Math\Exception\NumberFormatException If the string format is invalid.
     * @throws \Brick\Math\Exception\DivisionByZeroException If parsing a rational with denominator zero.
     * @throws \Brick\Math\Exception\RoundingNecessaryException If converting to a subclass requires rounding.
     *
     * @pure
     */
    final public static function of(BigNumber|int|float|string $value): static
    {
        $value = self::_of($value);

        if (static::class === BigNumber::class) {
            assert($value instanceof static);

            return $value;
        }

        return static::from($value);
    }

    /**
     * Creates a BigNumber of the given value, or returns null if the input is null.
     *
     * Behaves like of() for non-null values.
     *
     * @see BigNumber::of()
     *
     * @throws NumberFormatException      If the format of the number is not valid.
     * @throws DivisionByZeroException    If the value represents a rational number with a denominator of zero.
     * @throws RoundingNecessaryException If the value cannot be converted to an instance of the subclass without rounding.
     */
    public static function ofNullable(BigNumber|int|float|string|null $value): ?static
    {
        if (is_null($value)) {
            return null;
        }

        return static::of($value);
    }

    /**
     * Returns the minimum of the given values.
     *
     * @param BigNumber|int|float|string ...$values The numbers to compare. All the numbers need to be convertible
     *                                              to an instance of the class this method is called on.
     *
     * @throws NoValuesProvidedException If no values are given.
     * @throws MathException             If an argument is not valid.
     *
     * @pure
     */
    final public static function min(BigNumber|int|float|string ...$values): static
    {
        $min = null;

        foreach ($values as $value) {
            $value = static::of($value);

            if ($min === null || $value->isLessThan($min)) {
                $min = $value;
            }
        }

        if ($min === null) {
            throw NoValuesProvidedException::forMethod(__METHOD__);
        }

        return $min;
    }

    /**
     * Returns the maximum of the given values.
     *
     * @param BigNumber|int|float|string ...$values The numbers to compare. All the numbers need to be convertible
     *                                              to an instance of the class this method is called on.
     *
     * @throws NoValuesProvidedException If no values are given.
     * @throws MathException             If an argument is not valid.
     *
     * @pure
     */
    final public static function max(BigNumber|int|float|string ...$values): static
    {
        $max = null;

        foreach ($values as $value) {
            $value = static::of($value);

            if ($max === null || $value->isGreaterThan($max)) {
                $max = $value;
            }
        }

        if ($max === null) {
            throw NoValuesProvidedException::forMethod(__METHOD__);
        }

        return $max;
    }

    /**
     * Returns the minimum of the given values, preserving the widest type.
     *
     * Unlike min(), this method returns a BigNumber whose concrete type is the widest
     * type among all input values (BigInteger < BigDecimal < BigRational).
     *
     * @param BigNumber|int|float|string ...$values The numbers to compare.
     *
     * @throws NoValuesProvidedException If no values are given.
     * @throws MathException             If an argument is not valid.
     *
     * @pure
     */
    final public static function minimumOf(BigNumber|int|float|string ...$values): BigNumber
    {
        if ($values === []) {
            throw NoValuesProvidedException::forMethod(__METHOD__);
        }

        // Convert all values to BigNumber and determine widest type
        $converted = [];
        $hasRational = false;
        $hasDecimal = false;

        foreach ($values as $value) {
            $bigNum = BigNumber::of($value);
            $converted[] = $bigNum;

            if ($bigNum instanceof BigRational) {
                $hasRational = true;
            } elseif ($bigNum instanceof BigDecimal) {
                $hasDecimal = true;
            }
        }

        // Find the minimum value
        $min = $converted[0];
        foreach ($converted as $value) {
            if ($value->isLessThan($min)) {
                $min = $value;
            }
        }

        // Convert result to widest type
        if ($hasRational) {
            return $min->toBigRational();
        }
        if ($hasDecimal) {
            return $min->toBigDecimal();
        }

        return $min->toBigInteger();
    }

    /**
     * Returns the maximum of the given values, preserving the widest type.
     *
     * Unlike max(), this method returns a BigNumber whose concrete type is the widest
     * type among all input values (BigInteger < BigDecimal < BigRational).
     *
     * @param BigNumber|int|float|string ...$values The numbers to compare.
     *
     * @throws NoValuesProvidedException If no values are given.
     * @throws MathException             If an argument is not valid.
     *
     * @pure
     */
    final public static function maximumOf(BigNumber|int|float|string ...$values): BigNumber
    {
        if ($values === []) {
            throw NoValuesProvidedException::forMethod(__METHOD__);
        }

        // Convert all values to BigNumber and determine widest type
        $converted = [];
        $hasRational = false;
        $hasDecimal = false;

        foreach ($values as $value) {
            $bigNum = BigNumber::of($value);
            $converted[] = $bigNum;

            if ($bigNum instanceof BigRational) {
                $hasRational = true;
            } elseif ($bigNum instanceof BigDecimal) {
                $hasDecimal = true;
            }
        }

        // Find the maximum value
        $max = $converted[0];
        foreach ($converted as $value) {
            if ($value->isGreaterThan($max)) {
                $max = $value;
            }
        }

        // Convert result to widest type
        if ($hasRational) {
            return $max->toBigRational();
        }
        if ($hasDecimal) {
            return $max->toBigDecimal();
        }

        return $max->toBigInteger();
    }

    /**
     * Widens the given values to the widest common type.
     *
     * Converts all input values to the widest type among them (BigInteger < BigDecimal < BigRational).
     * This is useful when you need to perform operations on mixed types while preserving precision.
     *
     * @param BigNumber|int|float|string ...$values The numbers to widen.
     *
     * @return BigNumber[] An array of BigNumber instances, all of the same concrete type.
     *
     * @throws NoValuesProvidedException If no values are given.
     * @throws MathException             If an argument is not valid.
     *
     * @pure
     */
    final public static function widen(BigNumber|int|float|string ...$values): array
    {
        if ($values === []) {
            throw NoValuesProvidedException::forMethod(__METHOD__);
        }

        // Convert all values to BigNumber and determine widest type
        $converted = [];
        $hasRational = false;
        $hasDecimal = false;

        foreach ($values as $value) {
            $bigNum = BigNumber::of($value);
            $converted[] = $bigNum;

            if ($bigNum instanceof BigRational) {
                $hasRational = true;
            } elseif ($bigNum instanceof BigDecimal) {
                $hasDecimal = true;
            }
        }

        // Widen all values to the widest type
        if ($hasRational) {
            return array_map(fn (BigNumber $n) => $n->toBigRational(), $converted);
        }
        if ($hasDecimal) {
            return array_map(fn (BigNumber $n) => $n->toBigDecimal(), $converted);
        }

        return array_map(fn (BigNumber $n) => $n->toBigInteger(), $converted);
    }

    /**
     * Returns the sum of the given values.
     *
     * When called on BigNumber, sum() accepts any supported type and returns a result whose type is the widest among
     * the given values (BigInteger < BigDecimal < BigRational).
     *
     * When called on BigInteger, BigDecimal, or BigRational, sum() requires that all values can be converted to that
     * specific subclass, and returns a result of the same type.
     *
     * @param BigNumber|int|float|string ...$values The values to add. All values must be convertible to the class on
     *                                              which this method is called.
     *
     * @throws NoValuesProvidedException If no values are given.
     * @throws MathException             If an argument is not valid.
     *
     * @pure
     */
    final public static function sum(BigNumber|int|float|string ...$values): static
    {
        $first = array_shift($values);

        if ($first === null) {
            throw NoValuesProvidedException::forMethod(__METHOD__);
        }

        $sum = static::of($first);

        foreach ($values as $value) {
            $sum = self::add($sum, static::of($value));
        }

        assert($sum instanceof static);

        return $sum;
    }

    /**
     * Checks if this number is equal to the given one.
     *
     * @pure
     */
    final public function isEqualTo(BigNumber|int|float|string $that): bool
    {
        return $this->compareTo($that) === 0;
    }

    /**
     * Checks if this number is strictly lower than the given one.
     *
     * @pure
     */
    final public function isLessThan(BigNumber|int|float|string $that): bool
    {
        return $this->compareTo($that) < 0;
    }

    /**
     * Checks if this number is lower than or equal to the given one.
     *
     * @pure
     */
    final public function isLessThanOrEqualTo(BigNumber|int|float|string $that): bool
    {
        return $this->compareTo($that) <= 0;
    }

    /**
     * Checks if this number is strictly greater than the given one.
     *
     * @pure
     */
    final public function isGreaterThan(BigNumber|int|float|string $that): bool
    {
        return $this->compareTo($that) > 0;
    }

    /**
     * Checks if this number is greater than or equal to the given one.
     *
     * @pure
     */
    final public function isGreaterThanOrEqualTo(BigNumber|int|float|string $that): bool
    {
        return $this->compareTo($that) >= 0;
    }

    /**
     * Checks if this number equals zero.
     *
     * @pure
     */
    final public function isZero(): bool
    {
        return $this->getSign() === 0;
    }

    /**
     * Checks if this number equals one.
     *
     * @pure
     */
    final public function isOne(): bool
    {
        return $this->isEqualTo(1);
    }

    /**
     * Checks if this number is strictly negative.
     *
     * @pure
     */
    final public function isNegative(): bool
    {
        return $this->getSign() < 0;
    }

    /**
     * Checks if this number is negative or zero.
     *
     * @pure
     */
    final public function isNegativeOrZero(): bool
    {
        return $this->getSign() <= 0;
    }

    /**
     * Checks if this number is strictly positive.
     *
     * @pure
     */
    final public function isPositive(): bool
    {
        return $this->getSign() > 0;
    }

    /**
     * Checks if this number is positive or zero.
     *
     * @pure
     */
    final public function isPositiveOrZero(): bool
    {
        return $this->getSign() >= 0;
    }

    /**
     * Returns the sign of this number.
     *
     * Indicates whether the number is negative, zero, or positive.
     *
     * @return -1|0|1 Returns -1 for negative numbers, 0 for zero, and 1 for positive numbers.
     *
     * @pure
     */
    abstract public function getSign(): int;

    /**
     * Compares this number to the given one.
     *
     * Returns -1 if `$this` is lower than, 0 if equal to, 1 if greater than `$that`.
     *
     * @return -1|0|1
     *
     * @throws MathException If the number is not valid.
     *
     * @pure
     */
    abstract public function compareTo(BigNumber|int|float|string $that): int;

    /**
     * Converts this number to a BigInteger.
     *
     * @throws RoundingNecessaryException If this number cannot be converted to a BigInteger without rounding.
     *
     * @pure
     */
    abstract public function toBigInteger(): BigInteger;

    /**
     * Converts this number to a BigDecimal.
     *
     * @throws RoundingNecessaryException If this number cannot be converted to a BigDecimal without rounding.
     *
     * @pure
     */
    abstract public function toBigDecimal(): BigDecimal;

    /**
     * Converts this number to a BigRational.
     *
     * @pure
     */
    abstract public function toBigRational(): BigRational;

    /**
     * Converts this number to a BigDecimal with the given scale, using rounding if necessary.
     *
     * @param int          $scale        The scale of the resulting `BigDecimal`.
     * @param RoundingMode $roundingMode An optional rounding mode, defaults to UNNECESSARY.
     *
     * @throws RoundingNecessaryException If this number cannot be converted to the given scale without rounding.
     *                                    This only applies when RoundingMode::UNNECESSARY is used.
     *
     * @pure
     */
    abstract public function toScale(int $scale, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): BigDecimal;

    /**
     * Returns the exact value of this number as a native integer.
     *
     * If this number cannot be converted to a native integer without losing precision, an exception is thrown.
     * Note that the acceptable range for an integer depends on the platform and differs for 32-bit and 64-bit.
     *
     * @throws MathException If this number cannot be exactly converted to a native integer.
     *
     * @pure
     */
    abstract public function toInt(): int;

    /**
     * Returns an approximation of this number as a floating-point value.
     *
     * Note that this method can discard information as the precision of a floating-point value
     * is inherently limited.
     *
     * If the number is greater than the largest representable floating point number, positive infinity is returned.
     * If the number is less than the smallest representable floating point number, negative infinity is returned.
     *
     * @pure
     */
    abstract public function toFloat(): float;

    #[Override]
    final public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    /**
     * Returns a string representation of this number.
     *
     * The output of this method can be parsed by the `of()` factory method;
     * this will yield an object equal to this one, without any information loss.
     *
     * @pure
     */
    abstract public function __toString(): string;

    /**
     * Overridden by subclasses to convert a BigNumber to an instance of the subclass.
     *
     * @throws RoundingNecessaryException If the value cannot be converted.
     *
     * @pure
     */
    abstract protected static function from(BigNumber $number): static;

    /**
     * Proxy method to access BigInteger's protected constructor from sibling classes.
     *
     * @internal
     *
     * @pure
     */
    final protected function newBigInteger(string $value): BigInteger
    {
        return new BigInteger($value);
    }

    /**
     * Proxy method to access BigDecimal's protected constructor from sibling classes.
     *
     * @internal
     *
     * @pure
     */
    final protected function newBigDecimal(string $value, int $scale = 0): BigDecimal
    {
        return new BigDecimal($value, $scale);
    }

    /**
     * Proxy method to access BigRational's protected constructor from sibling classes.
     *
     * @internal
     *
     * @pure
     */
    final protected function newBigRational(BigInteger $numerator, BigInteger $denominator, bool $checkDenominator): BigRational
    {
        return new BigRational($numerator, $denominator, $checkDenominator);
    }

    /**
     * @throws NumberFormatException   If the format of the number is not valid.
     * @throws DivisionByZeroException If the value represents a rational number with a denominator of zero.
     *
     * @pure
     */
    private static function _of(BigNumber|int|float|string $value): BigNumber
    {
        if ($value instanceof BigNumber) {
            return $value;
        }

        if (is_int($value)) {
            return new BigInteger((string) $value);
        }

        if (is_float($value)) {
            if (is_nan($value)) {
                $value = 'NAN';
            } else {
                $value = (string) $value;
            }
        }

        if (str_contains($value, '/')) {
            // Rational number
            if (preg_match(self::PARSE_REGEXP_RATIONAL, $value, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
                throw InvalidNumberFormatException::fromValue($value);
            }

            $sign = $matches['sign'];
            $numerator = $matches['numerator'];
            $denominator = $matches['denominator'];

            $numerator = self::cleanUp($sign, $numerator);
            $denominator = self::cleanUp(null, $denominator);

            if ($denominator === '0') {
                throw DenominatorMustNotBeZeroException::zero();
            }

            return new BigRational(
                new BigInteger($numerator),
                new BigInteger($denominator),
                false,
            );
        } else {
            // Integer or decimal number
            if (preg_match(self::PARSE_REGEXP_NUMERICAL, $value, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
                throw InvalidNumberFormatException::fromValue($value);
            }

            $sign = $matches['sign'];
            $point = $matches['point'];
            $integral = $matches['integral'];
            $fractional = $matches['fractional'];
            $exponent = $matches['exponent'];

            if ($integral === null && $fractional === null) {
                throw InvalidNumberFormatException::fromValue($value);
            }

            if ($integral === null) {
                $integral = '0';
            }

            if ($point !== null || $exponent !== null) {
                $fractional ??= '';

                if ($exponent !== null) {
                    if ($exponent[0] === '-') {
                        $exponent = ltrim(substr($exponent, 1), '0') ?: '0';
                        $exponent = filter_var($exponent, FILTER_VALIDATE_INT);
                        if ($exponent !== false) {
                            $exponent = -$exponent;
                        }
                    } else {
                        if ($exponent[0] === '+') {
                            $exponent = substr($exponent, 1);
                        }
                        $exponent = ltrim($exponent, '0') ?: '0';
                        $exponent = filter_var($exponent, FILTER_VALIDATE_INT);
                    }
                } else {
                    $exponent = 0;
                }

                if ($exponent === false) {
                    throw ExponentTooLargeException::tooLarge();
                }

                $unscaledValue = self::cleanUp($sign, $integral . $fractional);

                $scale = strlen($fractional) - $exponent;

                if ($scale < 0) {
                    if ($unscaledValue !== '0') {
                        $unscaledValue .= str_repeat('0', -$scale);
                    }
                    $scale = 0;
                }

                return new BigDecimal($unscaledValue, $scale);
            }

            $integral = self::cleanUp($sign, $integral);

            return new BigInteger($integral);
        }
    }

    /**
     * Removes optional leading zeros and applies sign.
     *
     * @param string|null $sign   The sign, '+' or '-', optional. Null is allowed for convenience and treated as '+'.
     * @param string      $number The number, validated as a string of digits.
     *
     * @pure
     */
    private static function cleanUp(string|null $sign, string $number): string
    {
        $number = ltrim($number, '0');

        if ($number === '') {
            return '0';
        }

        return $sign === '-' ? '-' . $number : $number;
    }

    /**
     * Adds two BigNumber instances in the correct order to avoid a RoundingNecessaryException.
     *
     * @pure
     */
    private static function add(BigNumber $a, BigNumber $b): BigNumber
    {
        if ($a instanceof BigRational) {
            return $a->plus($b);
        }

        if ($b instanceof BigRational) {
            return $b->plus($a);
        }

        if ($a instanceof BigDecimal) {
            return $a->plus($b);
        }

        if ($b instanceof BigDecimal) {
            return $b->plus($a);
        }

        return $a->plus($b);
    }
}
