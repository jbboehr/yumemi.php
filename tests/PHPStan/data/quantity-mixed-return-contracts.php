<?php

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

/**
 * @param Quantity<'second'>|int $factor
 * @return Quantity<'meter'>
 */
function scaleDistance(Units $units, Quantity|int $factor): Quantity
{
    return $units->quantity(1, 'meter')->mul($factor);
}

/**
 * @param Quantity<'second'>|int $factor
 * @return Quantity<'meter'>
 */
function divideDistance(Units $units, Quantity|int $factor): Quantity
{
    return $units->quantity(1, 'meter')->div($factor);
}

/**
 * @param Quantity<'second'>|int $factor
 * @return Quantity<'meter * second'>|Quantity<'meter'>
 */
function scaleWithOptionalDuration(Units $units, Quantity|int $factor): Quantity
{
    return $units->quantity(1, 'meter')->mul($factor);
}

/**
 * @param Quantity<'second'>|int $factor
 * @return Quantity<'meter / second'>|Quantity<'meter'>
 */
function divideWithOptionalDuration(Units $units, Quantity|int $factor): Quantity
{
    return $units->quantity(1, 'meter')->div($factor);
}

/** @return Quantity<'meter'> */
function scaleByInteger(Units $units, int $factor): Quantity
{
    return $units->quantity(1, 'meter')->mul($factor);
}
