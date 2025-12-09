<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a rational number denominator of zero is provided.
 */
final class DenominatorMustNotBeZeroException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function zero(): self
    {
        return new self('The denominator of a rational number cannot be zero.');
    }
}
