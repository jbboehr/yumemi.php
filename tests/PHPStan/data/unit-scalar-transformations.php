<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;
use function PHPStan\Testing\assertType;

$integer = unit(3, 'meter');
assertType("unit_float<'meter'>", (float) $integer);
assertType("3&unit_int<'meter'>", (int) $integer);

$float = unit(1.5, 'second');
assertType("unit_int<'second'>", (int) $float);
assertType("unit_float<'second'>", (float) $float);

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
assertType("unit_float<'second'>", abs(unit(-1.5, 'second')));
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

assertType("unit_float<'meter'>", ceil(unit(1.25, 'meter')));
assertType("unit_float<'meter'>", floor(unit(1.75, 'meter')));
assertType("unit_float<'meter'>", round(unit(1.25, 'meter')));
assertType("unit_float<'meter'>", round(unit(125, 'meter'), -1));
assertType(
    "unit_float<'meter'>",
    round(num: unit(1.25, 'meter'), precision: 1, mode: RoundingMode::HalfEven),
);
assertType("unit_float<'second'>", ceil(num: unit(2, 'second')));
assertType("unit_float<'second'>", floor(num: unit(2, 'second')));

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

$dynamicFunction = static fn (int $value): int => $value;
assertType('int', $dynamicFunction(-3));

$absoluteFunction = abs(...);
assertType('int<0, max>', $absoluteFunction(-3));
