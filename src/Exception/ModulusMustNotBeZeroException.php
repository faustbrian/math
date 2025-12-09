<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a modulus of zero is provided.
 */
final class ModulusMustNotBeZeroException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function zero(): self
    {
        return new self('The modulus must not be zero.');
    }
}
