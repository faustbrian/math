<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when no values are provided to a method that requires at least one.
 *
 * This exception is typically thrown by aggregate methods like min(), max(), or sum()
 * when called with an empty array or no arguments, where at least one value is required
 * to perform the calculation.
 */
final class NoValuesProvidedException extends RuntimeException implements MathException
{
    /**
     * Creates a new exception indicating that a method was called without required values.
     *
     * @param string $method The name of the method that was called without values.
     *
     * @return self The exception instance with a message specifying the method name.
     *
     * @pure
     */
    public static function forMethod(string $method): self
    {
        return new self(sprintf('%s() expects at least one value.', $method));
    }
}
