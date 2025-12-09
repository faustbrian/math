<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use LogicException;

/**
 * Exception thrown when __unserialize() is called directly.
 */
final class UnserializeCalledException extends LogicException implements MathException
{
    /**
     * @pure
     */
    public static function calledDirectly(): self
    {
        return new self('__unserialize() is an internal function, it must not be called directly.');
    }
}
