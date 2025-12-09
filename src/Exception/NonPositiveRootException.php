<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a non-positive root is provided.
 */
final class NonPositiveRootException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function notPositive(): self
    {
        return new self('The root must be positive.');
    }
}
