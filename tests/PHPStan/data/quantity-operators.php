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
assertType("Quantity<'meter / second ^ 2'>", ($meters + $feet) / $seconds ** 2);

$compoundLength = $meters;
$compoundLength += $feet;
assertType("Quantity<'meter'>", $compoundLength);

$compoundDifference = $meters;
$compoundDifference -= $feet;
assertType("Quantity<'meter'>", $compoundDifference);

$compoundProduct = $meters;
$compoundProduct *= $seconds;
assertType("Quantity<'meter * second'>", $compoundProduct);

$compoundQuotient = $meters;
$compoundQuotient /= $seconds;
assertType("Quantity<'meter / second'>", $compoundQuotient);

$compoundPower = $meters;
$compoundPower **= -1;
assertType("Quantity<'1 / meter'>", $compoundPower);

$scalarLeftProduct = 2;
$scalarLeftProduct *= $meters;
assertType("Quantity<'meter'>", $scalarLeftProduct);

$scalarLeftQuotient = 2;
$scalarLeftQuotient /= $meters;
assertType("Quantity<'1 / meter'>", $scalarLeftQuotient);

$rationalLeftQuotient = $rational;
$rationalLeftQuotient /= $meters;
assertType("Quantity<'1 / meter'>", $rationalLeftQuotient);

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
