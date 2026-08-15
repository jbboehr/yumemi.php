<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;
use function PHPStan\Testing\assertType;

$integer = unit(3, 'meter');
assertType("3.0&unit_float<'meter'>", (float) $integer);
assertType("3&unit_int<'meter'>", (int) $integer);
assertType("3.0&unit_float<'meter'>", floatval($integer));
assertType("3.0&unit_float<'meter'>", doubleval(value: $integer));
assertType("3&unit_int<'meter'>", intval($integer));
assertType("3&unit_int<'meter'>", intval($integer, 16));

$float = unit(1.5, 'second');
assertType("1&unit_int<'second'>", (int) $float);
assertType("1.5&unit_float<'second'>", (float) $float);
assertType("1&unit_int<'second'>", intval(value: $float));
assertType("1.5&unit_float<'second'>", floatval($float));

/** @var unit_numeric_string<'second'> $numericString */
$numericString = '30';
assertType("unit_int<'second'>", (int) $numericString);
assertType("unit_float<'second'>", (float) $numericString);
assertType("unit_int<'second'>", intval($numericString));
assertType("unit_int<'second'>", intval($numericString, base: 10));
assertType('int', intval($numericString, 16));
assertType("unit_float<'second'>", floatval($numericString));
assertType('float|int', $numericString + 0);

/**
 * @param unit_numeric_string<'second'> $value
 */
function assertDynamicNumericStringBase(string $value, int $base): void
{
    assertType('int', intval($value, $base));
}

/** @param int<-5, 10> $value */
function assertBrandedRangeCasts(int $value): void
{
    $range = unit($value, 'meter');

    assertType("unit_int<'meter'>&int<-5, 10>", (int) $range);
    assertType("unit_float<'meter'>", (float) $range);
    assertType("unit_int<'meter'>&int<-5, 10>", intval($range));
    assertType("unit_float<'meter'>", doubleval($range));
}

/** @param unit_int<'meter'>|unit_int<'second'> $value */
function assertBrandedUnionCasts(int $value): void
{
    assertType("unit_float<'meter'>|unit_float<'second'>", (float) $value);
}

assertType("3&unit_int<'meter'>", abs(unit(-3, 'meter')));
assertType("unit_float<'meter'>", abs(unit(-9223372036854775807 - 1, 'meter')));
assertType("1.5&unit_float<'second'>", abs(unit(-1.5, 'second')));
assertType("3&unit_int<'meter'>", abs(num: unit(-3, 'meter')));

/** @param int<-5, 10> $value */
function assertBrandedRangeAbsoluteValue(int $value): void
{
    assertType("unit_int<'meter'>&int<0, 10>", abs(unit($value, 'meter')));
}

/** @param unit_int<'meter'> $value */
function assertUnboundedBrandedAbsoluteValue(int $value): void
{
    assertType(
        "((unit_int<'meter'>&int<0, max>)|unit_float<'meter'>)",
        abs($value),
    );
}

assertType("2.0&unit_float<'meter'>", ceil(unit(1.25, 'meter')));
assertType("1.0&unit_float<'meter'>", floor(unit(1.75, 'meter')));
assertType("2.0&unit_float<'meter'>", round(unit(1.5, 'meter')));
assertType("130.0&unit_float<'meter'>", round(unit(125, 'meter'), -1));
assertType("1.3&unit_float<'meter'>", round(unit(1.25, 'meter'), 1, PHP_ROUND_HALF_UP));
assertType("1.2&unit_float<'meter'>", round(unit(1.25, 'meter'), 1, PHP_ROUND_HALF_DOWN));
assertType("1.2&unit_float<'meter'>", round(unit(1.25, 'meter'), 1, PHP_ROUND_HALF_EVEN));
assertType("1.3&unit_float<'meter'>", round(unit(1.25, 'meter'), 1, PHP_ROUND_HALF_ODD));

/** @param 0|1 $precision */
function assertBrandedRoundPrecisionAlternatives(int $precision): void
{
    assertType("1.0&unit_float<'meter'>|1.3&unit_float<'meter'>", round(unit(1.25, 'meter'), $precision));
}

/** @param 1|2 $mode */
function assertBrandedRoundModeAlternatives(int $mode): void
{
    assertType("1.0&unit_float<'meter'>|2.0&unit_float<'meter'>", round(unit(1.5, 'meter'), 0, $mode));
}

function assertDynamicBrandedRoundPrecision(int $precision): void
{
    assertType("unit_float<'meter'>", round(unit(1.25, 'meter'), $precision));
}
assertType("2.0&unit_float<'second'>", ceil(num: unit(2, 'second')));
assertType("2.0&unit_float<'second'>", floor(num: unit(2, 'second')));

assertType("3.0&unit_float<'meter / second'>", fdiv(unit(6, 'meter'), unit(2, 'second')));
assertType("3.0&unit_float<'meter'>", fdiv(num1: unit(6, 'meter'), num2: 2));
assertType("0.5&unit_float<'1 / second'>", fdiv(1, unit(2, 'second')));
assertType("unit_float<'meter'>", fdiv(unit(1.0, 'meter'), 0.0));
assertType("2&unit_int<'meter / second'>", intdiv(unit(7, 'meter'), unit(3, 'second')));
assertType("-2&unit_int<'meter'>", intdiv(num1: unit(-7, 'meter'), num2: 3));
assertType("-2&unit_int<'1 / second'>", intdiv(7, unit(-3, 'second')));
assertType("1.0&unit_float<'meter'>", fmod(unit(7, 'meter'), unit(3, 'm')));
assertType("1.0&unit_float<'meter'>", fmod(num1: unit(7, 'meter'), num2: unit(3, 'meter')));
assertType("5.0&unit_float<'meter'>", hypot(unit(3, 'meter'), unit(4, 'm')));
assertType("5.0&unit_float<'meter'>", hypot(x: unit(3, 'meter'), y: unit(4, 'meter')));
assertType("9&unit_int<'meter ^ 2'>", pow(unit(3, 'meter'), 2));
assertType("0.5&unit_float<'1 / second'>", pow(num: unit(2.0, 'second'), exponent: -1));
assertType("3.141592653589793&unit_float<'radian'>", deg2rad(unit(180, 'degree')));
assertType("1.5707963267948966&unit_float<'radian'>", deg2rad(num: unit(90.0, 'arc_degree')));
assertType("180.0&unit_float<'arc_degree'>", rad2deg(unit(M_PI, 'rad')));
assertType("0.0&unit_float<'1'>", sin(unit(0, 'radian')));
assertType("1.0&unit_float<'1'>", cos(num: unit(0.0, 'rad')));
assertType("0.0&unit_float<'1'>", tan(unit(0.0, 'radian')));
assertType("0.5235987755982989&unit_float<'radian'>", asin(unit(0.5, '1')));
assertType("0.5235987755982989&unit_float<'radian'>", asin(unit_to(unit(50.0, 'percent'), 'percent', '1')));
assertType("1.0471975511965979&unit_float<'radian'>", acos(num: unit(0.5, 'meter / meter')));
assertType("0.7853981633974483&unit_float<'radian'>", atan(unit(1, 'second / second')));
assertType("0.6435011087932844&unit_float<'radian'>", atan2(unit(3, 'meter'), unit(4, '100 * centimeter')));
assertType("0.4636476090008061&unit_float<'radian'>", atan2(x: unit(2, 'second'), y: unit(1.0, 'second')));

/** @param unit_int<'meter'>&int<1, 5> $value */
function assertBrandedBinaryMath(int $value): void
{
    assertType("unit_int<'meter'>&int<0, 2>", intdiv($value, 2));
    assertType("unit_float<'meter'>", fmod($value, unit(2, 'meter')));
    assertType("unit_float<'meter'>", hypot($value, unit(2, 'meter')));
    assertType("unit_int<'meter ^ 2'>&int<1, 25>", pow($value, 2));
}

/** @param unit_int<'arc_degree'>&int<0, 360> $value */
function assertBrandedAngleConversion(int $value): void
{
    assertType("unit_float<'radian'>", deg2rad($value));
}

/** @param unit_int<'radian'>|unit_float<'rad'> $value */
function assertBrandedAngleUnion(int|float $value): void
{
    assertType("unit_float<'arc_degree'>", rad2deg($value));
}

/** @param unit_int<'radian'>|unit_float<'rad'> $value */
function assertBrandedDirectTrigUnion(int|float $value): void
{
    assertType("unit_float<'1'>", sin($value));
}

/** @param unit_int<'1'>|unit_float<'meter / meter'> $value */
function assertBrandedInverseTrigUnion(int|float $value): void
{
    assertType("unit_float<'radian'>", atan($value));
}

/** @param unit_int<'arc_degree'>|float $value */
function assertMixedAngleConversionFallsBackToFloat(int|float $value): void
{
    assertType('float', deg2rad($value));
}

/** @param unit_int<'meter'>|float $value */
function assertMixedFloatDivision(int|float $value): void
{
    assertType('float', fdiv($value, 2.0));
}

/** @param unit_int<'meter'>|unit_float<'second'> $value */
function assertBrandedUnionFloatDivision(int|float $value): void
{
    assertType("unit_float<'meter'>|unit_float<'second'>", fdiv($value, 2));
}

assertType("1&unit_int<'meter'>", min(unit(3, 'meter'), unit(1, 'meter'), unit(2, 'meter')));
assertType("3&unit_int<'meter'>", max(unit(3, 'meter'), unit(1, 'meter'), unit(2, 'meter')));
assertType("1&unit_int<'meter'>", min(value: unit(3, 'meter'), values: unit(1, 'meter')));
assertType("2&unit_int<'meter'>", max(value: unit(1, 'meter'), values: unit(2, 'meter')));
assertType("1&unit_int<'meter'>", min(unit(1, 'meter'), unit(2, 'm')));
assertType("2&unit_int<'meter'>", max(unit(1, 'meter'), unit(2, 'm')));
assertType("1.5&unit_float<'meter'>", min(unit(3.5, 'meter'), unit(1.5, 'meter')));
assertType("3.5&unit_float<'meter'>", max(unit(3.5, 'meter'), unit(1.5, 'meter')));
assertType("1.5&unit_float<'meter'>", min(unit(2, 'meter'), unit(1.5, 'meter')));
assertType("2&unit_int<'meter'>", max(unit(2, 'meter'), unit(1.5, 'meter')));

/**
 * @param int<0, 20> $lower
 * @param int<10, 30> $upper
 */
function assertBrandedIntegerExtrema(int $lower, int $upper): void
{
    assertType("unit_int<'meter'>&int<0, 20>", min(unit($lower, 'meter'), unit($upper, 'meter')));
    assertType("unit_int<'meter'>&int<10, 30>", max(unit($lower, 'meter'), unit($upper, 'meter')));
}

/** @param non-empty-list<unit_int<'meter'>&int<1, 10>> $values */
function assertBrandedArrayExtrema(array $values): void
{
    assertType("unit_int<'meter'>&int<1, 10>", min($values));
    assertType("unit_int<'meter'>&int<1, 10>", max($values));
    assertType("unit_int<'meter'>&int<1, 10>", min(...$values));
    assertType("unit_int<'meter'>&int<1, 10>", max(...$values));
}

assertType("1&unit_int<'meter'>", min([unit(3, 'meter'), unit(1, 'meter'), unit(2, 'meter')]));
assertType("3&unit_int<'meter'>", max([unit(3, 'meter'), unit(1, 'meter'), unit(2, 'meter')]));
assertType("1.5&unit_float<'meter'>", min([unit(3.5, 'meter'), unit(1.5, 'meter')]));
assertType("3.5&unit_float<'meter'>", max([unit(3.5, 'meter'), unit(1.5, 'meter')]));

/** @param list<unit_int<'meter'>> $values */
function assertOptionallyUnpackedBrandedExtrema(array $values): void
{
    assertType("unit_int<'meter'>", min(unit(5, 'meter'), ...$values));
    assertType("unit_int<'meter'>", max(unit(5, 'meter'), ...$values));
}

/** @param non-empty-list<unit_float<'meter'>> $values */
function assertBrandedFloatArrayExtrema(array $values): void
{
    assertType("unit_float<'meter'>", min($values));
    assertType("unit_float<'meter'>", max($values));
}

/** @param non-empty-list<unit_int<'meter'>|unit_float<'meter'>> $values */
function assertMixedCarrierArrayExtrema(array $values): void
{
    assertType("unit_float<'meter'>|unit_int<'meter'>", min($values));
    assertType("unit_float<'meter'>|unit_int<'meter'>", max($values));
}

/**
 * @param (unit_int<'meter'>&int<0, 10>)|(unit_int<'meter'>&int<20, 30>) $left
 * @param unit_int<'meter'>&int<5, 25> $right
 */
function assertBrandedUnionExtrema(int $left, int $right): void
{
    assertType("unit_int<'meter'>&int<0, 25>", min($left, $right));
    assertType("unit_int<'meter'>&int<5, 30>", max($left, $right));
}

/** @param float $floating */
function assertMixedCarrierExtrema(float $floating): void
{
    assertType(
        "5&unit_int<'meter'>|unit_float<'meter'>",
        min(unit($floating, 'meter'), unit(5, 'meter')),
    );
    assertType(
        "5&unit_int<'meter'>|unit_float<'meter'>",
        max(unit($floating, 'meter'), unit(5, 'meter')),
    );
}

assertType("3.0&unit_float<'meter'>", sqrt(unit(9, 'meter^2')));
assertType("1.5&unit_float<'1/100 * meter'>", sqrt(unit(2.25, 'centimeter^2')));
assertType("2.0&unit_float<'newton'>", sqrt(unit(4.0, 'newton^2')));
assertType("2.0&unit_float<'meter / second'>", sqrt(unit(4.0, 'meter^2 / second^2')));
assertType("2.0&unit_float<'1 / meter'>", sqrt(unit(4.0, 'meter^-2')));
assertType("2.0&unit_float<'1'>", sqrt(unit(4, '1')));
assertType("2.0&unit_float<'meter'>", sqrt(num: unit(4, 'meter^2')));
assertType("unit_float<'meter'>", sqrt(unit(-4.0, 'meter^2')));
assertType('float', sqrt(unit(1, 'meter')));

/** @param int<0, 100> $value */
function assertBrandedRangeSquareRoot(int $value): void
{
    assertType("unit_float<'meter'>", sqrt(unit($value, 'meter^2')));
}

/** @param unit_int<'meter^2'>|unit_float<'second^2'> $value */
function assertBrandedUnionSquareRoots(int|float $value): void
{
    assertType("unit_float<'meter'>|unit_float<'second'>", sqrt($value));
}

/** @param unit_int<'meter^2'>|float $value */
function assertMixedSquareRootFallsBackToFloat(int|float $value): void
{
    assertType('float', sqrt($value));
}

/**
 * @param (unit_int<'meter'>&int<-5, 5>)|(unit_int<'second'>&int<-2, 2>) $value
 */
function assertBrandedUnionTransformations(int $value): void
{
    assertType(
        "(unit_int<'meter'>&int<0, 5>)|(unit_int<'second'>&int<0, 2>)",
        abs($value),
    );
    assertType("unit_float<'meter'>|unit_float<'second'>", round($value));
}

/** @param unit_int<'meter'> $value */
function assertPotentiallyOverflowingRound(int $value): void
{
    assertType("unit_float<'meter'>", round(-$value));
    assertType(
        "((unit_int<'meter'>&int<0, max>)|unit_float<'meter'>)",
        abs(-$value),
    );
}

assertType('3', abs(-3));
assertType('float', ceil(1.25));
assertType('float', floor(1.75));
assertType('float', round(1.25));
assertType('float', sqrt(4.0));
assertType('3.0', floatval(3));
assertType('3', intval(3.5));
assertType('float', fdiv(6, 2));
assertType('int', intdiv(7, 3));
assertType('float', fmod(7, 3));
assertType('float', hypot(3, 4));
assertType('9', pow(3, 2));
assertType('float', deg2rad(180));
assertType('float', rad2deg(M_PI));
assertType('float', sin(0.5));
assertType('float', asin(0.5));
assertType('float', atan2(1.0, 1.0));

$dynamicFunction = static fn (int $value): int => $value;
assertType('int', $dynamicFunction(-3));

$absoluteFunction = abs(...);
assertType('int<0, max>', $absoluteFunction(-3));

$roundFunction = round(...);
assertType('float', $roundFunction(1.25));

$squareRootFunction = sqrt(...);
assertType('float', $squareRootFunction(4.0));

$angleFunction = deg2rad(...);
assertType('float', $angleFunction(180.0));

$trigFunction = sin(...);
assertType('float', $trigFunction(0.5));

$directionFunction = atan2(...);
assertType('float', $directionFunction(1.0, 1.0));

$powerFunction = pow(...);
assertType('float|int|object', $powerFunction(3, 2));

$integerDivisionFunction = intdiv(...);
assertType('int', $integerDivisionFunction(7, 3));
