<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

$units = Units::default();
$meters = $units->quantity(1, 'meter');
$seconds = $units->quantity(1, 'second');
$feet = $units->quantity(1, 'foot');

// Converting addition/subtraction require compatible dimensions.
$meters->add($seconds);
$meters->sub($seconds);

// Same-unit addition/subtraction require normalized-equivalent units, including scale.
$meters->addWithSameUnit($feet);
$meters->subWithSameUnit($feet);

// Valid: converting methods accept compatible dimensions.
$meters->add($feet);
$meters->sub($feet);

// Valid: strict methods accept definitionally equivalent units.
$kilometers = $units->quantity(1, 'kilometer');
$thousandMeters = $units->quantity(1, '1000 * meter');
$kilometers->addWithSameUnit($thousandMeters);
$kilometers->subWithSameUnit($thousandMeters);

// Unbranded operands fail open because their units are not statically known.
function addUnknown(Quantity $unknown): void
{
    Units::default()->quantity(1, 'meter')->add($unknown);
    Units::default()->quantity(1, 'meter')->addWithSameUnit($unknown);
}
