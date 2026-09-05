<?php

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

use function PHPStan\Testing\assertType;

/**
 * @param Quantity<'second'>|int          $integerOrQuantity
 * @param Quantity<'second'>|Rational     $rationalOrQuantity
 * @param Quantity<'second'>|int|Rational $factor
 */
function combineKnownQuantityOperands(
    Units $units,
    Quantity|int $integerOrQuantity,
    Quantity|Rational $rationalOrQuantity,
    Quantity|int|Rational $factor,
): void {
    $distance = $units->quantity(1, 'meter');
    assertType("Quantity<'meter * second'>|Quantity<'meter'>", $distance->mul($integerOrQuantity));
    assertType("Quantity<'meter / second'>|Quantity<'meter'>", $distance->div($integerOrQuantity));
    assertType("Quantity<'meter * second'>|Quantity<'meter'>", $distance->mul($rationalOrQuantity));
    assertType("Quantity<'meter / second'>|Quantity<'meter'>", $distance->div($rationalOrQuantity));
    assertType("Quantity<'meter * second'>|Quantity<'meter'>", $distance->mul($factor));
    assertType("Quantity<'meter / second'>|Quantity<'meter'>", $distance->div($factor));
}

function combineUnknownQuantityOperands(
    Units $units,
    Quantity|int $integerOrQuantity,
    Quantity|Rational $rationalOrQuantity,
    mixed $factor,
): void {
    $distance = $units->quantity(1, 'meter');
    assertType(Quantity::class, $distance->mul($integerOrQuantity));
    assertType(Quantity::class, $distance->div($integerOrQuantity));
    assertType(Quantity::class, $distance->mul($rationalOrQuantity));
    assertType(Quantity::class, $distance->div($rationalOrQuantity));
    assertType(Quantity::class, $distance->mul($factor));
    assertType(Quantity::class, $distance->div($factor));
}

function combineScalarQuantityOperands(Units $units, int|Rational $factor): void
{
    $distance = $units->quantity(1, 'meter');
    assertType("Quantity<'meter'>", $distance->mul($factor));
    assertType("Quantity<'meter'>", $distance->div($factor));
}

/**
 * @param Quantity<'ampere'>|Quantity<'meter'> $quantity
 * @param Quantity<'second'>|int              $factor
 */
function combineQuantityReceiverAndOperandUnions(Quantity $quantity, Quantity|int $factor): void
{
    assertType(
        "Quantity<'ampere * second'>|Quantity<'ampere'>|Quantity<'meter * second'>|Quantity<'meter'>",
        $quantity->mul($factor),
    );
    assertType(
        "Quantity<'ampere / second'>|Quantity<'ampere'>|Quantity<'meter / second'>|Quantity<'meter'>",
        $quantity->div($factor),
    );
}

/**
 * @param Quantity<'ampere'>|Quantity<'meter'>                    $quantity
 * @param Quantity<'kilogram'>|Quantity<'second'>|Rational        $factor
 */
function combineEveryFiniteReceiverAndOperandAlternative(Quantity $quantity, Quantity|Rational $factor): void
{
    assertType(
        "Quantity<'ampere * kilogram'>|Quantity<'ampere * second'>|Quantity<'ampere'>|Quantity<'kilogram * meter'>|Quantity<'meter * second'>|Quantity<'meter'>",
        $quantity->mul($factor),
    );
    assertType(
        "Quantity<'ampere / kilogram'>|Quantity<'ampere / second'>|Quantity<'ampere'>|Quantity<'meter / kilogram'>|Quantity<'meter / second'>|Quantity<'meter'>",
        $quantity->div($factor),
    );
}
