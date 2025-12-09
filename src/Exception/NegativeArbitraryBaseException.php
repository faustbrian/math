<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to convert a negative number to an arbitrary base.
 */
final class NegativeArbitraryBaseException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function negative(): self
    {
        return new self('toArbitraryBase() does not support negative numbers.');
    }
}
