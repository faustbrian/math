<?php

declare(strict_types=1);

namespace Brick\Math\Internal;

use function extension_loaded;

/**
 * Central registry for Calculator implementation selection and caching.
 *
 * This singleton-style registry manages the Calculator instance used throughout the
 * Brick\Math library. It automatically detects and selects the fastest available
 * implementation based on installed PHP extensions.
 *
 * Selection Priority:
 * 1. GMP extension (fastest, preferred)
 * 2. BCMath extension (fast, good alternative)
 * 3. Native PHP (slowest, guaranteed fallback)
 *
 * The selected implementation is cached for the duration of the request to avoid
 * repeated detection overhead. Manual override is available primarily for testing.
 *
 * @internal
 */
final class CalculatorRegistry
{
    /**
     * The cached Calculator instance.
     *
     * Null until first access, then populated by autodetection or manual set().
     * This static property provides request-level caching of the selected implementation.
     *
     * @var Calculator|null
     */
    private static ?Calculator $instance = null;

    /**
     * Manually sets the Calculator implementation to use.
     *
     * This method is primarily intended for unit testing to force a specific
     * implementation. In production, autodetection (via get()) is recommended
     * as it selects the optimal implementation for the environment.
     *
     * @param Calculator|null $calculator The calculator instance to use, or null to reset to autodetect
     * @return void
     */
    final public static function set(?Calculator $calculator): void
    {
        self::$instance = $calculator;
    }

    /**
     * Returns the Calculator instance, autodetecting if needed.
     *
     * On first call, automatically detects the fastest available implementation
     * based on loaded PHP extensions. Subsequent calls return the cached instance.
     * This lazy initialization pattern avoids detection overhead until actually needed.
     *
     * Note: While this method modifies static state on first call, it is marked
     * pure for static analysis because it behaves deterministically in normal usage
     * (without explicit set() calls).
     *
     * @return Calculator The Calculator instance to use for all operations
     *
     * @pure
     */
    final public static function get(): Calculator
    {
        /** @phpstan-ignore impure.staticPropertyAccess */
        if (self::$instance === null) {
            /** @phpstan-ignore impure.propertyAssign */
            self::$instance = self::detect();
        }

        /** @phpstan-ignore impure.staticPropertyAccess */
        return self::$instance;
    }

    /**
     * Detects and returns the fastest available Calculator implementation.
     *
     * Checks for PHP extensions in order of performance: GMP (fastest), BCMath
     * (fast), then falls back to NativeCalculator (pure PHP, slowest). This
     * method is called once per request to initialize the cached instance.
     *
     * @return Calculator The best available Calculator implementation
     *
     * @pure
     *
     * @codeCoverageIgnore
     */
    private static function detect(): Calculator
    {
        if (extension_loaded('gmp')) {
            return new Calculator\GmpCalculator();
        }

        if (extension_loaded('bcmath')) {
            return new Calculator\BcMathCalculator();
        }

        return new Calculator\NativeCalculator();
    }
}
