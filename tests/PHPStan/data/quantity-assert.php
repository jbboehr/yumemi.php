<?php

/**
 * TypeInferenceTestCase fixture for the Quantity<'...'> object path.
 *
 * Slice 1: Units::quantity() construction inference and Quantity<'...'> PHPDoc resolution.
 */

use jbboehr\Yumemi\Number\FloatRangePolicy;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;
use function jbboehr\Yumemi\unit;
use function PHPStan\Testing\assertType;

$units = Units::default();

// --- Units::quantity() construction ---

assertType("Quantity<'meter'>", $units->quantity(1, 'meter'));
assertType("Quantity<'meter / second'>", $units->quantity(1, 'meter / second'));
assertType("Quantity<'newton'>", $units->quantity(2, 'newton'));

// --- Units::parseQuantity() construction ---

assertType("Quantity<'meter'>", $units->parseQuantity('2 meter'));
assertType("Quantity<'international_foot'>", $units->parseQuantity('12 foot'));
assertType("Quantity<'meter / second'>", $units->parseQuantity('2 meter / (4 second)'));
assertType("Quantity<'meter ^ 2'>", $units->parseQuantity('(2 meter)^2'));
assertType("Quantity<'meter / second'>", $units->parseQuantity('meter / second'));
assertType("Quantity<'1'>", $units->parseQuantity('1000'));
assertType('*ERROR*', $units->parseQuantity('2 not_a_real_unit_xyz'));
assertType('*ERROR*', $units->parseQuantity('meter * / second'));
assertType('*ERROR*', $units->parseQuantity('2 B'));

function dynamicParsedQuantity(Units $units, string $expression): void
{
    assertType('jbboehr\\Yumemi\\Quantity', $units->parseQuantity($expression));
}

/** @param '2 meter'|'3 foot' $expression */
function finiteParsedQuantities(Units $units, string $expression): void
{
    assertType("Quantity<'international_foot'>|Quantity<'meter'>", $units->parseQuantity($expression));
}

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
$feet = $units->quantity(1, 'foot');

// mul / div combine units
assertType("Quantity<'meter * second'>", $m->mul($s));
assertType("Quantity<'meter / second'>", $m->div($s));

// scalar operand preserves the unit
assertType("Quantity<'meter'>", $m->mul(2));
assertType("Quantity<'meter'>", $m->div(2));

// pow raises by a constant integer
assertType("Quantity<'meter ^ 2'>", $m->pow(2));
assertType("Quantity<'1 / meter'>", $m->pow(-1));
assertType("Quantity<'meter ^ 10000'>", $m->pow(10_000));
assertType("Quantity<'1 / meter ^ 10000'>", $m->pow(-10_000));

// root extracts exact symbolic powers; runtime magnitude exactness is not part of the generic type
$squareMeters = $units->quantity(4, 'meter^2');
$squarePixels = $units->quantity(4, 'pixel^2');
assertType("Quantity<'meter'>", $squareMeters->root(2));
assertType("Quantity<'pixel'>", $squarePixels->root(2));
assertType("Quantity<'meter'>", $units->quantity(2, 'meter^2')->root(2));

// substitution remains explicit when different symbolic names form a perfect normalized power
$mixedLengthSquare = $units->quantity(1, 'kilometer * millimeter');
assertType('*ERROR*', $mixedLengthSquare->root(2));
assertType("Quantity<'meter'>", $mixedLengthSquare->simplify()->root(2));

// invalid constant degrees and unit powers are rejected statically
assertType('*ERROR*', $squareMeters->root(0));
assertType('*ERROR*', $m->root(2));
assertType('*ERROR*', $m->pow(10_001));
assertType('*ERROR*', $m->pow(-10_001));

/** @param Quantity<'meter ^ 2'> $area */
function rootWithDynamicDegree(Quantity $area, int $degree): void
{
    assertType(Quantity::class, $area->root($degree));
}

/** @param Quantity<'meter ^ 2'>|Quantity<'second ^ 2'> $quantity */
function rootReceiverUnion(Quantity $quantity): void
{
    assertType("Quantity<'meter'>|Quantity<'second'>", $quantity->root(2));
}

// neg keeps the unit; add / sub convert compatible operands and keep the left unit
assertType("Quantity<'meter'>", $m->neg());
assertType("Quantity<'meter'>", $m->add($m));
assertType("Quantity<'meter'>", $m->sub($m));
assertType("Quantity<'meter'>", $m->add($units->quantity(1, 'foot')));
assertType("Quantity<'meter'>", $m->sub($units->quantity(1, 'foot')));

// comparisons convert compatible operands and retain their native result types
assertType('-1|0|1', $m->compareTo($feet));
assertType('bool', $m->equals($feet));
assertType('bool', $m->lessThan($feet));
assertType('bool', $m->lessThanOrEqualTo($feet));
assertType('bool', $m->greaterThan($feet));
assertType('bool', $m->greaterThanOrEqualTo($feet));

// incompatible branded comparisons are statically invalid
assertType('*ERROR*', $m->compareTo($s));
assertType('*ERROR*', $m->equals($s));
assertType('*ERROR*', $m->lessThan($s));
assertType('*ERROR*', $m->lessThanOrEqualTo($s));
assertType('*ERROR*', $m->greaterThan($s));
assertType('*ERROR*', $m->greaterThanOrEqualTo($s));

// same-unit variants keep the left unit without conversion
assertType("Quantity<'meter'>", $m->addWithSameUnit($m));
assertType("Quantity<'meter'>", $m->subWithSameUnit($m));

// to() rebrands to the target unit (catalog spelling)
assertType("Quantity<'international_foot'>", $m->to('foot'));

// Conversion targets must share the receiver's dimension. Integer extractions carry the target unit.
assertType("unit_int<'international_foot'>", $m->intValueIn('foot'));
assertType("unit_int<'meter'>", $m->exactIntValueIn('meter'));
assertType('jbboehr\\Yumemi\\Number\\Rational', $m->valueIn('foot'));
assertType('string', $m->decimalValueIn('foot', 2, \RoundingMode::HalfEven));
assertType('string', $m->significantDecimalValueIn('foot', 3, \RoundingMode::HalfEven));
assertType('string', $m->exactDecimalValueIn('meter'));
assertType("unit_float<'international_foot'>", $m->floatValueIn('foot'));
assertType("unit_float<'international_foot'>", $m->floatValueIn('foot', FloatRangePolicy::Ieee754));
assertType('*ERROR*', $m->to('second'));
assertType('*ERROR*', $m->valueIn('second'));
assertType('*ERROR*', $m->intValueIn('second'));
assertType('*ERROR*', $m->exactIntValueIn('second'));
assertType('*ERROR*', $m->decimalValueIn('second', 2, \RoundingMode::HalfEven));
assertType('*ERROR*', $m->significantDecimalValueIn('second', 3, \RoundingMode::HalfEven));
assertType('*ERROR*', $m->exactDecimalValueIn('second'));
assertType('*ERROR*', $m->floatValueIn('second'));

// Unknown constant targets are invalid in the authoritative PHPStan registry.
assertType('*ERROR*', $m->to('not_a_real_unit_xyz'));
assertType('*ERROR*', $m->valueIn('not_a_real_unit_xyz'));
assertType('*ERROR*', $m->intValueIn('not_a_real_unit_xyz'));
assertType('*ERROR*', $m->exactIntValueIn('not_a_real_unit_xyz'));
assertType('*ERROR*', $m->decimalValueIn('not_a_real_unit_xyz', 2, \RoundingMode::HalfEven));
assertType('*ERROR*', $m->significantDecimalValueIn('not_a_real_unit_xyz', 3, \RoundingMode::HalfEven));
assertType('*ERROR*', $m->exactDecimalValueIn('not_a_real_unit_xyz'));
assertType('*ERROR*', $m->floatValueIn('not_a_real_unit_xyz'));

// An invalid conversion result is ErrorType: the diagnostic is emitted once at the offending call.
// Reusing it fails open — chained calls degrade to mixed rather than branding a bogus unit or crashing.
$invalidConversion = $m->to('second');
assertType('*ERROR*', $invalidConversion);
assertType('mixed', $invalidConversion->to('meter'));
assertType('mixed', $invalidConversion->mul($s));

/** @param 'meter'|'foot' $unit */
function finiteConversionTargets(Units $units, string $unit): void
{
    $meters = $units->quantity(1, 'meter');
    assertType("Quantity<'international_foot'>|Quantity<'meter'>", $meters->to($unit));
    assertType("unit_int<'international_foot'>|unit_int<'meter'>", $meters->intValueIn($unit));
    assertType("unit_int<'international_foot'>|unit_int<'meter'>", $meters->exactIntValueIn($unit));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $meters->valueIn($unit));
    assertType('string', $meters->decimalValueIn($unit, 2, \RoundingMode::HalfEven));
    assertType('string', $meters->significantDecimalValueIn($unit, 3, \RoundingMode::HalfEven));
    assertType('string', $meters->exactDecimalValueIn($unit));
    assertType("unit_float<'international_foot'>|unit_float<'meter'>", $meters->floatValueIn($unit));
}

/** @param 'meter'|'second' $unit */
function partlyIncompatibleConversionTargets(Units $units, string $unit): void
{
    $meters = $units->quantity(1, 'meter');
    assertType('*ERROR*', $meters->to($unit));
    assertType('*ERROR*', $meters->valueIn($unit));
    assertType('*ERROR*', $meters->intValueIn($unit));
    assertType('*ERROR*', $meters->exactIntValueIn($unit));
    assertType('*ERROR*', $meters->decimalValueIn($unit, 2, \RoundingMode::HalfEven));
    assertType('*ERROR*', $meters->significantDecimalValueIn($unit, 3, \RoundingMode::HalfEven));
    assertType('*ERROR*', $meters->exactDecimalValueIn($unit));
    assertType('*ERROR*', $meters->floatValueIn($unit));
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

function compareDynamic(\jbboehr\Yumemi\Quantity $q, \jbboehr\Yumemi\Units $units): void
{
    $meters = $units->quantity(1, 'meter');

    assertType('-1|0|1', $meters->compareTo($q));
    assertType('bool', $meters->equals($q));
    assertType('bool', $meters->lessThan($q));
    assertType('bool', $meters->lessThanOrEqualTo($q));
    assertType('bool', $meters->greaterThan($q));
    assertType('bool', $meters->greaterThanOrEqualTo($q));
}

function extractDynamic(\jbboehr\Yumemi\Quantity $q, string $unit): void
{
    assertType('int', $q->intValueIn($unit));
    assertType('int', $q->exactIntValueIn($unit));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $q->valueIn($unit));
    assertType('string', $q->decimalValueIn($unit, 2, \RoundingMode::HalfEven));
    assertType('string', $q->significantDecimalValueIn($unit, 3, \RoundingMode::HalfEven));
    assertType('string', $q->exactDecimalValueIn($unit));
    assertType('float', $q->floatValueIn($unit));
}

function extractKnownFromUnbranded(\jbboehr\Yumemi\Quantity $q): void
{
    assertType("Quantity<'international_foot'>", $q->to('foot'));
    assertType("unit_int<'international_foot'>", $q->intValueIn('foot'));
    assertType("unit_int<'meter'>", $q->exactIntValueIn('meter'));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $q->valueIn('foot'));
    assertType('string', $q->decimalValueIn('foot', 2, \RoundingMode::HalfEven));
    assertType('string', $q->significantDecimalValueIn('foot', 3, \RoundingMode::HalfEven));
    assertType('string', $q->exactDecimalValueIn('meter'));
    assertType("unit_float<'international_foot'>", $q->floatValueIn('foot'));
}

/** @param 'meter'|'foot' $unit */
function extractFiniteFromUnbranded(\jbboehr\Yumemi\Quantity $q, string $unit): void
{
    assertType("Quantity<'international_foot'>|Quantity<'meter'>", $q->to($unit));
    assertType("unit_int<'international_foot'>|unit_int<'meter'>", $q->intValueIn($unit));
    assertType("unit_int<'international_foot'>|unit_int<'meter'>", $q->exactIntValueIn($unit));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $q->valueIn($unit));
    assertType('string', $q->decimalValueIn($unit, 2, \RoundingMode::HalfEven));
    assertType('string', $q->significantDecimalValueIn($unit, 3, \RoundingMode::HalfEven));
    assertType('string', $q->exactDecimalValueIn($unit));
    assertType("unit_float<'international_foot'>|unit_float<'meter'>", $q->floatValueIn($unit));
}

function extractBrandedDynamic(\jbboehr\Yumemi\Units $units, string $unit): void
{
    $meters = $units->quantity(1, 'meter');
    assertType('jbboehr\\Yumemi\\Quantity', $meters->to($unit));
    assertType('int', $meters->intValueIn($unit));
    assertType('int', $meters->exactIntValueIn($unit));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $meters->valueIn($unit));
    assertType('string', $meters->decimalValueIn($unit, 2, \RoundingMode::HalfEven));
    assertType('string', $meters->significantDecimalValueIn($unit, 3, \RoundingMode::HalfEven));
    assertType('string', $meters->exactDecimalValueIn($unit));
    assertType('float', $meters->floatValueIn($unit));
}

/** @param Quantity<'international_foot'>|Quantity<'meter'> $length */
function compatibleQuantityReceiverUnion(Quantity $length): void
{
    assertType("Quantity<'international_inch'>", $length->to('inch'));
    assertType("Quantity<'international_foot'>|Quantity<'meter'>", $length->neg());
    assertType(
        "Quantity<'international_foot * second'>|Quantity<'meter * second'>",
        $length->mul(Units::default()->quantity(1, 'second')),
    );
}

/**
 * @param Quantity<'meter'>                                $length
 * @param Quantity<'international_foot'>|Quantity<'meter'> $other
 */
function compatibleQuantityOperandUnion(Quantity $length, Quantity $other): void
{
    assertType("Quantity<'meter'>", $length->add($other));
    assertType(
        "Quantity<'international_foot * meter'>|Quantity<'meter ^ 2'>",
        $length->mul($other),
    );
}
