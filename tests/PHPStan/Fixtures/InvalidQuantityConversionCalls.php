<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

$meters = Units::default()->quantity(1, 'meter');

$meters->to('second');
$meters->valueIn('second');
$meters->intValueIn('second');
$meters->exactIntValueIn('second');
$meters->decimalValueIn('second', 2, \RoundingMode::HalfEven);
$meters->significantDecimalValueIn('second', 3, \RoundingMode::HalfEven);
$meters->exactDecimalValueIn('second');
$meters->floatValueIn('second');
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
$meters->decimalValueIn('foot', 2, \RoundingMode::HalfEven);
$meters->significantDecimalValueIn('foot', 3, \RoundingMode::HalfEven);
$meters->exactDecimalValueIn('foot');
$meters->floatValueIn('foot');
$meters->to('meter * / second');
$meters->to('degree_Celsius');

// Genuinely dynamic targets fail open, including on an unbranded receiver.
function convertUnknown(Quantity $quantity, string $unit): void
{
    $quantity->to($unit);
    $quantity->valueIn($unit);
    $quantity->intValueIn($unit);
    $quantity->exactIntValueIn($unit);
    $quantity->decimalValueIn($unit, 2, \RoundingMode::HalfEven);
    $quantity->significantDecimalValueIn($unit, 3, \RoundingMode::HalfEven);
    $quantity->exactDecimalValueIn($unit);
    $quantity->floatValueIn($unit);
}

/** @param Quantity<'meter'>|Quantity<'second'> $quantity */
function convertReceiverUnion(Quantity $quantity): void
{
    $quantity->to('foot');
}

// @phpstan-ignore yumemi.invalidQuantityConversion (exercise identifier-specific suppression)
$meters->to('second');
