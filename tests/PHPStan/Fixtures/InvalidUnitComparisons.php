<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

$meters = unit(1, 'meter');
$seconds = unit(1, 'second');
$feet = unit(1, 'foot');

$meters == $seconds;
$meters != $seconds;
$meters === $seconds;
$meters !== $seconds;
$meters < $seconds;
$meters <= $seconds;
$meters > $seconds;
$meters >= $seconds;
$meters <=> $seconds;
$meters == $feet;
$meters == 1;
1 < $meters;

// Valid: every native comparison pair is definitionally equivalent.
$meters == unit(1, 'meter');
$meters === unit(1, '100 * centimeter');
$meters <=> unit(1.0, 'meter');

/** @param unit_int<'meter'>|unit_int<'second'> $value */
function compareUnitUnion(int $value): void
{
    $value == unit(1, 'meter');
    unit(1, 'meter') == $value;
}

/** @param int|unit_int<'meter'> $value */
function compareMixedUnion(int $value): void
{
    $value == unit(1, 'meter');
    unit(1, 'meter') == $value;
}

/** @param unit_int<'meter'>|null $value */
function compareNullableUnit(?int $value): void
{
    $value !== null;
    $value != null;
    $value == unit(1, 'meter');
    unit(1, 'meter') == $value;
    $value === unit(1, 'meter');
    unit(1, 'meter') !== $value;
}

$meters === 1;

$object = new \stdClass();
$object == $meters;
$meters != $object;
$object === $meters;
$meters !== $object;

/** @param unit_int<'second'>|unit_int<'kilogram'> $value */
function compareIncompatibleUnitUnion(int $value): void
{
    unit(1, 'meter') == $value;
}

/** @param unit_int<'meter'>|\stdClass $value */
function compareUnitAndObject(int|\stdClass $value): void
{
    $value == unit(1, 'meter');
    unit(1, 'meter') == $value;

    // Valid: strict identity may compare a unit arm with a nonnumeric, nonunit arm.
    $value === unit(1, 'meter');
    unit(1, 'meter') !== $value;
}

/** @var unit_numeric_string<'meter'> $meterText */
$meterText = '1';
/** @var unit_numeric_string<'100 * centimeter'> $equivalentMeterText */
$equivalentMeterText = '1';
/** @var unit_numeric_string<'second'> $secondText */
$secondText = '1';
/** @var numeric-string $bareText */
$bareText = '1';

// Valid: numeric-string and native brands carry definitionally equivalent units.
$meterText == $equivalentMeterText;
$meterText <=> $meters;

$meterText == $secondText;
$meterText === $secondText;
$meterText < $seconds;
$meterText === $bareText;
$bareText == $meterText;

/**
 * @param unit_numeric_string<'meter'>|unit_numeric_string<'second'> $value
 * @param unit_numeric_string<'meter'> $meters
 */
function compareNumericStringUnion(string $value, string $meters): void
{
    $value == $meters;
}

// Valid: the comparison rule ignores non-comparison binary operations.
$meters + $seconds;
