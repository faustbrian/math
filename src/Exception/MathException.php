<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use Throwable;

/**
 * Marker interface for all math exceptions.
 *
 * This interface provides a common type for all exceptions thrown by the brick/math
 * package, allowing consumers to catch and handle all math-related exceptions with
 * a single catch block. All exception classes in this package implement this interface.
 *
 * ```php
 * try {
 *     $result = BigInteger::of('invalid');
 * } catch (MathException $e) {
 *     // Handle any math exception
 * }
 * ```
 *
 * Specific exception types can be caught individually for fine-grained error handling,
 * or this interface can be used to catch all math exceptions at once.
 *
 * @api
 */
interface MathException extends Throwable
{
}
