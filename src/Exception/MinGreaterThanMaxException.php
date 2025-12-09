<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when $min is greater than $max in a range operation.
 */
final class MinGreaterThanMaxException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function inRandomRange(): self
    {
        return new self('$min cannot be greater than $max.');
    }
}
