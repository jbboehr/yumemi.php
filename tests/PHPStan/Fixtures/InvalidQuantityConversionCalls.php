<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

$meters = Units::default()->quantity(1, 'meter');

$meters->to('second');
$meters->valueIn('second');
$meters->intValueIn('second');
$meters->exactIntValueIn('second');
$meters->to('not_a_real_unit_xyz');

/**
 * @param Quantity<'meter'> $meters
 * @param 'meter'|'second'  $unit
 */
function convertToPossibleDimension(Quantity $meters, string $unit): void
{
    $meters->to($unit);
}

// Valid: compatible conversion/extraction targets.
$meters->to('foot');
$meters->valueIn('foot');
$meters->intValueIn('foot');
$meters->exactIntValueIn('foot');

// Genuinely dynamic targets fail open, including on an unbranded receiver.
function convertUnknown(Quantity $quantity, string $unit): void
{
    $quantity->to($unit);
    $quantity->valueIn($unit);
    $quantity->intValueIn($unit);
    $quantity->exactIntValueIn($unit);
}
