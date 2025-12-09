<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use LogicException;

/**
 * Exception thrown when __unserialize() is called directly.
 *
 * The __unserialize() method is a magic method intended for internal use by PHP's
 * serialization mechanism. This exception is thrown when the method is called directly
 * by user code, which is not supported and indicates incorrect usage of the API.
 */
final class UnserializeCalledException extends LogicException implements MathException
{
    /**
     * Creates a new exception indicating that __unserialize() was called directly.
     *
     * @return self The exception instance with a descriptive message.
     *
     * @pure
     */
    public static function calledDirectly(): self
    {
        return new self('__unserialize() is an internal function, it must not be called directly.');
    }
}
