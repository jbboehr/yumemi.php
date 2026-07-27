<?php

/**
 * TypeInferenceTestCase fixture for the Quantity<'...'> object path.
 *
 * Slice 1: Units::quantity() construction inference and Quantity<'...'> PHPDoc resolution.
 */

use jbboehr\Yumemi\Units;
use function jbboehr\Yumemi\unit;
use function PHPStan\Testing\assertType;

$units = Units::default();

// --- Units::quantity() construction ---

assertType("Quantity<'meter'>", $units->quantity(1, 'meter'));
assertType("Quantity<'meter / second'>", $units->quantity(1, 'meter / second'));
assertType("Quantity<'newton'>", $units->quantity(2, 'newton'));

// A branded native integer is already expressed in its branded unit: quantity() does not convert it.
$nativeFeet = unit(3, 'foot');
assertType("Quantity<'international_foot'>", $units->quantity($nativeFeet, 'foot'));
assertType('*ERROR*', $units->quantity($nativeFeet, 'meter'));

$nativeKilometers = unit(2, 'kilometer');
assertType("Quantity<'1000 * meter'>", $units->quantity($nativeKilometers, '1000 * meter'));

// The configured PHPStan registry is authoritative for statically known unit strings.
assertType('*ERROR*', $units->quantity(1, 'not_a_real_unit_xyz'));

// non-constant unit string → native Quantity fallback (not branded)
function dynamicQuantity(Units $units, string $u): void
{
    assertType('jbboehr\\Yumemi\\Quantity', $units->quantity(1, $u));
}

function dynamicBrandedQuantity(Units $units, string $u): void
{
    assertType('jbboehr\\Yumemi\\Quantity', $units->quantity(unit(1, 'meter'), $u));
}

/** @param 'meter'|'foot' $unit */
function finiteQuantityTargets(Units $units, string $unit): void
{
    assertType("Quantity<'international_foot'>|Quantity<'meter'>", $units->quantity(1, $unit));
    assertType('*ERROR*', $units->quantity(unit(1, 'meter'), $unit));
}

/** @param 'foot'|'international_foot' $unit */
function equivalentQuantityTargets(Units $units, string $unit): void
{
    assertType("Quantity<'international_foot'>", $units->quantity(unit(1, 'foot'), $unit));
}

// --- Quantity<'...'> PHPDoc resolution ---

/** @var \jbboehr\Yumemi\Quantity<'meter / second'> $speed */
$speed = $units->quantity(1, 'meter / second');
assertType("Quantity<'meter / second'>", $speed);

// --- Slice 2: fluent method inference ---

$m = $units->quantity(1, 'meter');
$s = $units->quantity(1, 'second');
$km = $units->quantity(1, 'kilometer');
$centimetersPerSecond = $units->quantity(1, 'centimeter / second');
$newtons = $units->quantity(1, 'newton');
$percent = $units->quantity(1, 'percent');

// mul / div combine units
assertType("Quantity<'meter * second'>", $m->mul($s));
assertType("Quantity<'meter / second'>", $m->div($s));

// scalar operand preserves the unit
assertType("Quantity<'meter'>", $m->mul(2));
assertType("Quantity<'meter'>", $m->div(2));

// pow raises by a constant integer
assertType("Quantity<'meter ^ 2'>", $m->pow(2));
assertType("Quantity<'1 / meter'>", $m->pow(-1));

// neg keeps the unit; add / sub convert compatible operands and keep the left unit
assertType("Quantity<'meter'>", $m->neg());
assertType("Quantity<'meter'>", $m->add($m));
assertType("Quantity<'meter'>", $m->sub($m));
assertType("Quantity<'meter'>", $m->add($units->quantity(1, 'foot')));
assertType("Quantity<'meter'>", $m->sub($units->quantity(1, 'foot')));

// same-unit variants keep the left unit without conversion
assertType("Quantity<'meter'>", $m->addWithSameUnit($m));
assertType("Quantity<'meter'>", $m->subWithSameUnit($m));

// to() rebrands to the target unit (catalog spelling)
assertType("Quantity<'international_foot'>", $m->to('foot'));

// Conversion targets must share the receiver's dimension. Integer extractions carry the target unit.
assertType("unit_int<'international_foot'>", $m->intValueIn('foot'));
assertType("unit_int<'meter'>", $m->exactIntValueIn('meter'));
assertType('jbboehr\\Yumemi\\Number\\Rational', $m->valueIn('foot'));
assertType('*ERROR*', $m->to('second'));
assertType('*ERROR*', $m->valueIn('second'));
assertType('*ERROR*', $m->intValueIn('second'));
assertType('*ERROR*', $m->exactIntValueIn('second'));

// Unknown constant targets are invalid in the authoritative PHPStan registry.
assertType('*ERROR*', $m->to('not_a_real_unit_xyz'));
assertType('*ERROR*', $m->valueIn('not_a_real_unit_xyz'));
assertType('*ERROR*', $m->intValueIn('not_a_real_unit_xyz'));
assertType('*ERROR*', $m->exactIntValueIn('not_a_real_unit_xyz'));

/** @param 'meter'|'foot' $unit */
function finiteConversionTargets(Units $units, string $unit): void
{
    $meters = $units->quantity(1, 'meter');
    assertType("Quantity<'international_foot'>|Quantity<'meter'>", $meters->to($unit));
    assertType("unit_int<'international_foot'>|unit_int<'meter'>", $meters->intValueIn($unit));
    assertType("unit_int<'international_foot'>|unit_int<'meter'>", $meters->exactIntValueIn($unit));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $meters->valueIn($unit));
}

/** @param 'meter'|'second' $unit */
function partlyIncompatibleConversionTargets(Units $units, string $unit): void
{
    $meters = $units->quantity(1, 'meter');
    assertType('*ERROR*', $meters->to($unit));
    assertType('*ERROR*', $meters->valueIn($unit));
    assertType('*ERROR*', $meters->intValueIn($unit));
    assertType('*ERROR*', $meters->exactIntValueIn($unit));
}

// normalize() rebrands to the catalog-normalized form
assertType("Quantity<'1000 * meter'>", $km->normalize());

// simplify() folds the normalized scale into the runtime magnitude and keeps only unit factors
assertType("Quantity<'meter'>", $km->simplify());
assertType("Quantity<'meter / second'>", $centimetersPerSecond->simplify());
assertType("Quantity<'kilogram * meter / second ^ 2'>", $newtons->simplify());
assertType("Quantity<'1'>", $percent->simplify());

// chains compose
assertType("Quantity<'meter / second'>", $m->div($s)->mul($s)->div($s));
assertType("Quantity<'meter'>", $km->simplify()->add($m));

// unbranded-quantity operand → native fallback (cannot compute unit)
function combineDynamic(\jbboehr\Yumemi\Quantity $q, \jbboehr\Yumemi\Units $units): void
{
    $m = $units->quantity(1, 'meter');
    assertType('jbboehr\\Yumemi\\Quantity', $m->mul($q));
}

function extractDynamic(\jbboehr\Yumemi\Quantity $q, string $unit): void
{
    assertType('int', $q->intValueIn($unit));
    assertType('int', $q->exactIntValueIn($unit));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $q->valueIn($unit));
}

function extractKnownFromUnbranded(\jbboehr\Yumemi\Quantity $q): void
{
    assertType("Quantity<'international_foot'>", $q->to('foot'));
    assertType("unit_int<'international_foot'>", $q->intValueIn('foot'));
    assertType("unit_int<'meter'>", $q->exactIntValueIn('meter'));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $q->valueIn('foot'));
}

/** @param 'meter'|'foot' $unit */
function extractFiniteFromUnbranded(\jbboehr\Yumemi\Quantity $q, string $unit): void
{
    assertType("Quantity<'international_foot'>|Quantity<'meter'>", $q->to($unit));
    assertType("unit_int<'international_foot'>|unit_int<'meter'>", $q->intValueIn($unit));
    assertType("unit_int<'international_foot'>|unit_int<'meter'>", $q->exactIntValueIn($unit));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $q->valueIn($unit));
}

function extractBrandedDynamic(\jbboehr\Yumemi\Units $units, string $unit): void
{
    $meters = $units->quantity(1, 'meter');
    assertType('jbboehr\\Yumemi\\Quantity', $meters->to($unit));
    assertType('int', $meters->intValueIn($unit));
    assertType('int', $meters->exactIntValueIn($unit));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $meters->valueIn($unit));
}
