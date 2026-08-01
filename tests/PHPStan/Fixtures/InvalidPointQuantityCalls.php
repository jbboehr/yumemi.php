<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Units;

$units = Units::default();
$celsius = $units->point(0, 'celsius');
$meters = $units->point(0, 'meter');

$celsius->add($units->quantity(1, 'meter'));
$celsius->sub($units->quantity(1, 'second'));
$celsius->difference($meters);
$celsius->to('meter');
$celsius->valueIn('second');
$celsius->compareTo($meters);
$celsius->equals($meters);

// Valid: compatible point scales and temperature differences.
$fahrenheit = $units->point(32, 'fahrenheit');
$celsius->add($units->quantity(18, 'delta_fahrenheit'));
$celsius->difference($fahrenheit);
$celsius->to('kelvin');
$celsius->compareTo($fahrenheit);

// Unbranded operands and receivers fail open when their coordinate is not statically known.
function inspectUnknownPoint(PointQuantity $unknown, string $unit): void
{
    Units::default()->point(0, 'celsius')->difference($unknown);
    $unknown->to($unit);
}

/** @param PointQuantity<'celsius'>|PointQuantity<'meter'> $point */
function convertPointReceiverUnion(PointQuantity $point): void
{
    $point->to('fahrenheit');
}

/** @param PointQuantity<'celsius'>|PointQuantity<'meter'> $other */
function comparePointOperandUnion(PointQuantity $other): void
{
    Units::default()->point(0, 'celsius')->difference($other);
}
