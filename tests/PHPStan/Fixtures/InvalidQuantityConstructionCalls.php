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

// Valid: bare integer, alias, and normalized-equivalent target.
$units->quantity(3, 'meter');
$units->quantity($feet, 'international_foot');
$units->quantity(unit(2, 'kilometer'), '1000 * meter');
