<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

$units = Units::default();
$meters = $units->quantity(1, 'meter');
$seconds = $units->quantity(1, 'second');
$feet = $units->quantity(1, 'foot');

$meters->compareTo($seconds);
$meters->equals($seconds);
$meters->lessThan($seconds);
$meters->lessThanOrEqualTo($seconds);
$meters->greaterThan($seconds);
$meters->greaterThanOrEqualTo($seconds);

// Valid: comparisons convert compatible dimensions.
$meters->compareTo($feet);
$meters->equals($feet);
$meters->lessThan($feet);
$meters->lessThanOrEqualTo($feet);
$meters->greaterThan($feet);
$meters->greaterThanOrEqualTo($feet);

// Unbranded operands fail open because their units are not statically known.
function compareUnknown(Quantity $unknown): void
{
    $meters = Units::default()->quantity(1, 'meter');

    $meters->compareTo($unknown);
    $meters->equals($unknown);
    $meters->lessThan($unknown);
    $meters->lessThanOrEqualTo($unknown);
    $meters->greaterThan($unknown);
    $meters->greaterThanOrEqualTo($unknown);
}

/** @param Quantity<'international_foot'>|Quantity<'second'> $other */
function compareOperandUnion(Quantity $other): void
{
    Units::default()->quantity(1, 'meter')->equals($other);
}
