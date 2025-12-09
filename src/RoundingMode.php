<?php

declare(strict_types=1);

namespace Brick\Math;

/**
 * Defines rounding behaviors for numerical operations that discard precision.
 *
 * Each rounding mode specifies how to calculate the least significant digit of a rounded
 * result when the exact value cannot be represented at the desired precision. The digits
 * that cannot be represented are called the "discarded fraction" regardless of their
 * contribution to the overall value.
 *
 * Important: The discarded fraction refers to the digits being removed, not their numerical
 * value. For example, when rounding 12.99 to the nearest integer, the discarded fraction
 * is "99" (which is greater than 0.5), even though its absolute value as a number is less
 * than one.
 *
 * Usage Context:
 * These rounding modes are used throughout Brick\Math for operations like division with
 * limited precision, scale reduction, and conversion operations where exact representation
 * is not possible.
 */
enum RoundingMode
{
    /**
     * Requires exact results with no rounding.
     *
     * When this mode is used, the operation must produce a result that can be exactly
     * represented at the requested precision. If any rounding would be necessary, a
     * RoundingNecessaryException is thrown instead.
     *
     * Use this mode when precision loss is unacceptable and you want to detect cases
     * where exact representation is impossible.
     */
    case UNNECESSARY;

    /**
     * Rounds away from zero (towards infinity in absolute value).
     *
     * Always increments the digit prior to any nonzero discarded fraction, increasing
     * the magnitude. Positive numbers round up, negative numbers round down (more negative).
     *
     * Examples: 1.1 → 2, 1.9 → 2, -1.1 → -2, -1.9 → -2
     *
     * Note: This mode never decreases the magnitude of the calculated value.
     */
    case UP;

    /**
     * Rounds towards zero (truncation).
     *
     * Never increments the digit prior to a discarded fraction, effectively truncating
     * the number. This always decreases the magnitude, rounding towards zero.
     *
     * Examples: 1.1 → 1, 1.9 → 1, -1.1 → -1, -1.9 → -1
     *
     * Note: This mode never increases the magnitude of the calculated value.
     */
    case DOWN;

    /**
     * Rounds towards positive infinity (ceiling).
     *
     * For positive numbers, behaves like UP. For negative numbers, behaves like DOWN.
     * Always rounds in the direction of positive infinity.
     *
     * Examples: 1.1 → 2, 1.9 → 2, -1.1 → -1, -1.9 → -1
     *
     * Note: This mode never decreases the calculated value.
     */
    case CEILING;

    /**
     * Rounds towards negative infinity (floor).
     *
     * For positive numbers, behaves like DOWN. For negative numbers, behaves like UP.
     * Always rounds in the direction of negative infinity.
     *
     * Examples: 1.1 → 1, 1.9 → 1, -1.1 → -2, -1.9 → -2
     *
     * Note: This mode never increases the calculated value.
     */
    case FLOOR;

    /**
     * Rounds to nearest neighbor, ties round away from zero.
     *
     * Rounds to the nearest value. When exactly halfway between two values (e.g., 1.5),
     * rounds away from zero (like UP). This is the rounding mode commonly taught in schools.
     *
     * Examples: 1.4 → 1, 1.5 → 2, 1.6 → 2, -1.5 → -2
     *
     * Behavior: UP if discarded fraction ≥ 0.5, otherwise DOWN.
     */
    case HALF_UP;

    /**
     * Rounds to nearest neighbor, ties round towards zero.
     *
     * Rounds to the nearest value. When exactly halfway between two values (e.g., 1.5),
     * rounds towards zero (like DOWN).
     *
     * Examples: 1.4 → 1, 1.5 → 1, 1.6 → 2, -1.5 → -1
     *
     * Behavior: UP if discarded fraction > 0.5, otherwise DOWN.
     */
    case HALF_DOWN;

    /**
     * Rounds to nearest neighbor, ties round towards positive infinity.
     *
     * Rounds to the nearest value. When exactly halfway between two values, rounds
     * towards positive infinity (combines HALF_UP for positive, HALF_DOWN for negative).
     *
     * Examples: 1.5 → 2, -1.5 → -1
     *
     * Behavior: For positive, like HALF_UP; for negative, like HALF_DOWN.
     */
    case HALF_CEILING;

    /**
     * Rounds to nearest neighbor, ties round towards negative infinity.
     *
     * Rounds to the nearest value. When exactly halfway between two values, rounds
     * towards negative infinity (combines HALF_DOWN for positive, HALF_UP for negative).
     *
     * Examples: 1.5 → 1, -1.5 → -2
     *
     * Behavior: For positive, like HALF_DOWN; for negative, like HALF_UP.
     */
    case HALF_FLOOR;

    /**
     * Rounds to nearest neighbor, ties round to even (Banker's rounding).
     *
     * Rounds to the nearest value. When exactly halfway between two values, rounds
     * to the nearest even number. This statistically minimizes cumulative error when
     * applied repeatedly over many calculations.
     *
     * Examples: 1.5 → 2, 2.5 → 2, 3.5 → 4, -1.5 → -2, -2.5 → -2
     *
     * Also known as "Banker's rounding" or "unbiased rounding". Preferred in financial
     * and scientific applications where cumulative rounding error matters.
     *
     * Behavior: HALF_UP if left digit is odd, HALF_DOWN if left digit is even.
     */
    case HALF_EVEN;
}
