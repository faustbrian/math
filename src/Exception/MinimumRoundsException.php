<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when the number of rounds is less than the minimum required.
 */
final class MinimumRoundsException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function atLeastOne(): self
    {
        return new self('The number of rounds must be at least 1.');
    }
}
