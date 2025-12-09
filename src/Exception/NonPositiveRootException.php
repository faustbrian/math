<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a non-positive root is provided to a root operation.
 *
 * Root operations (cube root, nth root, etc.) require a positive integer for the root
 * parameter. This exception is thrown when attempting to use zero or negative values,
 * which are mathematically invalid for general root operations in this context.
 */
final class NonPositiveRootException extends RuntimeException implements MathException
{
    /**
     * Creates a new exception indicating that a non-positive root value was provided.
     *
     * @return self The exception instance with a descriptive message.
     *
     * @pure
     */
    public static function notPositive(): self
    {
        return new self('The root must be positive.');
    }
}
