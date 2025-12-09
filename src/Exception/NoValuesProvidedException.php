<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when no values are provided to a method that requires at least one.
 */
final class NoValuesProvidedException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function forMethod(string $method): self
    {
        return new self(sprintf('%s() expects at least one value.', $method));
    }
}
