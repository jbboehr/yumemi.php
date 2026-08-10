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

// Exact roots require a positive bounded degree and a perfect symbolic unit power.
$meters->root(2);
$units->quantity(4, 'meter^2')->root(0);
$units->quantity(4, 'meter^2')->root(10_001);

// Power diagnostics also surface exponent overflow from the same arithmetic rule.
$meters->pow(10_001);
$units->quantity(1, 'meter^100')->pow(101);

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

/** @param Quantity<'meter'>|Quantity<'second'> $quantity */
function addReceiverUnion(Quantity $quantity): void
{
    $quantity->add(Units::default()->quantity(1, 'meter'));
}

/** @param Quantity<'international_foot'>|Quantity<'second'> $other */
function addOperandUnion(Quantity $other): void
{
    Units::default()->quantity(1, 'meter')->add($other);
}

// @phpstan-ignore yumemi.invalidQuantityArithmetic (exercise identifier-specific suppression)
$meters->add($seconds);
