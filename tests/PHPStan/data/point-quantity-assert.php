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

/** @param 'celsius'|'fahrenheit' $unit */
function finitePointTargets(Units $units, string $unit): void
{
    assertType("PointQuantity<'celsius'>|PointQuantity<'fahrenheit'>", $units->point(0, $unit));
    assertType("Quantity<'delta_degree_Celsius'>|Quantity<'delta_fahrenheit'>", $units->deltaQuantity(1, $unit));
}
