<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to perform an unsupported operation on a negative number.
 *
 * This is a legacy exception class that was originally used for various operations
 * that don't support negative numbers, such as square roots, modular operations,
 * and number theory functions. It has been superseded by more specific exception
 * classes that provide clearer error context for different scenarios.
 *
 * @deprecated Use specific exception classes instead for better error handling:
 *             - NegativeSquareRootException for square root of negative numbers
 *             - NegativeModulusException for negative modulus in modular operations
 *             - NegativeOperandException for operations requiring non-negative operands
 *             - NegativePrimeSearchException for prime number operations on negative values
 *             - NegativeArbitraryBaseException for base conversion of negative numbers
 *             - NegativeByteConversionException for unsigned byte conversion of negative numbers
 *             - EvenRootOfNegativeException for even roots of negative numbers
 */
final class NegativeNumberException extends RuntimeException implements MathException
{
}
