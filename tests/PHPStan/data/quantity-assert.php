<?php

/**
 * TypeInferenceTestCase fixture for the Quantity<'...'> object path.
 *
 * Slice 1: Units::quantity() construction inference and Quantity<'...'> PHPDoc resolution.
 */

use jbboehr\IudexMensurarumMysteriorum\Units;
use function PHPStan\Testing\assertType;

$units = Units::default();

// --- Units::quantity() construction ---

assertType("Quantity<'meter'>", $units->quantity(1, 'meter'));
assertType("Quantity<'meter / second'>", $units->quantity(1, 'meter / second'));
assertType("Quantity<'newton'>", $units->quantity(2, 'newton'));

// Unknown-in-default-catalog unit → fail open to native Quantity (the instance may hold a
// custom registry where this unit is valid), never a poisoning error.
assertType('jbboehr\\IudexMensurarumMysteriorum\\Quantity', $units->quantity(1, 'not_a_real_unit_xyz'));

// non-constant unit string → native Quantity fallback (not branded)
function dynamicQuantity(Units $units, string $u): void
{
    assertType('jbboehr\\IudexMensurarumMysteriorum\\Quantity', $units->quantity(1, $u));
}

// --- Quantity<'...'> PHPDoc resolution ---

/** @var \jbboehr\IudexMensurarumMysteriorum\Quantity<'meter / second'> $speed */
$speed = $units->quantity(1, 'meter / second');
assertType("Quantity<'meter / second'>", $speed);
