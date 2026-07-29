<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;

$units = Units::default();
$feet = unit(3, 'foot');
$seconds = unit(3, 'second');

// quantity() treats the magnitude as already expressed in the target unit; it does not convert.
$units->quantity($feet, 'meter');
$units->quantity($seconds, 'meter');
$units->quantity(1, 'not_a_real_unit_xyz');

/** @param 'meter'|'foot' $unit */
function constructWithPossibleMismatch(Units $units, string $unit): void
{
    $units->quantity(unit(1, 'meter'), $unit);
}

// Valid: bare integer, alias, and normalized-equivalent target.
$units->quantity(3, 'meter');
$units->quantity($feet, 'international_foot');
$units->quantity(unit(2, 'kilometer'), '1000 * meter');

$units->quantity(1, 'meter * / second');
$units->quantity(1, 'B');
