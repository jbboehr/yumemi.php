<?php

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

use function PHPStan\Testing\assertType;

$units = Units::default();
$meters = $units->quantity(1, 'meter');
$feet = $units->quantity(1, 'foot');
$seconds = $units->quantity(1, 'second');
$rational = new Rational(3, 2);

assertType("Quantity<'meter'>", $meters + $feet);
assertType("Quantity<'meter'>", $meters - $feet);

assertType("Quantity<'meter * second'>", $meters * $seconds);
assertType("Quantity<'meter / second'>", $meters / $seconds);
assertType("Quantity<'meter'>", $meters * 2);
assertType("Quantity<'meter'>", $meters / 2);
assertType("Quantity<'meter'>", 2 * $meters);
assertType("Quantity<'meter'>", $rational * $meters);
assertType("Quantity<'meter'>", $meters * $rational);
assertType("Quantity<'meter'>", $meters / $rational);
assertType("Quantity<'1 / meter'>", 2 / $meters);
assertType("Quantity<'1 / meter'>", $rational / $meters);

assertType("Quantity<'meter ^ 2'>", $meters ** 2);
assertType("Quantity<'1 / meter'>", $meters ** -1);

/**
 * @param Quantity<'international_foot'>|Quantity<'meter'> $length
 * @param Quantity<'second'>                                $seconds
 */
function inspectQuantityOperatorUnion(Quantity $length, Quantity $seconds): void
{
    assertType("Quantity<'international_foot'>|Quantity<'meter'>", $length + $length);
    assertType("Quantity<'international_foot * second'>|Quantity<'meter * second'>", $length * $seconds);
}

/** @param Quantity<'meter'> $meters */
function inspectDynamicQuantityPower(Quantity $meters, int $power): void
{
    assertType(Quantity::class, $meters ** $power);
}

function inspectUnbrandedQuantityOperators(Quantity $quantity): void
{
    assertType(Quantity::class, $quantity * 2);
    assertType(Quantity::class, 2 / $quantity);
}
