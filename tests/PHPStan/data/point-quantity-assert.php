<?php

use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Units;
use function PHPStan\Testing\assertType;

$units = Units::default();
$freezing = $units->point(0, 'celsius');
$boilingFahrenheit = $units->point(212, 'fahrenheit');
$rise = $units->deltaQuantity(18, 'fahrenheit');

assertType("PointQuantity<'celsius'>", $freezing);
assertType("PointQuantity<'fahrenheit'>", $boilingFahrenheit);
assertType("Quantity<'delta_fahrenheit'>", $rise);
assertType("PointQuantity<'celsius'>", $freezing->add($rise));
assertType("PointQuantity<'celsius'>", $freezing->sub($rise));
assertType("Quantity<'delta_fahrenheit'>", $boilingFahrenheit->difference($freezing));
assertType("PointQuantity<'fahrenheit'>", $freezing->to('fahrenheit'));

$summit = $units->point(4410, 'meter');
$trailhead = $units->point(1800, 'meter');
assertType("Quantity<'meter'>", $summit->difference($trailhead));

$initialPosition = $units->point(100, 'meter');
$displacement = $units->quantity(15, 'meter / second')->mul($units->quantity(4, 'second'));
assertType("Quantity<'meter'>", $displacement);
assertType("PointQuantity<'meter'>", $initialPosition->add($displacement));

$finalTemperature = $units->point(350, 'kelvin');
$initialTemperature = $units->point(300, 'kelvin');
assertType("Quantity<'kelvin'>", $finalTemperature->difference($initialTemperature));

assertType('jbboehr\\Yumemi\\Number\\Rational', $freezing->valueIn('fahrenheit'));
assertType('int', $freezing->intValueIn('fahrenheit'));
assertType('int', $freezing->exactIntValueIn('fahrenheit'));
assertType('string', $freezing->decimalValueIn('fahrenheit', 2, \RoundingMode::HalfEven));
assertType('string', $freezing->significantDecimalValueIn('fahrenheit', 3, \RoundingMode::HalfEven));
assertType('string', $freezing->exactDecimalValueIn('fahrenheit'));
assertType('float', $freezing->floatValueIn('fahrenheit'));

assertType('-1|0|1', $freezing->compareTo($boilingFahrenheit));
assertType('bool', $freezing->equals($boilingFahrenheit));
assertType('bool', $freezing->lessThan($boilingFahrenheit));
assertType('bool', $freezing->lessThanOrEqualTo($boilingFahrenheit));
assertType('bool', $freezing->greaterThan($boilingFahrenheit));
assertType('bool', $freezing->greaterThanOrEqualTo($boilingFahrenheit));

assertType('*ERROR*', $freezing->add($units->quantity(1, 'meter')));
assertType('*ERROR*', $freezing->difference($units->point(1, 'meter')));
assertType('*ERROR*', $freezing->to('meter'));
assertType('*ERROR*', $freezing->compareTo($units->point(1, 'meter')));
assertType('*ERROR*', $units->point(1, 'celsius / second'));
assertType('*ERROR*', $units->deltaQuantity(1, 'B'));

/** @var PointQuantity<'degree_Celsius'> $alias */
$alias = $freezing;
assertType("PointQuantity<'degree_Celsius'>", $alias);

function dynamicPoint(Units $units, string $unit): void
{
    assertType(PointQuantity::class, $units->point(0, $unit));
    assertType('jbboehr\\Yumemi\\Quantity', $units->deltaQuantity(1, $unit));
}

function dynamicPointConversion(PointQuantity $point, string $unit): void
{
    assertType(PointQuantity::class, $point->to($unit));
    assertType('jbboehr\\Yumemi\\Number\\Rational', $point->valueIn($unit));
}

/** @param 'celsius'|'fahrenheit' $unit */
function finitePointTargets(Units $units, string $unit): void
{
    $point = $units->point(0, 'celsius');

    assertType("PointQuantity<'celsius'>|PointQuantity<'fahrenheit'>", $units->point(0, $unit));
    assertType("Quantity<'delta_degree_Celsius'>|Quantity<'delta_fahrenheit'>", $units->deltaQuantity(1, $unit));
    assertType("PointQuantity<'celsius'>|PointQuantity<'fahrenheit'>", $point->to($unit));
}

/** @param 'celsius'|'meter' $unit */
function partlyIncompatiblePointTargets(Units $units, string $unit): void
{
    assertType('*ERROR*', $units->point(0, 'celsius')->to($unit));
}

function knownPointTargetFromUnbranded(PointQuantity $point): void
{
    assertType("PointQuantity<'celsius'>", $point->to('celsius'));
}

/** @param PointQuantity<'celsius'>|PointQuantity<'fahrenheit'> $point */
function compatiblePointReceiverUnion(PointQuantity $point): void
{
    assertType("PointQuantity<'kelvin'>", $point->to('kelvin'));
    assertType(
        "PointQuantity<'celsius'>|PointQuantity<'fahrenheit'>",
        $point->add(Units::default()->quantity(1, 'delta_celsius')),
    );
    assertType(
        "Quantity<'delta_degree_Celsius'>|Quantity<'delta_fahrenheit'>",
        $point->difference(Units::default()->point(0, 'kelvin')),
    );
}

/**
 * @param PointQuantity<'celsius'>                              $point
 * @param PointQuantity<'celsius'>|PointQuantity<'fahrenheit'> $other
 */
function compatiblePointOperandUnion(PointQuantity $point, PointQuantity $other): void
{
    assertType("Quantity<'delta_degree_Celsius'>", $point->difference($other));
    assertType('bool', $point->equals($other));
}
