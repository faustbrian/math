<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to perform an unsupported operation, such as a square root, on a negative number.
 *
 * @deprecated Use specific exception classes instead:
 *             - NegativeSquareRootException
 *             - NegativeModulusException
 *             - NegativeOperandException
 *             - NegativePrimeSearchException
 *             - NegativeArbitraryBaseException
 *             - NegativeByteConversionException
 *             - EvenRootOfNegativeException
 */
final class NegativeNumberException extends RuntimeException implements MathException
{
}
