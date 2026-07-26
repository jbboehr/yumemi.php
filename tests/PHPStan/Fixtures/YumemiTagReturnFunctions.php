<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

/**
 * Fixture functions for the extension-optional @yumemi-return tag.
 *
 * These are `require`d into the test process (not analysed as a data file) so that native function
 * reflection can see them: TypeInferenceTestCase does not index function declarations local to the
 * analysed fixture, but it does resolve functions that already exist in the process. Each keeps a
 * native return type in its signature; @yumemi-return brands the result only when the extension runs.
 */

/**
 * @yumemi-return unit_int<'foot'>
 */
function measuredFeet(): int
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
 * Trailing prose after the type → unparseable payload, treated as absent.
 *
 * @yumemi-return unit_int<'foot'> the height above ground
 */
function withProse(): int
{
    return 1;
}
