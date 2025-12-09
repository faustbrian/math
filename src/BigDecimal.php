<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\ExponentOutOfRangeException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeScaleException;
use Brick\Math\Exception\NegativeSquareRootException;
use Brick\Math\Exception\SquareRootRoundingException;
use Brick\Math\Exception\UnserializeCalledException;
use Brick\Math\Internal\Calculator;
use Brick\Math\Internal\CalculatorRegistry;
use Override;

use function rtrim;
use function sprintf;
use function str_pad;
use function str_repeat;
use function strlen;
use function substr;

use const STR_PAD_LEFT;

/**
 * Immutable, arbitrary-precision signed decimal numbers.
 */
final readonly class BigDecimal extends BigNumber
{
    /**
     * The unscaled value of this decimal number.
     *
     * This is a string of digits with an optional leading minus sign.
     * No leading zero must be present.
     * No leading minus sign must be present if the value is 0.
     */
    private string $value;

    /**
     * The scale (number of digits after the decimal point) of this decimal number.
     *
     * This must be zero or more.
     */
    private int $scale;

    /**
     * Protected constructor. Use a factory method to obtain an instance.
     *
     * @param string $value The unscaled value, validated.
     * @param int    $scale The scale, validated.
     *
     * @pure
     */
    protected function __construct(string $value, int $scale = 0)
    {
        $this->value = $value;
        $this->scale = $scale;
    }

    /**
     * Creates a BigDecimal from an unscaled value and a scale.
     *
     * Example: `(12345, 3)` will result in the BigDecimal `12.345`.
     *
     * @param BigNumber|int|float|string $value The unscaled value. Must be convertible to a BigInteger.
     * @param int                        $scale The scale of the number. If negative, the scale will be set to zero
     *                                          and the unscaled value will be adjusted accordingly.
     *
     * @pure
     */
    public static function ofUnscaledValue(BigNumber|int|float|string $value, int $scale = 0): BigDecimal
    {
        $value = (string) BigInteger::of($value);

        if ($scale < 0) {
            if ($value !== '0') {
                $value .= str_repeat('0', -$scale);
            }
            $scale = 0;
        }

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns a BigDecimal representing zero, with a scale of zero.
     *
     * @pure
     */
    public static function zero(): BigDecimal
    {
        /** @var BigDecimal|null $zero */
        static $zero;

        if ($zero === null) {
            $zero = new BigDecimal('0');
        }

        return $zero;
    }

    /**
     * Returns a BigDecimal representing one, with a scale of zero.
     *
     * @pure
     */
    public static function one(): BigDecimal
    {
        /** @var BigDecimal|null $one */
        static $one;

        if ($one === null) {
            $one = new BigDecimal('1');
        }

        return $one;
    }

    /**
     * Returns a BigDecimal representing ten, with a scale of zero.
     *
     * @pure
     */
    public static function ten(): BigDecimal
    {
        /** @var BigDecimal|null $ten */
        static $ten;

        if ($ten === null) {
            $ten = new BigDecimal('10');
        }

        return $ten;
    }

    /**
     * Returns the mathematical constant π (pi) to the specified scale.
     *
     * Uses the Machin formula: π/4 = 4·arctan(1/5) - arctan(1/239)
     * with Taylor series expansion for arctan.
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public static function pi(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($scale === 0) {
            return new BigDecimal('3', 0);
        }

        // Use extra precision for intermediate calculations
        $workingScale = $scale + 10;

        // Machin's formula: π/4 = 4·arctan(1/5) - arctan(1/239)
        $arctan5 = self::arctanReciprocal(5, $workingScale);
        $arctan239 = self::arctanReciprocal(239, $workingScale);

        $pi = $arctan5->multipliedBy(4)->minus($arctan239)->multipliedBy(4);

        return $pi->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Computes arctan(1/n) using the Taylor series.
     *
     * arctan(x) = x - x³/3 + x⁵/5 - x⁷/7 + ...
     * arctan(1/n) = 1/n - 1/(3n³) + 1/(5n⁵) - ...
     *
     * @pure
     */
    private static function arctanReciprocal(int $n, int $scale): BigDecimal
    {
        $nSquared = $n * $n;
        $nPower = BigDecimal::of($n);
        $result = BigDecimal::one()->dividedBy($n, $scale, RoundingMode::DOWN);
        $term = $result;
        $k = 1;
        $sign = -1;

        for (; ;) {
            $nPower = $nPower->multipliedBy($nSquared);
            $k += 2;
            $newTerm = BigDecimal::one()->dividedBy($nPower->multipliedBy($k), $scale, RoundingMode::DOWN);

            if ($newTerm->isZero()) {
                break;
            }

            if ($sign === 1) {
                $result = $result->plus($newTerm);
            } else {
                $result = $result->minus($newTerm);
            }

            $term = $newTerm;
            $sign = -$sign;
        }

        return $result;
    }

    /**
     * Returns Euler's number e to the specified scale.
     *
     * Uses the Taylor series: e = Σ(1/n!) for n = 0 to ∞
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public static function e(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($scale === 0) {
            return new BigDecimal('3', 0);
        }

        // Use extra precision for intermediate calculations
        $workingScale = $scale + 10;

        // e = 1 + 1/1! + 1/2! + 1/3! + ...
        $result = BigDecimal::one();
        $factorial = BigInteger::one();
        $n = 1;

        for (; ;) {
            $factorial = $factorial->multipliedBy($n);
            $term = BigDecimal::one()->dividedBy($factorial->toBigDecimal(), $workingScale, RoundingMode::DOWN);

            if ($term->isZero()) {
                break;
            }

            $result = $result->plus($term);
            $n++;
        }

        return $result->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the golden ratio φ (phi) to the specified scale.
     *
     * φ = (1 + √5) / 2 ≈ 1.6180339887...
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public static function phi(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        // φ = (1 + √5) / 2
        $workingScale = $scale + 5;
        $sqrt5 = BigDecimal::of(5)->sqrt($workingScale, RoundingMode::DOWN);

        return BigDecimal::one()->plus($sqrt5)->dividedBy(2, $scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The number to add. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the number is not valid, or is not convertible to a BigDecimal.
     *
     * @pure
     */
    public function plus(BigNumber|int|float|string $that): BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->value === '0' && $that->scale <= $this->scale) {
            return $this;
        }

        if ($this->value === '0' && $this->scale <= $that->scale) {
            return $that;
        }

        [$a, $b] = $this->scaleValues($this, $that);

        $value = CalculatorRegistry::get()->add($a, $b);
        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The number to subtract. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the number is not valid, or is not convertible to a BigDecimal.
     *
     * @pure
     */
    public function minus(BigNumber|int|float|string $that): BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->value === '0' && $that->scale <= $this->scale) {
            return $this;
        }

        [$a, $b] = $this->scaleValues($this, $that);

        $value = CalculatorRegistry::get()->sub($a, $b);
        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the product of this number and the given one.
     *
     * The result has a scale of `$this->scale + $that->scale`.
     *
     * @param BigNumber|int|float|string $that The multiplier. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the multiplier is not a valid number, or is not convertible to a BigDecimal.
     *
     * @pure
     */
    public function multipliedBy(BigNumber|int|float|string $that): BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->value === '1' && $that->scale === 0) {
            return $this;
        }

        if ($this->value === '1' && $this->scale === 0) {
            return $that;
        }

        $value = CalculatorRegistry::get()->mul($this->value, $that->value);
        $scale = $this->scale + $that->scale;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the result of the division of this number by the given one, at the given scale.
     *
     * @param BigNumber|int|float|string $that         The divisor.
     * @param int|null                   $scale        The desired scale, or null to use the scale of this number.
     * @param RoundingMode               $roundingMode An optional rounding mode, defaults to UNNECESSARY.
     *
     * @throws InvalidArgumentException If the scale or rounding mode is invalid.
     * @throws MathException            If the number is invalid, is zero, or rounding was necessary.
     *
     * @pure
     */
    public function dividedBy(BigNumber|int|float|string $that, ?int $scale = null, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        if ($scale === null) {
            $scale = $this->scale;
        } elseif ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($that->value === '1' && $that->scale === 0 && $scale === $this->scale) {
            return $this;
        }

        $p = $this->valueWithMinScale($that->scale + $scale);
        $q = $that->valueWithMinScale($this->scale - $scale);

        $result = CalculatorRegistry::get()->divRound($p, $q, $roundingMode);

        return new BigDecimal($result, $scale);
    }

    /**
     * Returns the exact result of the division of this number by the given one.
     *
     * The scale of the result is automatically calculated to fit all the fraction digits.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the divisor is not a valid number, is not convertible to a BigDecimal, is zero,
     *                       or the result yields an infinite number of digits.
     *
     * @pure
     */
    public function exactlyDividedBy(BigNumber|int|float|string $that): BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        [, $b] = $this->scaleValues($this, $that);

        $d = rtrim($b, '0');
        $scale = strlen($b) - strlen($d);

        $calculator = CalculatorRegistry::get();

        foreach ([5, 2] as $prime) {
            for (; ;) {
                $lastDigit = (int) $d[-1];

                if ($lastDigit % $prime !== 0) {
                    break;
                }

                $d = $calculator->divQ($d, (string) $prime);
                $scale++;
            }
        }

        return $this->dividedBy($that, $scale)->stripTrailingZeros();
    }

    /**
     * Limits (clamps) this number between the given minimum and maximum values.
     *
     * If the number is lower than $min, returns a copy of $min.
     * If the number is greater than $max, returns a copy of $max.
     * Otherwise, returns this number unchanged.
     *
     * @param BigNumber|int|float|string $min The minimum. Must be convertible to a BigDecimal.
     * @param BigNumber|int|float|string $max The maximum. Must be convertible to a BigDecimal.
     *
     * @throws MathException If min/max are not convertible to a BigDecimal.
     */
    public function clamp(BigNumber|int|float|string $min, BigNumber|int|float|string $max): BigDecimal
    {
        if ($this->isLessThan($min)) {
            return BigDecimal::of($min);
        } elseif ($this->isGreaterThan($max)) {
            return BigDecimal::of($max);
        }

        return $this;
    }

    /**
     * Rounds this number to the given scale, using the given rounding mode.
     *
     * @param int          $scale        The scale to round to.
     * @param RoundingMode $roundingMode The rounding mode to use.
     *
     * @throws InvalidArgumentException If the scale is negative.
     *
     * @pure
     */
    public function round(int $scale, RoundingMode $roundingMode = RoundingMode::HALF_UP): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($scale >= $this->scale) {
            return $this;
        }

        return $this->dividedBy(BigDecimal::one(), $scale, $roundingMode);
    }

    /**
     * Returns this number exponentiated to the given value.
     *
     * The result has a scale of `$this->scale * $exponent`.
     *
     * @throws InvalidArgumentException If the exponent is not in the range 0 to 1,000,000.
     *
     * @pure
     */
    public function power(int $exponent): BigDecimal
    {
        if ($exponent === 0) {
            return BigDecimal::one();
        }

        if ($exponent === 1) {
            return $this;
        }

        if ($exponent < 0 || $exponent > Calculator::MAX_POWER) {
            throw ExponentOutOfRangeException::forExponent($exponent, Calculator::MAX_POWER);
        }

        return new BigDecimal(CalculatorRegistry::get()->pow($this->value, $exponent), $this->scale * $exponent);
    }

    /**
     * Returns the quotient of the division of this number by the given one.
     *
     * The quotient has a scale of `0`.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the divisor is not a valid decimal number, or is zero.
     *
     * @pure
     */
    public function quotient(BigNumber|int|float|string $that): BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        $quotient = CalculatorRegistry::get()->divQ($p, $q);

        return new BigDecimal($quotient, 0);
    }

    /**
     * Returns the remainder of the division of this number by the given one.
     *
     * The remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the divisor is not a valid decimal number, or is zero.
     *
     * @pure
     */
    public function remainder(BigNumber|int|float|string $that): BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        $remainder = CalculatorRegistry::get()->divR($p, $q);

        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new BigDecimal($remainder, $scale);
    }

    /**
     * Returns the quotient and remainder of the division of this number by the given one.
     *
     * The quotient has a scale of `0`, and the remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return array{BigDecimal, BigDecimal} An array containing the quotient and the remainder.
     *
     * @throws MathException If the divisor is not a valid decimal number, or is zero.
     *
     * @pure
     */
    public function quotientAndRemainder(BigNumber|int|float|string $that): array
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        [$quotient, $remainder] = CalculatorRegistry::get()->divQR($p, $q);

        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        $quotient = new BigDecimal($quotient, 0);
        $remainder = new BigDecimal($remainder, $scale);

        return [$quotient, $remainder];
    }

    /**
     * Returns the square root of this number, rounded to the given number of decimals.
     *
     * @throws InvalidArgumentException If the scale is negative.
     * @throws NegativeNumberException  If this number is negative.
     * @throws RoundingNecessaryException If RoundingMode::UNNECESSARY is used and rounding is needed.
     *
     * @pure
     */
    public function sqrt(int $scale, RoundingMode $roundingMode = RoundingMode::DOWN): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->value === '0') {
            return new BigDecimal('0', $scale);
        }

        if ($this->value[0] === '-') {
            throw NegativeSquareRootException::negative();
        }

        // For rounding, we need extra precision to determine the correct rounding
        // We calculate with 1 extra digit, then round based on that digit
        $extraScale = $scale + 1;

        $value = $this->value;
        $addDigits = 2 * $extraScale - $this->scale;

        if ($addDigits > 0) {
            // add zeros
            $value .= str_repeat('0', $addDigits);
        } elseif ($addDigits < 0) {
            // trim digits
            if (-$addDigits >= strlen($this->value)) {
                // requesting a scale too low, will always yield a zero result
                return new BigDecimal('0', $scale);
            }

            $value = substr($value, 0, $addDigits);
        }

        $sqrtValue = CalculatorRegistry::get()->sqrt($value);
        $sqrtDecimal = new BigDecimal($sqrtValue, $extraScale);

        // Check if result is exact (sqrt² == original value at requested scale)
        // For UNNECESSARY mode and optimization
        $squared = $sqrtDecimal->multipliedBy($sqrtDecimal);
        $isExact = $squared->isEqualTo($this);

        if ($isExact) {
            // Exact result, just truncate the extra digit
            return $sqrtDecimal->toScale($scale, RoundingMode::DOWN);
        }

        if ($roundingMode === RoundingMode::UNNECESSARY) {
            throw SquareRootRoundingException::roundingNecessary();
        }

        // Apply the rounding mode to go from extraScale to scale
        return $sqrtDecimal->toScale($scale, $roundingMode);
    }

    /**
     * Returns the natural logarithm (ln) of this number.
     *
     * Uses the Taylor series: ln(x) = 2 × (y + y³/3 + y⁵/5 + ...) where y = (x-1)/(x+1)
     * For faster convergence, we use: ln(x) = ln(x/e^k) + k for appropriate k.
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If the number is not positive or scale is negative.
     *
     * @pure
     */
    public function ln(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->isNegativeOrZero()) {
            throw new \InvalidArgumentException('Natural logarithm is only defined for positive numbers.');
        }

        if ($this->isEqualTo(1)) {
            return new BigDecimal('0', $scale);
        }

        $workingScale = $scale + 15;

        // For better convergence, use ln(x) = ln(x/e^k) + k where we choose k to make x/e^k close to 1
        // For simplicity, we use the series directly with argument reduction using powers of 2

        // Reduce x to the range [1, 2) by dividing by 2^k
        $x = $this;
        $k = 0;
        $two = BigDecimal::of(2);

        while ($x->isGreaterThan($two)) {
            $x = $x->dividedBy(2, $workingScale, RoundingMode::DOWN);
            $k++;
        }

        $half = BigDecimal::of('0.5');
        while ($x->isLessThan($half)) {
            $x = $x->multipliedBy(2);
            $k--;
        }

        // Now compute ln(x) for x in [0.5, 2] using the series
        // ln(x) = 2 * (y + y³/3 + y⁵/5 + ...) where y = (x-1)/(x+1)
        $xMinus1 = $x->minus(1);
        $xPlus1 = $x->plus(1);
        $y = $xMinus1->dividedBy($xPlus1, $workingScale, RoundingMode::DOWN);

        $y2 = $y->multipliedBy($y);
        $result = $y;
        $yPower = $y;
        $n = 1;

        for (; ;) {
            $n += 2;
            $yPower = $yPower->multipliedBy($y2);
            $term = $yPower->dividedBy($n, $workingScale, RoundingMode::DOWN);

            if ($term->isZero()) {
                break;
            }

            $result = $result->plus($term);
        }

        $result = $result->multipliedBy(2);

        // Add back k * ln(2)
        if ($k !== 0) {
            $ln2 = self::computeLn2($workingScale);
            $result = $result->plus($ln2->multipliedBy($k));
        }

        return $result->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Computes ln(2) to the given scale using Taylor series.
     *
     * @pure
     */
    private static function computeLn2(int $scale): BigDecimal
    {
        // ln(2) = 2 * (1/3 + 1/(3*3³) + 1/(5*3⁵) + ...) using y = 1/3 from (2-1)/(2+1)
        $y = BigDecimal::one()->dividedBy(3, $scale, RoundingMode::DOWN);
        $y2 = $y->multipliedBy($y);
        $result = $y;
        $yPower = $y;
        $n = 1;

        for (; ;) {
            $n += 2;
            $yPower = $yPower->multipliedBy($y2);
            $term = $yPower->dividedBy($n, $scale, RoundingMode::DOWN);

            if ($term->isZero()) {
                break;
            }

            $result = $result->plus($term);
        }

        return $result->multipliedBy(2);
    }

    /**
     * Returns the base-10 logarithm of this number.
     *
     * Computed as ln(x) / ln(10).
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If the number is not positive or scale is negative.
     *
     * @pure
     */
    public function log10(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->isNegativeOrZero()) {
            throw new \InvalidArgumentException('Logarithm is only defined for positive numbers.');
        }

        $workingScale = $scale + 10;
        $lnX = $this->ln($workingScale);
        $ln10 = BigDecimal::of(10)->ln($workingScale);

        return $lnX->dividedBy($ln10, $scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the logarithm of this number to the given base.
     *
     * Computed as ln(x) / ln(base).
     *
     * @param BigNumber|int|float|string $base The base of the logarithm. Must be positive and not equal to 1.
     * @param int                        $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If the number or base is not positive, or base is 1, or scale is negative.
     *
     * @pure
     */
    public function log(BigNumber|int|float|string $base, int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        $base = BigDecimal::of($base);

        if ($this->isNegativeOrZero()) {
            throw new \InvalidArgumentException('Logarithm is only defined for positive numbers.');
        }

        if ($base->isNegativeOrZero()) {
            throw new \InvalidArgumentException('Logarithm base must be positive.');
        }

        if ($base->isEqualTo(1)) {
            throw new \InvalidArgumentException('Logarithm base cannot be 1.');
        }

        $workingScale = $scale + 10;
        $lnX = $this->ln($workingScale);
        $lnBase = $base->ln($workingScale);

        return $lnX->dividedBy($lnBase, $scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns e raised to the power of this number.
     *
     * Uses the Taylor series: e^x = Σ(x^n/n!) for n = 0 to ∞
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public function exp(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->isZero()) {
            return BigDecimal::one()->toScale($scale, RoundingMode::DOWN);
        }

        $workingScale = $scale + 15;

        // For better convergence, reduce x to [-1, 1] using e^x = (e^(x/n))^n
        $x = $this;
        $squarings = 0;

        // Keep halving until |x| < 1
        while ($x->abs()->isGreaterThan(1)) {
            $x = $x->dividedBy(2, $workingScale, RoundingMode::DOWN);
            $squarings++;
        }

        // Taylor series: e^x = 1 + x + x²/2! + x³/3! + ...
        $result = BigDecimal::one();
        $term = BigDecimal::one();
        $n = 0;

        for (; ;) {
            $n++;
            $term = $term->multipliedBy($x)->dividedBy($n, $workingScale, RoundingMode::DOWN);

            if ($term->isZero()) {
                break;
            }

            $result = $result->plus($term);
        }

        // Square the result $squarings times
        for ($i = 0; $i < $squarings; $i++) {
            $result = $result->multipliedBy($result)->toScale($workingScale, RoundingMode::DOWN);
        }

        return $result->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the sine of this number (in radians).
     *
     * Uses the Taylor series: sin(x) = x - x³/3! + x⁵/5! - x⁷/7! + ...
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public function sin(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->isZero()) {
            return new BigDecimal('0', $scale);
        }

        $workingScale = $scale + 15;

        // Reduce angle to [-π, π] for better convergence
        $x = self::reduceAngle($this, $workingScale);

        // Taylor series: sin(x) = x - x³/3! + x⁵/5! - ...
        $x2 = $x->multipliedBy($x);
        $result = $x;
        $term = $x;
        $n = 1;
        $sign = -1;

        for (; ;) {
            $n += 2;
            $term = $term->multipliedBy($x2)->dividedBy(($n - 1) * $n, $workingScale, RoundingMode::DOWN);

            if ($term->isZero()) {
                break;
            }

            if ($sign === 1) {
                $result = $result->plus($term);
            } else {
                $result = $result->minus($term);
            }

            $sign = -$sign;
        }

        return $result->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the cosine of this number (in radians).
     *
     * Uses the Taylor series: cos(x) = 1 - x²/2! + x⁴/4! - x⁶/6! + ...
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public function cos(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->isZero()) {
            return BigDecimal::one()->toScale($scale, RoundingMode::DOWN);
        }

        $workingScale = $scale + 15;

        // Reduce angle to [-π, π] for better convergence
        $x = self::reduceAngle($this, $workingScale);

        // Taylor series: cos(x) = 1 - x²/2! + x⁴/4! - ...
        $x2 = $x->multipliedBy($x);
        $result = BigDecimal::one();
        $term = BigDecimal::one();
        $n = 0;
        $sign = -1;

        for (; ;) {
            $n += 2;
            $term = $term->multipliedBy($x2)->dividedBy(($n - 1) * $n, $workingScale, RoundingMode::DOWN);

            if ($term->isZero()) {
                break;
            }

            if ($sign === 1) {
                $result = $result->plus($term);
            } else {
                $result = $result->minus($term);
            }

            $sign = -$sign;
        }

        return $result->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the tangent of this number (in radians).
     *
     * Computed as sin(x) / cos(x).
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative or cos(x) is zero.
     *
     * @pure
     */
    public function tan(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        $workingScale = $scale + 10;
        $sinX = $this->sin($workingScale);
        $cosX = $this->cos($workingScale);

        if ($cosX->isZero()) {
            throw new \InvalidArgumentException('Tangent is undefined at this point (cos(x) = 0).');
        }

        return $sinX->dividedBy($cosX, $scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the arc sine (inverse sine) of this number.
     *
     * The input must be in the range [-1, 1].
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative or the number is outside [-1, 1].
     *
     * @pure
     */
    public function asin(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->abs()->isGreaterThan(1)) {
            throw new \InvalidArgumentException('Arc sine is only defined for values in the range [-1, 1].');
        }

        if ($this->isZero()) {
            return new BigDecimal('0', $scale);
        }

        if ($this->isEqualTo(1)) {
            return self::pi($scale)->dividedBy(2, $scale, RoundingMode::HALF_UP);
        }

        if ($this->isEqualTo(-1)) {
            return self::pi($scale)->dividedBy(2, $scale, RoundingMode::HALF_UP)->negated();
        }

        $workingScale = $scale + 15;

        // For |x| > 0.5, use asin(x) = π/2 - 2*asin(sqrt((1-x)/2)) for better convergence
        if ($this->abs()->isGreaterThan(BigDecimal::of('0.5'))) {
            $sign = $this->getSign();
            $absX = $this->abs();
            $halfPi = self::pi($workingScale)->dividedBy(2, $workingScale, RoundingMode::DOWN);
            $sqrtArg = BigDecimal::one()->minus($absX)->dividedBy(2, $workingScale, RoundingMode::DOWN);
            $sqrtVal = $sqrtArg->sqrt($workingScale, RoundingMode::DOWN);
            $innerAsin = $sqrtVal->asin($workingScale);
            $result = $halfPi->minus($innerAsin->multipliedBy(2));

            if ($sign < 0) {
                $result = $result->negated();
            }

            return $result->toScale($scale, RoundingMode::HALF_UP);
        }

        // Taylor series: asin(x) = x + x³/6 + 3x⁵/40 + ...
        // = Σ ((2n)! / (4^n * (n!)² * (2n+1))) * x^(2n+1)
        $x2 = $this->multipliedBy($this);
        $result = $this;
        $term = $this;
        $n = 0;

        for (; ;) {
            $n++;
            // term *= x² * (2n-1)² / (2n * (2n+1))
            $numerator = (2 * $n - 1) * (2 * $n - 1);
            $denominator = 2 * $n * (2 * $n + 1);
            $term = $term->multipliedBy($x2)->multipliedBy($numerator)->dividedBy($denominator, $workingScale, RoundingMode::DOWN);

            if ($term->isZero()) {
                break;
            }

            $result = $result->plus($term);
        }

        return $result->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the arc cosine (inverse cosine) of this number.
     *
     * The input must be in the range [-1, 1].
     * Computed as π/2 - asin(x).
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative or the number is outside [-1, 1].
     *
     * @pure
     */
    public function acos(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->abs()->isGreaterThan(1)) {
            throw new \InvalidArgumentException('Arc cosine is only defined for values in the range [-1, 1].');
        }

        $workingScale = $scale + 10;
        $halfPi = self::pi($workingScale)->dividedBy(2, $workingScale, RoundingMode::DOWN);
        $asinX = $this->asin($workingScale);

        return $halfPi->minus($asinX)->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the arc tangent (inverse tangent) of this number.
     *
     * Uses the Taylor series: atan(x) = x - x³/3 + x⁵/5 - x⁷/7 + ...
     * For |x| > 1, uses atan(x) = π/2 - atan(1/x).
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public function atan(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->isZero()) {
            return new BigDecimal('0', $scale);
        }

        $workingScale = $scale + 15;

        // For |x| > 1, use atan(x) = sign(x) * π/2 - atan(1/x)
        if ($this->abs()->isGreaterThan(1)) {
            $sign = $this->getSign();
            $halfPi = self::pi($workingScale)->dividedBy(2, $workingScale, RoundingMode::DOWN);
            $reciprocal = BigDecimal::one()->dividedBy($this, $workingScale, RoundingMode::DOWN);
            $atanRecip = $reciprocal->atan($workingScale);

            if ($sign > 0) {
                return $halfPi->minus($atanRecip)->toScale($scale, RoundingMode::HALF_UP);
            }

            return $halfPi->negated()->minus($atanRecip)->toScale($scale, RoundingMode::HALF_UP);
        }

        // For |x| > 0.5, use argument reduction: atan(x) = 2 * atan(x / (1 + sqrt(1 + x²)))
        $absX = $this->abs();
        $threshold = new BigDecimal('5', 1); // 0.5

        if ($absX->isGreaterThan($threshold)) {
            $x2 = $this->multipliedBy($this);
            $sqrt = $x2->plus(BigDecimal::one())->sqrt($workingScale);
            $reduced = $this->dividedBy($sqrt->plus(BigDecimal::one()), $workingScale, RoundingMode::DOWN);

            return $reduced->atan($workingScale)->multipliedBy(2)->toScale($scale, RoundingMode::HALF_UP);
        }

        // Taylor series: atan(x) = x - x³/3 + x⁵/5 - x⁷/7 + ...
        $x2 = $this->multipliedBy($this);
        $result = $this;
        $term = $this;
        $n = 1;
        $sign = -1;

        for (; ;) {
            $n += 2;
            $term = $term->multipliedBy($x2);
            $currentTerm = $term->dividedBy($n, $workingScale, RoundingMode::DOWN);

            if ($currentTerm->isZero()) {
                break;
            }

            if ($sign === 1) {
                $result = $result->plus($currentTerm);
            } else {
                $result = $result->minus($currentTerm);
            }

            $sign = -$sign;
        }

        return $result->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the hyperbolic sine of this number.
     *
     * Computed as (e^x - e^(-x)) / 2.
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public function sinh(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->isZero()) {
            return new BigDecimal('0', $scale);
        }

        $workingScale = $scale + 10;
        $expX = $this->exp($workingScale);
        $expNegX = $this->negated()->exp($workingScale);

        return $expX->minus($expNegX)->dividedBy(2, $scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the hyperbolic cosine of this number.
     *
     * Computed as (e^x + e^(-x)) / 2.
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public function cosh(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->isZero()) {
            return BigDecimal::one()->toScale($scale, RoundingMode::DOWN);
        }

        $workingScale = $scale + 10;
        $expX = $this->exp($workingScale);
        $expNegX = $this->negated()->exp($workingScale);

        return $expX->plus($expNegX)->dividedBy(2, $scale, RoundingMode::HALF_UP);
    }

    /**
     * Returns the hyperbolic tangent of this number.
     *
     * Computed as sinh(x) / cosh(x).
     *
     * @param int $scale The number of decimal places.
     *
     * @throws InvalidArgumentException If scale is negative.
     *
     * @pure
     */
    public function tanh(int $scale): BigDecimal
    {
        if ($scale < 0) {
            throw NegativeScaleException::negative();
        }

        if ($this->isZero()) {
            return new BigDecimal('0', $scale);
        }

        $workingScale = $scale + 10;

        return $this->sinh($workingScale)->dividedBy($this->cosh($workingScale), $scale, RoundingMode::HALF_UP);
    }

    /**
     * Reduces an angle to the range [-π, π] for better Taylor series convergence.
     *
     * @pure
     */
    private static function reduceAngle(BigDecimal $angle, int $scale): BigDecimal
    {
        $pi = self::pi($scale);
        $twoPi = $pi->multipliedBy(2);

        // Reduce to [-2π, 2π]
        $reduced = $angle->remainder($twoPi);

        // Further reduce to [-π, π]
        if ($reduced->isGreaterThan($pi)) {
            $reduced = $reduced->minus($twoPi);
        } elseif ($reduced->isLessThan($pi->negated())) {
            $reduced = $reduced->plus($twoPi);
        }

        return $reduced;
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the left.
     *
     * @pure
     */
    public function withPointMovedLeft(int $n): BigDecimal
    {
        if ($n === 0) {
            return $this;
        }

        if ($n < 0) {
            return $this->withPointMovedRight(-$n);
        }

        return new BigDecimal($this->value, $this->scale + $n);
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the right.
     *
     * @pure
     */
    public function withPointMovedRight(int $n): BigDecimal
    {
        if ($n === 0) {
            return $this;
        }

        if ($n < 0) {
            return $this->withPointMovedLeft(-$n);
        }

        $value = $this->value;
        $scale = $this->scale - $n;

        if ($scale < 0) {
            if ($value !== '0') {
                $value .= str_repeat('0', -$scale);
            }
            $scale = 0;
        }

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns a copy of this BigDecimal with any trailing zeros removed from the fractional part.
     *
     * @pure
     */
    public function stripTrailingZeros(): BigDecimal
    {
        if ($this->scale === 0) {
            return $this;
        }

        $trimmedValue = rtrim($this->value, '0');

        if ($trimmedValue === '') {
            return BigDecimal::zero();
        }

        $trimmableZeros = strlen($this->value) - strlen($trimmedValue);

        if ($trimmableZeros === 0) {
            return $this;
        }

        if ($trimmableZeros > $this->scale) {
            $trimmableZeros = $this->scale;
        }

        $value = substr($this->value, 0, -$trimmableZeros);
        $scale = $this->scale - $trimmableZeros;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the absolute value of this number.
     *
     * @pure
     */
    public function abs(): BigDecimal
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Returns the negated value of this number.
     *
     * @pure
     */
    public function negated(): BigDecimal
    {
        return new BigDecimal(CalculatorRegistry::get()->neg($this->value), $this->scale);
    }

    #[Override]
    public function compareTo(BigNumber|int|float|string $that): int
    {
        $that = BigNumber::of($that);

        if ($that instanceof BigInteger) {
            $that = $that->toBigDecimal();
        }

        if ($that instanceof BigDecimal) {
            [$a, $b] = $this->scaleValues($this, $that);

            return CalculatorRegistry::get()->cmp($a, $b);
        }

        return -$that->compareTo($this);
    }

    #[Override]
    public function getSign(): int
    {
        return ($this->value === '0') ? 0 : (($this->value[0] === '-') ? -1 : 1);
    }

    /**
     * @pure
     */
    public function getUnscaledValue(): BigInteger
    {
        return self::newBigInteger($this->value);
    }

    /**
     * @pure
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * Returns the number of significant digits in the number.
     *
     * This is the number of digits to both sides of the decimal point, stripped of leading zeros.
     * The sign has no impact on the result.
     *
     * Examples:
     *   0 => 0
     *   0.0 => 0
     *   123 => 3
     *   123.456 => 6
     *   0.00123 => 3
     *   0.0012300 => 5
     *
     * @pure
     */
    public function getPrecision(): int
    {
        $value = $this->value;

        if ($value === '0') {
            return 0;
        }

        $length = strlen($value);

        return ($value[0] === '-') ? $length - 1 : $length;
    }

    /**
     * Returns a string representing the integral part of this decimal number.
     *
     * Example: `-123.456` => `-123`.
     *
     * @pure
     */
    public function getIntegralPart(): string
    {
        if ($this->scale === 0) {
            return $this->value;
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return substr($value, 0, -$this->scale);
    }

    /**
     * Returns a string representing the fractional part of this decimal number.
     *
     * If the scale is zero, an empty string is returned.
     *
     * Examples: `-123.456` => '456', `123` => ''.
     *
     * @pure
     */
    public function getFractionalPart(): string
    {
        if ($this->scale === 0) {
            return '';
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return substr($value, -$this->scale);
    }

    /**
     * Returns whether this decimal number has a non-zero fractional part.
     *
     * @pure
     */
    public function hasNonZeroFractionalPart(): bool
    {
        return $this->getFractionalPart() !== str_repeat('0', $this->scale);
    }

    #[Override]
    public function toBigInteger(): BigInteger
    {
        $zeroScaleDecimal = $this->scale === 0 ? $this : $this->dividedBy(1, 0);

        return self::newBigInteger($zeroScaleDecimal->value);
    }

    #[Override]
    public function toBigDecimal(): BigDecimal
    {
        return $this;
    }

    #[Override]
    public function toBigRational(): BigRational
    {
        $numerator = self::newBigInteger($this->value);
        $denominator = self::newBigInteger('1' . str_repeat('0', $this->scale));

        return self::newBigRational($numerator, $denominator, false);
    }

    #[Override]
    public function toScale(int $scale, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): BigDecimal
    {
        if ($scale === $this->scale) {
            return $this;
        }

        return $this->dividedBy(BigDecimal::one(), $scale, $roundingMode);
    }

    #[Override]
    public function toInt(): int
    {
        return $this->toBigInteger()->toInt();
    }

    #[Override]
    public function toFloat(): float
    {
        return (float) (string) $this;
    }

    /**
     * @return numeric-string
     */
    #[Override]
    public function __toString(): string
    {
        if ($this->scale === 0) {
            /** @var numeric-string */
            return $this->value;
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        /** @phpstan-ignore return.type */
        return substr($value, 0, -$this->scale) . '.' . substr($value, -$this->scale);
    }

    /**
     * This method is required for serializing the object and SHOULD NOT be accessed directly.
     *
     * @internal
     *
     * @return array{value: string, scale: int}
     */
    public function __serialize(): array
    {
        return ['value' => $this->value, 'scale' => $this->scale];
    }

    /**
     * This method is only here to allow unserializing the object and cannot be accessed directly.
     *
     * @internal
     *
     * @param array{value: string, scale: int} $data
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
        $this->scale = $data['scale'];
    }

    #[Override]
    protected static function from(BigNumber $number): static
    {
        return $number->toBigDecimal();
    }

    /**
     * Puts the internal values of the given decimal numbers on the same scale.
     *
     * @return array{string, string} The scaled integer values of $x and $y.
     *
     * @pure
     */
    private function scaleValues(BigDecimal $x, BigDecimal $y): array
    {
        $a = $x->value;
        $b = $y->value;

        if ($b !== '0' && $x->scale > $y->scale) {
            $b .= str_repeat('0', $x->scale - $y->scale);
        } elseif ($a !== '0' && $x->scale < $y->scale) {
            $a .= str_repeat('0', $y->scale - $x->scale);
        }

        return [$a, $b];
    }

    /**
     * @pure
     */
    private function valueWithMinScale(int $scale): string
    {
        $value = $this->value;

        if ($this->value !== '0' && $scale > $this->scale) {
            $value .= str_repeat('0', $scale - $this->scale);
        }

        return $value;
    }

    /**
     * Adds leading zeros if necessary to the unscaled value to represent the full decimal number.
     *
     * @pure
     */
    private function getUnscaledValueWithLeadingZeros(): string
    {
        $value = $this->value;
        $targetLength = $this->scale + 1;
        $negative = ($value[0] === '-');
        $length = strlen($value);

        if ($negative) {
            $length--;
        }

        if ($length >= $targetLength) {
            return $this->value;
        }

        if ($negative) {
            $value = substr($value, 1);
        }

        $value = str_pad($value, $targetLength, '0', STR_PAD_LEFT);

        if ($negative) {
            $value = '-' . $value;
        }

        return $value;
    }
}
