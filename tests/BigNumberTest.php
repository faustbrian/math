<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\NoValuesProvidedException;
use Brick\Math\Exception\RoundingNecessaryException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for class BigNumber.
 *
 * Most of the tests are performed in concrete classes.
 * Only static methods that can be called on BigNumber itself may justify tests here.
 */
class BigNumberTest extends AbstractTestCase
{
    /**
     * @param class-string<BigNumber>          $callingClass  The BigNumber class to call sum() on.
     * @param list<BigNumber|int|float|string> $values        The values to add.
     * @param string                           $expectedClass The expected class name.
     * @param string                           $expectedSum   The expected sum.
     */
    #[DataProvider('providerSum')]
    public function testSum(string $callingClass, array $values, string $expectedClass, string $expectedSum): void
    {
        $sum = $callingClass::sum(...$values);

        self::assertInstanceOf($expectedClass, $sum);
        self::assertSame($expectedSum, (string) $sum);
    }

    public static function providerSum(): array
    {
        return [
            [BigNumber::class, [-1], BigInteger::class, '-1'],
            [BigNumber::class, [-1, '99'], BigInteger::class, '98'],
            [BigInteger::class, [-1, '99'], BigInteger::class, '98'],
            [BigDecimal::class, [-1, '99'], BigDecimal::class, '98'],
            [BigRational::class, [-1, '99'], BigRational::class, '98'],
            [BigNumber::class, [-1, '99', '-0.7'], BigDecimal::class, '97.3'],
            [BigDecimal::class, [-1, '99', '-0.7'], BigDecimal::class, '97.3'],
            [BigRational::class, [-1, '99', '-0.7'], BigRational::class, '973/10'],
            [BigNumber::class, [-1, '99', '-0.7', '3/2'], BigRational::class, '1976/20'],
            [BigNumber::class, [-1, '3/2'], BigRational::class, '1/2'],
            [BigNumber::class, [-0.5], BigDecimal::class, '-0.5'],
            [BigNumber::class, [-0.5, 1], BigDecimal::class, '0.5'],
            [BigNumber::class, [-0.5, 1, '0.7'], BigDecimal::class, '1.2'],
            [BigNumber::class, [-0.5, 1, '0.7', '47/7'], BigRational::class, '554/70'],
            [BigNumber::class, ['-1/9'], BigRational::class, '-1/9'],
            [BigNumber::class, ['-1/9', 123], BigRational::class, '1106/9'],
            [BigNumber::class, ['-1/9', 123, '8349.3771'], BigRational::class, '762503939/90000'],
            [BigNumber::class, ['-1/9', '8349.3771', 123], BigRational::class, '762503939/90000'],
        ];
    }

    /**
     * @param class-string<BigNumber>          $callingClass The BigNumber class to call sum() on.
     * @param list<BigNumber|int|float|string> $values       The values to add.
     */
    #[DataProvider('providerSumThrowsRoundingNecessaryException')]
    public function testSumThrowsRoundingNecessaryException(string $callingClass, array $values): void
    {
        $this->expectException(RoundingNecessaryException::class);
        $callingClass::sum(...$values);
    }

    public static function providerSumThrowsRoundingNecessaryException(): array
    {
        return [
            [BigInteger::class, [1, '1.5']],
            [BigDecimal::class, ['1.5', '1/3']],
        ];
    }

    /**
     * @param list<BigNumber|int|float|string> $values        The values to compare.
     * @param string                           $expectedClass The expected class name.
     * @param string                           $expectedValue The expected value.
     */
    #[DataProvider('providerMinimumOf')]
    public function testMinimumOf(array $values, string $expectedClass, string $expectedValue): void
    {
        $result = BigNumber::minimumOf(...$values);

        self::assertInstanceOf($expectedClass, $result);
        self::assertSame($expectedValue, (string) $result);
    }

    public static function providerMinimumOf(): array
    {
        return [
            // All integers
            [[1, 2, 3], BigInteger::class, '1'],
            [[3, 1, 2], BigInteger::class, '1'],
            [[-5, 0, 5], BigInteger::class, '-5'],

            // All decimals
            [['1.5', '2.5', '0.5'], BigDecimal::class, '0.5'],
            [['-1.5', '0', '1.5'], BigDecimal::class, '-1.5'],

            // All rationals
            [['1/2', '1/3', '1/4'], BigRational::class, '1/4'],
            [['2/3', '3/4', '5/6'], BigRational::class, '2/3'],

            // Mixed integer and decimal -> decimal
            [[1, '1.5', 2], BigDecimal::class, '1'],
            [[5, '2.5', 3], BigDecimal::class, '2.5'],

            // Mixed integer and rational -> rational
            [[1, '1/2', 2], BigRational::class, '1/2'],
            [[5, '7/2', 3], BigRational::class, '3'],

            // Mixed decimal and rational -> rational
            [['1.5', '1/3', '2.5'], BigRational::class, '1/3'],

            // All three types -> rational
            [[1, '1.5', '1/4'], BigRational::class, '1/4'],
            [[5, '2.5', '10/3'], BigRational::class, '25/10'], // 2.5 as rational

            // Single value
            [[42], BigInteger::class, '42'],
            [['3.14'], BigDecimal::class, '3.14'],
            [['2/3'], BigRational::class, '2/3'],
        ];
    }

    /**
     * @param list<BigNumber|int|float|string> $values        The values to compare.
     * @param string                           $expectedClass The expected class name.
     * @param string                           $expectedValue The expected value.
     */
    #[DataProvider('providerMaximumOf')]
    public function testMaximumOf(array $values, string $expectedClass, string $expectedValue): void
    {
        $result = BigNumber::maximumOf(...$values);

        self::assertInstanceOf($expectedClass, $result);
        self::assertSame($expectedValue, (string) $result);
    }

    public static function providerMaximumOf(): array
    {
        return [
            // All integers
            [[1, 2, 3], BigInteger::class, '3'],
            [[3, 1, 2], BigInteger::class, '3'],
            [[-5, 0, 5], BigInteger::class, '5'],

            // All decimals
            [['1.5', '2.5', '0.5'], BigDecimal::class, '2.5'],
            [['-1.5', '0', '1.5'], BigDecimal::class, '1.5'],

            // All rationals
            [['1/2', '1/3', '1/4'], BigRational::class, '1/2'],
            [['2/3', '3/4', '5/6'], BigRational::class, '5/6'],

            // Mixed integer and decimal -> decimal
            [[1, '1.5', 2], BigDecimal::class, '2'],
            [[5, '2.5', 3], BigDecimal::class, '5'],

            // Mixed integer and rational -> rational
            [[1, '1/2', 2], BigRational::class, '2'],
            [[5, '7/2', 3], BigRational::class, '5'],

            // Mixed decimal and rational -> rational
            [['1.5', '1/3', '2.5'], BigRational::class, '25/10'], // 2.5 as rational

            // All three types -> rational
            [[1, '1.5', '1/4'], BigRational::class, '15/10'], // 1.5 as rational
            [[5, '2.5', '10/3'], BigRational::class, '5'],

            // Single value
            [[42], BigInteger::class, '42'],
            [['3.14'], BigDecimal::class, '3.14'],
            [['2/3'], BigRational::class, '2/3'],
        ];
    }

    public function testMinimumOfWithNoValuesThrows(): void
    {
        $this->expectException(NoValuesProvidedException::class);
        BigNumber::minimumOf();
    }

    public function testMaximumOfWithNoValuesThrows(): void
    {
        $this->expectException(NoValuesProvidedException::class);
        BigNumber::maximumOf();
    }

    /**
     * @param list<BigNumber|int|float|string> $values        The values to widen.
     * @param string                           $expectedClass The expected class name for all results.
     * @param list<string>                     $expectedValues The expected string values.
     */
    #[DataProvider('providerWiden')]
    public function testWiden(array $values, string $expectedClass, array $expectedValues): void
    {
        $results = BigNumber::widen(...$values);

        self::assertCount(count($expectedValues), $results);

        foreach ($results as $i => $result) {
            self::assertInstanceOf($expectedClass, $result);
            self::assertSame($expectedValues[$i], (string) $result);
        }
    }

    public static function providerWiden(): array
    {
        return [
            // All integers stay integers
            [[1, 2, 3], BigInteger::class, ['1', '2', '3']],
            [[-5, 0, 5], BigInteger::class, ['-5', '0', '5']],

            // All decimals stay decimals
            [['1.5', '2.5'], BigDecimal::class, ['1.5', '2.5']],

            // All rationals stay rationals
            [['1/2', '2/3'], BigRational::class, ['1/2', '2/3']],

            // Mixed integer and decimal -> all become decimal
            [[1, '1.5', 2], BigDecimal::class, ['1', '1.5', '2']],

            // Mixed integer and rational -> all become rational
            [[1, '1/2', 2], BigRational::class, ['1', '1/2', '2']],

            // Mixed decimal and rational -> all become rational
            [['1.5', '1/3'], BigRational::class, ['15/10', '1/3']],

            // All three types -> all become rational
            [[1, '1.5', '1/4'], BigRational::class, ['1', '15/10', '1/4']],

            // Single values
            [[42], BigInteger::class, ['42']],
            [['3.14'], BigDecimal::class, ['3.14']],
            [['2/3'], BigRational::class, ['2/3']],
        ];
    }

    public function testWidenWithNoValuesThrows(): void
    {
        $this->expectException(NoValuesProvidedException::class);
        BigNumber::widen();
    }
}
