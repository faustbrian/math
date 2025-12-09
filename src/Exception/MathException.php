<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use Throwable;

/**
 * Marker interface for all math exceptions.
 *
 * Consumers can catch this interface to handle any exception
 * thrown by the brick/math package.
 */
interface MathException extends Throwable
{
}
