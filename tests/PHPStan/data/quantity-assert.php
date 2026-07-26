<?php

/**
 * TypeInferenceTestCase fixture for the Quantity<'...'> object path.
 *
 * Slice 1: Units::quantity() construction inference and Quantity<'...'> PHPDoc resolution.
 */

use jbboehr\Yumemi\Units;
use function PHPStan\Testing\assertType;

$units = Units::default();

// --- Units::quantity() construction ---

assertType("Quantity<'meter'>", $units->quantity(1, 'meter'));
assertType("Quantity<'meter / second'>", $units->quantity(1, 'meter / second'));
assertType("Quantity<'newton'>", $units->quantity(2, 'newton'));

// Unknown-in-default-catalog unit → fail open to native Quantity (the instance may hold a
// custom registry where this unit is valid), never a poisoning error.
assertType('jbboehr\\Yumemi\\Quantity', $units->quantity(1, 'not_a_real_unit_xyz'));

// non-constant unit string → native Quantity fallback (not branded)
function dynamicQuantity(Units $units, string $u): void
{
    assertType('jbboehr\\Yumemi\\Quantity', $units->quantity(1, $u));
}

// --- Quantity<'...'> PHPDoc resolution ---

/** @var \jbboehr\Yumemi\Quantity<'meter / second'> $speed */
$speed = $units->quantity(1, 'meter / second');
assertType("Quantity<'meter / second'>", $speed);

// --- Slice 2: fluent method inference ---

$m = $units->quantity(1, 'meter');
$s = $units->quantity(1, 'second');
$km = $units->quantity(1, 'kilometer');

// mul / div combine units
assertType("Quantity<'meter * second'>", $m->mul($s));
assertType("Quantity<'meter / second'>", $m->div($s));

// scalar operand preserves the unit
assertType("Quantity<'meter'>", $m->mul(2));
assertType("Quantity<'meter'>", $m->div(2));

// pow raises by a constant integer
assertType("Quantity<'meter ^ 2'>", $m->pow(2));
assertType("Quantity<'1 / meter'>", $m->pow(-1));

// neg / add / sub keep the left unit
assertType("Quantity<'meter'>", $m->neg());
assertType("Quantity<'meter'>", $m->add($m));
assertType("Quantity<'meter'>", $m->sub($m));

// to() rebrands to the target unit (catalog spelling)
assertType("Quantity<'international_foot'>", $m->to('foot'));

// normalize() rebrands to the catalog-normalized form
assertType("Quantity<'1000 * meter'>", $km->normalize());

// chains compose
assertType("Quantity<'meter / second'>", $m->div($s)->mul($s)->div($s));

// unbranded-quantity operand → native fallback (cannot compute unit)
function combineDynamic(\jbboehr\Yumemi\Quantity $q, \jbboehr\Yumemi\Units $units): void
{
    $m = $units->quantity(1, 'meter');
    assertType('jbboehr\\Yumemi\\Quantity', $m->mul($q));
}
