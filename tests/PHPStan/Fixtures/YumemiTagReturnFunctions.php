<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

/**
 * Fixture functions for the extension-optional @yumemi-return tag.
 *
 * These are `require`d into the test process (not analysed as a data file) so that native function
 * reflection can see them: TypeInferenceTestCase does not index function declarations local to the
 * analysed fixture, but it does resolve functions that already exist in the process. Each keeps a
 * native return type in its signature; @yumemi-return brands the result only when tag promotion is enabled.
 */

/**
 * @yumemi-return unit_int<'foot'>
 */
function measuredFeet(): int
{
    return 3;
}

/**
 * @return int<0, 100>
 * @yumemi-return unit_int<'second'>&int<0, 100>
 */
function boundedDuration(): int
{
    return 50;
}

/**
 * @return 3
 * @yumemi-return 3&unit_int<'meter'>
 */
function constantLength(): int
{
    return 3;
}

/**
 * @return float
 *
 * @yumemi-return unit_float<'meter / second'>
 */
function currentSpeed(): float
{
    return 1.0;
}

/**
 * @yumemi-return Quantity<'newton'>
 */
function appliedForce(Units $units): Quantity
{
    return $units->quantity(1, 'newton');
}

/**
 * @yumemi-return PointQuantity<'celsius'>
 */
function freezingPoint(Units $units): PointQuantity
{
    return $units->point(0, 'celsius');
}

// No tag → native return type is unchanged.
function plainLength(): int
{
    return 1;
}

/**
 * Invalid unit in the tag → treated as absent, native type preserved (no poisoning).
 *
 * @yumemi-return unit_int<'not_a_real_unit_xyz'>
 */
function bogusUnit(): int
{
    return 1;
}

/**
 * Trailing prose is parsed as the promoted return tag's description.
 *
 * @yumemi-return unit_int<'foot'> the height above ground
 */
function withProse(): int
{
    return 1;
}

/**
 * @return array<string, int|null>
 * @yumemi-return array<string, unit_int<'second'>|null>
 */
function durations(): array
{
    return ['request' => 1];
}

/**
 * @return mixed ordinary fallback
 * @phpstan-return int preferred fallback
 * @yumemi-return unit_int<'meter'>
 */
function phpstanFallbackWins(): int
{
    return 1;
}

/**
 * @return int fallback remains effective
 * @yumemi-return unit_float<'meter'>
 */
function mismatchedFallback(): int
{
    return 1;
}

final class TaggedProperties
{
    /**
     * @var int fallback description
     * @yumemi-var unit_int<'meter'>
     */
    public int $length = 1;
}
