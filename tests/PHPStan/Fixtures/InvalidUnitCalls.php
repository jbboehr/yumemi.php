<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_factor;
use function jbboehr\Yumemi\unit_to;

// unit(): unknown unit string — diagnostic even though the result is discarded.
unit(1.0, 'not_a_real_unit_xyz');

// unit_to(): unknown "to" unit.
unit_to(1.0, 'meter', 'not_a_real_unit_xyz');

// unit_to(): unknown "from" unit.
unit_to(1.0, 'not_a_real_unit_xyz', 'meter');

// unit_to(): dimensionally incompatible units.
unit_to(1.0, 'meter', 'second');

// unit_to(): branded value unit does not match the "from" unit.
unit_to(unit(3.0, 'foot'), 'meter', 'foot');

// unit_factor(): incompatible dimensions and unsupported multiplicative semantics.
unit_factor('meter', 'second');
unit_factor('celsius', 'kelvin');
unit_factor('celsius', 'celsius');
unit_factor('B', 'B');
unit_factor('not_a_real_unit_xyz', 'meter');
unit_factor('meter', 'not_a_real_unit_xyz');
unit_factor('meter /', 'meter');
unit_factor('meter', 'second /');

// Valid calls — no diagnostics expected.
unit(1.0, 'meter');
unit_to(3.0, 'foot', 'meter');
unit_factor('meter', 'foot');

// Malformed constant strings include expression-local source diagnostics.
unit(1.0, 'meter * / second');
unit_to(1.0, 'meter', 'second /');

// Known catalog units with unsupported semantics receive deliberate diagnostics.
unit(1.0, 'B');
unit(1.0, 'degree_Celsius');

// Affine conversions are valid only as standalone conversion units.
unit_to(1.0, 'degree_Celsius', 'meter');
unit_to(unit(1.0, 'kelvin'), 'celsius', 'kelvin');
unit_to(1.0, 'celsius * meter', 'kelvin');
unit_to(1.0, 'kilocelsius', 'kelvin');

// Valid affine calls produce no diagnostics.
unit_to(0, 'celsius', 'kelvin');
unit_to(32.0, 'fahrenheit', 'celsius');

// Non-constant native helper strings receive the dedicated dynamic-expression diagnostic.
function dynamicUnit(string $u): void
{
    unit(1.0, $u);
}

/** @param 'meter'|'not_a_real_unit_xyz' $u */
function partlyInvalidFiniteUnit(string $u): void
{
    unit(1.0, $u);
}

// Logarithmic conversion is a distinct unsupported operation.
unit_to(1.0, 'B', '1');

/** @param 'meter'|'second' $from */
function invalidFiniteFactorSource(string $from): void
{
    unit_factor($from, 'meter');
}

/** @param 'meter'|'second' $to */
function invalidFiniteFactorTarget(string $to): void
{
    unit_factor('meter', $to);
}

/** @param 'meter'|'second' $from */
function invalidFiniteConversionSource(string $from): void
{
    unit_to(1.0, $from, 'meter');
}

/** @param 'meter'|'second' $to */
function invalidFiniteConversionTarget(string $to): void
{
    unit_to(1.0, 'meter', $to);
}

/** @param unit_float<'foot'>|unit_float<'meter'> $value */
function invalidBrandedConversionUnion(float $value): void
{
    unit_to($value, 'meter', 'foot');
}

/** @param literal-string $unit */
function dynamicLiteralUnit(string $unit): void
{
    unit(1.0, $unit);
}

function dynamicFactor(string $from, string $to): void
{
    unit_factor($from, 'meter');
    unit_factor('meter', $to);
}

function dynamicConversion(string $from, string $to): void
{
    unit_to(1.0, $from, 'meter');
    unit_to(1.0, 'meter', $to);
}

/** @param 'foot'|'meter' $unit */
function ambiguousUnit(string $unit): void
{
    unit(1.0, $unit);
}

/** @param 'foot'|'international_foot' $unit */
function equivalentUnit(string $unit): void
{
    unit(1.0, $unit);
}

/** @param 'foot'|'meter' $from */
function ambiguousFactor(string $from): void
{
    unit_factor($from, 'meter');
}

/** @param 'foot'|'meter' $from */
function determinateConversion(string $from): void
{
    unit_to(1.0, $from, 'inch');
}

/** @param 'foot'|'meter' $to */
function ambiguousConversion(string $to): void
{
    unit_to(1.0, 'inch', $to);
}

/** @param 'celsius'|'fahrenheit' $to */
function ambiguousAffineConversion(string $to): void
{
    unit_to(273.15, 'kelvin', $to);
}

function dynamicNamedUnit(string $unit): void
{
    unit(unit: $unit, value: 1.0);
}

// The ordinary PHPStan argument rule owns non-string arguments; Yumemi must not duplicate it.
unit(1.0, 1);

/** @param 'foot'|'international_foot' $from */
function equivalentFactorSource(string $from): void
{
    unit_factor($from, 'meter');
}

/** @param 'meter'|'metre' $to */
function equivalentConversionTarget(string $to): void
{
    unit_to(1.0, 'foot', $to);
}

/** @param 'celsius'|'degree_Celsius' $to */
function equivalentAffineConversionTarget(string $to): void
{
    unit_to(273.15, 'kelvin', $to);
}

/** @param unit_float<'foot'>|unit_float<'meter'> $value */
function invalidBrandedConversionUnionAfterMatchingFirst(float $value): void
{
    unit_to($value, 'foot', 'meter');
}

/** @param 'Bq'|'Btu' $unit */
function ambiguousUnitWithReorderedCanonicalNames(string $unit): void
{
    unit(1.0, $unit);
}

/** @param 'foot'|'international_foot'|'meter' $to */
function duplicateThenDistinctFactorTargets(string $to): void
{
    unit_factor('foot', $to);
}

/** @param 'metres'|'mi' $to */
function ambiguousConversionWithReorderedCanonicalNames(string $to): void
{
    unit_to(1.0, 'meter', $to);
}

unit_to(1.0, 'second', 'metres');

// Unrelated and incomplete calls must remain outside this rule's ownership.
strlen('meter');
unit(1.0);
unit_factor('meter');
unit_to(1.0, 'meter');

// Ordinary argument diagnostics own non-string unit arguments.
unit_factor(1, 'meter');
unit_factor('meter', 1);
unit_to(1.0, 1, 'meter');
unit_to(1.0, 'meter', 1);

/** @param '100 * centimeter'|'meter' $unit */
function definitionallyEquivalentUnitExpressions(string $unit): void
{
    unit(1.0, $unit);
}

/** @param '100 * centimeter'|'meter' $from */
function definitionallyEquivalentFactorSources(string $from): void
{
    unit_factor($from, 'meter');
}

/** @param '100 * centimeter'|'meter' $to */
function definitionallyEquivalentConversionTargets(string $to): void
{
    unit_to(1.0, 'foot', $to);
}

/** @param 'celsius'|'fahrenheit' $from */
function determinateAffineConversionSources(string $from): void
{
    unit_to(1.0, $from, 'kelvin');
}

/** @param 'celsius'|'kelvin' $to */
function mixedAffineAndMultiplicativeConversionTargets(string $to): void
{
    unit_to(273.15, 'kelvin', $to);
}

// @phpstan-ignore yumemi.invalidUnitCall (exercise identifier-specific suppression)
unit(1.0, 'not_a_real_unit_xyz');

function ignoredDynamicUnitExpression(string $unit): void
{
    // @phpstan-ignore yumemi.dynamicUnitExpression (exercise identifier-specific suppression)
    unit(1.0, $unit);
}

/** @param 'foot'|'meter' $unit */
function ignoredAmbiguousUnitExpression(string $unit): void
{
    // @phpstan-ignore yumemi.ambiguousUnitExpression (exercise identifier-specific suppression)
    unit(1.0, $unit);
}
