<?php

use jbboehr\Yumemi\Units;
use jbboehr\Yumemi\Tests\PHPStan\Fixtures\NativeUnitExpressionConstants;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_factor;
use function jbboehr\Yumemi\unit_to;
use function PHPStan\Testing\assertType;

const YUMEMI_TEST_UNIT_PREFIX = 'me';

// --- unit() ---

assertType("unit_float<'meter'>", unit(1.5, 'meter'));
assertType("unit_float<'meter'>", unit(1.5, NativeUnitExpressionConstants::DISTANCE));
assertType("unit_float<'meter'>", unit(1.5, YUMEMI_TEST_UNIT_PREFIX . 'ter'));
assertType("unit_float<'meter'>", unit(unit: 'meter', value: 1.5));
assertType("3&unit_int<'second'>", unit(3, 'second'));
assertType("1&unit_int<'kilogram * meter / second'>", unit(1, 'meter / second kilogram'));
assertType("unit_float<'kilogram * meter / second ^ 2'>", unit(1500.0, 'kilogram') * unit(3.0, 'meter / second^2'));
assertType("1&unit_int<'pixel'>", unit(1, 'pixels'));
assertType("unit_float<'pixel ^ 2'>", unit(3.0, 'pixel') * unit(4.0, 'pixel'));
assertType("unit_float<'pixel / international_inch'>", unit(96.0, 'pixel / inch'));
assertType("unit_float<'english_metric_unit'>", unit(1.0, 'EMU'));
assertType("unit_float<'typographic_point'>", unit_to(96.0, 'css_pixel', 'typographic_point'));
assertType('*ERROR*', unit(1, 'pixel') + unit(1, 'css_pixel'));
assertType('*ERROR*', unit(1, 'px'));
assertType('*ERROR*', unit(1.0, 'not_a_real_unit_xyz'));

/** @param int<0, 100> $value */
function brandBoundedInteger(int $value): void
{
    $length = unit($value, 'meter');
    assertType("unit_int<'meter'>&int<0, 100>", $length);
    assertType("unit_int<'meter'>&int<2, 102>", $length + unit(2, 'meter'));
}

/** @param unit_int<'meter'>&int<0, 100> $value */
function narrowBoundedUnitInteger(int $value): void
{
    assertType("unit_int<'meter'>&int<0, 100>", $value);

    if ($value > unit(50, 'meter')) {
        assertType("unit_int<'meter'>&int<51, 100>", $value);
    }
}

/** @param unit_int<'meter'>&int<0, 100> $value */
function useBoundedIntegerAtRuntimeBoundaries(int $value): void
{
    assertType("unit_float<'international_foot'>", unit_to($value, 'meter', 'foot'));
    assertType("Quantity<'meter'>", Units::default()->quantity($value, 'meter'));
}

/** @param unit_int<'meter'>|unit_int<'second'> $value */
function finiteNativeUnitArithmetic(int $value): void
{
    assertType(
        "unit_int<'meter * second'>|unit_int<'second ^ 2'>",
        $value * unit(1, 'second'),
    );
    assertType('*ERROR*', $value + unit(1, 'meter'));
}

/** @param 'meter'|'foot' $unit */
function finiteUnits(string $unit): void
{
    assertType("1&unit_int<'international_foot'>|1&unit_int<'meter'>", unit(1, $unit));
    assertType("unit_float<'international_foot'>|unit_float<'meter'>", unit(1.0, $unit));
}

/** @param 'meter / second'|'kilogram' $unit */
function finiteCompoundUnits(string $unit): void
{
    assertType("1&unit_int<'kilogram'>|1&unit_int<'meter / second'>", unit(1, $unit));
}

/** @param 'foot'|'international_foot' $unit */
function finiteEquivalentUnits(string $unit): void
{
    assertType("1&unit_int<'international_foot'>", unit(1, $unit));
}

/** @param '100 * centimeter'|'meter' $unit */
function finiteDefinitionallyEquivalentUnits(string $unit): void
{
    assertType("1&unit_int<'meter'>", unit(1, $unit));
}

/** @param 'meter'|'not_a_real_unit_xyz' $unit */
function partlyInvalidFiniteUnits(string $unit): void
{
    assertType('*ERROR*', unit(1, $unit));
}

/** @param 'meter'|'meter /' $unit */
function partlyMalformedFiniteUnits(string $unit): void
{
    assertType('*ERROR*', unit(1.0, $unit));
}

function dynamicUnits(string $unit): void
{
    assertType('int', unit(1, $unit));
    assertType('float', unit(1.0, $unit));
}

function mixedNativeMagnitude(int|float $value): void
{
    assertType("unit_float<'meter'>", unit($value, 'meter'));
}

// --- unit_factor(): quotient brand and cancellation through ordinary unit algebra ---

assertType("unit_float<'international_foot / meter'>", unit_factor('meter', 'foot'));
assertType("unit_float<'international_foot'>", unit(1, 'meter') * unit_factor('meter', 'foot'));
assertType("unit_float<'international_foot'>", unit_factor('meter', 'foot') * unit(1, 'meter'));
assertType("unit_float<'meter'>", unit(1.0, 'foot') * unit_factor('foot', 'meter'));
assertType("unit_float<'1000 * meter / hour'>", unit(1, 'meter / second') * unit_factor(
    'meter / second',
    'kilometer / hour',
));
assertType("unit_float<'1'>", unit_factor('meter', 'meter'));
assertType("unit_float<'meter'>", unit(1, 'meter') * unit_factor('meter', 'meter'));
assertType("unit_float<'1/100'>", unit_factor('meter', 'centimeter'));
assertType("unit_float<'1/100 * meter'>", unit(1, 'meter') * unit_factor('meter', 'centimeter'));

/** @param 'meter'|'foot' $from */
function finiteFactorSource(string $from): void
{
    assertType("unit_float<'1'>|unit_float<'meter / international_foot'>", unit_factor($from, 'meter'));
}

/** @param 'meter'|'foot' $to */
function finiteFactorTarget(string $to): void
{
    assertType("unit_float<'1'>|unit_float<'international_foot / meter'>", unit_factor('meter', $to));
}

/** @param 'foot'|'international_foot' $from */
function equivalentFactorSource(string $from): void
{
    assertType("unit_float<'meter / international_foot'>", unit_factor($from, 'meter'));
}

/** @param '100 * centimeter'|'meter' $from */
function definitionallyEquivalentFactorSource(string $from): void
{
    assertType("unit_float<'1'>", unit_factor($from, 'meter'));
}

/** @param 'meter'|'second' $from */
function incompatibleFiniteFactorSource(string $from): void
{
    assertType('*ERROR*', unit_factor($from, 'meter'));
}

/** @param 'meter'|'second' $to */
function incompatibleFiniteFactorTarget(string $to): void
{
    assertType('*ERROR*', unit_factor('meter', $to));
}

function dynamicFactor(string $from, string $to): void
{
    assertType('float', unit_factor($from, $to));
}

assertType('*ERROR*', unit_factor('meter', 'second'));
assertType('*ERROR*', unit_factor('not_a_real_unit_xyz', 'meter'));
assertType('*ERROR*', unit_factor('meter', 'not_a_real_unit_xyz'));
assertType('*ERROR*', unit_factor('meter /', 'meter'));
assertType('*ERROR*', unit_factor('meter', 'second /'));
assertType('*ERROR*', unit_factor('celsius', 'kelvin'));
assertType('*ERROR*', unit_factor('celsius', 'celsius'));
assertType('*ERROR*', unit_factor('B', 'B'));

// --- unit_to() success: result branded with parsed *to* unit (always float) ---

assertType("unit_float<'meter'>", unit_to(3.0, 'foot', 'meter'));
assertType("unit_float<'international_foot'>", unit_to(1.0, 'meter', 'foot'));
assertType("unit_float<'1/100 * meter'>", unit_to(1.0, 'inch', 'centimeter'));
assertType("unit_float<'kilogram'>", unit_to(1.0, 'pound', 'kilogram'));
assertType("unit_float<'second'>", unit_to(1.0, 'hour', 'second'));
assertType("unit_float<'meter / second'>", unit_to(60.0, 'mile / hour', 'meter / second'));
assertType("unit_float<'1000 * meter / hour'>", unit_to(1.0, 'meter / second', 'kilometer / hour'));
assertType("unit_float<'meter ^ 3'>", unit_to(1.0, 'liter', 'meter^3'));
assertType("unit_float<'gram'>", unit_to(1.0, 'kilogram', 'gram'));
assertType("unit_float<'meter'>", unit_to(1.0, 'kilometer', 'meter'));

// affine sources can convert into a multiplicative brand; affine targets remain plain float
assertType("unit_float<'kelvin'>", unit_to(0, 'celsius', 'kelvin'));
assertType('float', unit_to(273.15, 'kelvin', 'celsius'));
assertType('float', unit_to(100, 'celsius', 'fahrenheit'));

// factor-1 derived SI still rebrands to the *to* spelling
assertType("unit_float<'kilogram * meter / second ^ 2'>", unit_to(1.0, 'newton', 'kilogram * meter / second^2'));
assertType("unit_float<'newton'>", unit_to(1.0, 'kilogram * meter / second^2', 'newton'));
assertType("unit_float<'joule'>", unit_to(1.0, 'newton * meter', 'joule'));
assertType("unit_float<'pascal'>", unit_to(1.0, 'newton / meter^2', 'pascal'));

// identity / alias
assertType("unit_float<'meter'>", unit_to(5.0, 'meter', 'meter'));
assertType("unit_float<'1000 * meter'>", unit_to(2.0, 'kilometer', '1000 * meter'));

// branded value + matching from
assertType("unit_float<'meter'>", unit_to(unit(3.0, 'foot'), 'foot', 'meter'));
assertType("unit_float<'meter / second'>", unit_to(unit(60.0, 'mile / hour'), 'mile / hour', 'meter / second'));

// int magnitude still yields unit_float after conversion
assertType("unit_float<'meter'>", unit_to(12, 'inch', 'meter'));

/** @param 'foot'|'meter' $from */
function finiteUnitToSource(string $from): void
{
    assertType("unit_float<'international_inch'>", unit_to(1.0, $from, 'inch'));
}

/** @param 'foot'|'meter' $to */
function finiteUnitToTarget(string $to): void
{
    assertType("unit_float<'international_foot'>|unit_float<'meter'>", unit_to(1.0, 'inch', $to));
}

/** @param 'foot'|'international_foot' $to */
function equivalentUnitToTarget(string $to): void
{
    assertType("unit_float<'international_foot'>", unit_to(1.0, 'inch', $to));
}

/** @param '100 * centimeter'|'meter' $to */
function definitionallyEquivalentUnitToTarget(string $to): void
{
    assertType("unit_float<'meter'>", unit_to(1.0, 'foot', $to));
}

/** @param 'celsius'|'degree_Celsius' $to */
function equivalentAffineUnitToTarget(string $to): void
{
    assertType('float', unit_to(273.15, 'kelvin', $to));
}

/** @param 'celsius'|'fahrenheit' $to */
function ambiguousAffineUnitToTarget(string $to): void
{
    assertType('float', unit_to(273.15, 'kelvin', $to));
}

/** @param 'celsius'|'fahrenheit' $from */
function finiteAffineUnitToSource(string $from): void
{
    assertType("unit_float<'kelvin'>", unit_to(1.0, $from, 'kelvin'));
}

/** @param 'celsius'|'kelvin' $to */
function mixedAffineAndMultiplicativeUnitToTarget(string $to): void
{
    assertType('float', unit_to(273.15, 'kelvin', $to));
}

/** @param 'meter'|'second' $from */
function incompatibleFiniteUnitToSource(string $from): void
{
    assertType('*ERROR*', unit_to(1.0, $from, 'meter'));
}

/** @param 'meter'|'second' $to */
function incompatibleFiniteUnitToTarget(string $to): void
{
    assertType('*ERROR*', unit_to(1.0, 'meter', $to));
}

/**
 * @param unit_float<'100 * centimeter'>|unit_float<'meter'> $value
 * @param '100 * centimeter'|'meter'                         $from
 */
function equivalentBrandedValueAndSourceUnions(float $value, string $from): void
{
    assertType("unit_float<'international_foot'>", unit_to($value, $from, 'foot'));
}

/** @param unit_float<'foot'>|unit_float<'meter'> $value */
function incompatibleBrandedValueUnion(float $value): void
{
    assertType('*ERROR*', unit_to($value, 'meter', 'foot'));
}

// --- unit_to() errors ---

assertType('*ERROR*', unit_to(1.0, 'meter', 'second'));
assertType('*ERROR*', unit_to(1.0, 'kilogram', 'meter'));
assertType('*ERROR*', unit_to(1.0, 'newton', 'joule'));
assertType('*ERROR*', unit_to(1.0, 'meter / second', 'meter / second^2'));
assertType('*ERROR*', unit_to(1.0, 'meter', 'not_a_real_unit_xyz'));
assertType('*ERROR*', unit_to(1.0, 'not_a_real_unit_xyz', 'meter'));

// value unit does not match from (normalized forms differ)
assertType('*ERROR*', unit_to(unit(3.0, 'foot'), 'meter', 'foot'));
assertType('*ERROR*', unit_to(unit(1.0, 'kilogram'), 'meter', 'kilogram'));
assertType('*ERROR*', unit_to(unit(0.0, 'kelvin'), 'celsius', 'kelvin'));
