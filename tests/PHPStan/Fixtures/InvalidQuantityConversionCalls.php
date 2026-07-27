<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

$meters = Units::default()->quantity(1, 'meter');

$meters->to('second');
$meters->valueIn('second');
$meters->intValueIn('second');
$meters->exactIntValueIn('second');

// Valid: compatible conversion/extraction targets.
$meters->to('foot');
$meters->valueIn('foot');
$meters->intValueIn('foot');
$meters->exactIntValueIn('foot');

// Unknown receiver/target units fail open.
function convertUnknown(Quantity $quantity, string $unit): void
{
    $quantity->to($unit);
    $quantity->valueIn($unit);
    $quantity->intValueIn($unit);
    $quantity->exactIntValueIn($unit);
}
