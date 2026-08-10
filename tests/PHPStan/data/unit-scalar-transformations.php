<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;
use function PHPStan\Testing\assertType;

$integer = unit(3, 'meter');
assertType("3.0&unit_float<'meter'>", (float) $integer);
assertType("3&unit_int<'meter'>", (int) $integer);

$float = unit(1.5, 'second');
assertType("1&unit_int<'second'>", (int) $float);
assertType("1.5&unit_float<'second'>", (float) $float);

/** @var unit_numeric_string<'second'> $numericString */
$numericString = '30';
assertType("unit_int<'second'>", (int) $numericString);
assertType("unit_float<'second'>", (float) $numericString);
assertType('float|int', $numericString + 0);

/** @param int<-5, 10> $value */
function assertBrandedRangeCasts(int $value): void
{
    $range = unit($value, 'meter');

    assertType("unit_int<'meter'>&int<-5, 10>", (int) $range);
    assertType("unit_float<'meter'>", (float) $range);
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
assertType("unit_float<'meter'>", round(unit(1.25, 'meter')));
assertType("unit_float<'meter'>", round(unit(125, 'meter'), -1));
assertType(
    "unit_float<'meter'>",
    round(num: unit(1.25, 'meter'), precision: 1, mode: RoundingMode::HalfEven),
);
assertType("2.0&unit_float<'second'>", ceil(num: unit(2, 'second')));
assertType("2.0&unit_float<'second'>", floor(num: unit(2, 'second')));

assertType("1&unit_int<'meter'>", min(unit(3, 'meter'), unit(1, 'meter'), unit(2, 'meter')));
assertType("3&unit_int<'meter'>", max(unit(3, 'meter'), unit(1, 'meter'), unit(2, 'meter')));
assertType("1&unit_int<'meter'>", min(value: unit(3, 'meter'), values: unit(1, 'meter')));
assertType("2&unit_int<'meter'>", max(value: unit(1, 'meter'), values: unit(2, 'meter')));
assertType("1&unit_int<'meter'>", min(unit(1, 'meter'), unit(2, 'm')));
assertType("2&unit_int<'meter'>", max(unit(1, 'meter'), unit(2, 'm')));

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

$dynamicFunction = static fn (int $value): int => $value;
assertType('int', $dynamicFunction(-3));

$absoluteFunction = abs(...);
assertType('int<0, max>', $absoluteFunction(-3));

$squareRootFunction = sqrt(...);
assertType('float', $squareRootFunction(4.0));
