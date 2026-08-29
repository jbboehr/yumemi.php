<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

$units = Units::default();
$meters = $units->quantity(1, 'meter');
$feet = $units->quantity(1, 'foot');
$meters == $feet;
$meters != $feet;
$meters === $feet;
$meters !== $feet;
$meters < $feet;
$meters <= $feet;
$meters > $feet;
$meters >= $feet;
$meters <=> $feet;

$celsius = $units->point(0, 'celsius');
$fahrenheit = $units->point(32, 'fahrenheit');
$celsius == $fahrenheit;
$celsius != $fahrenheit;
$celsius === $fahrenheit;
$celsius !== $fahrenheit;
$celsius < $fahrenheit;
$celsius <= $fahrenheit;
$celsius > $fahrenheit;
$celsius >= $fahrenheit;
$celsius <=> $fahrenheit;

$units->quantity(1, 'meter') < $units->quantity(100, 'centimeter');
0 == $celsius;

/** @param Quantity<'meter'>|int $value */
function compareQuantityUnion(Quantity|int $value): bool
{
    return $value == 0;
}

/** @param PointQuantity<'celsius'>|string $value */
function comparePointUnion(PointQuantity|string $value): bool
{
    return '' < $value;
}

/** @param Quantity<'meter'>|null $value */
function compareNullableQuantity(?Quantity $value): bool
{
    return 0 >= $value;
}

/** @param Quantity<'meter'>&\JsonSerializable $value */
function compareQuantityIntersection(Quantity&\JsonSerializable $value): bool
{
    return $value !== 0;
}

0 <=> $units->quantity(1, 'meter');

// Valid: use Yumemi's unit-aware method API.
$meters->equals($feet);
$celsius->compareTo($fahrenheit);
$meters->lessThan($feet);

// Valid: unrelated native values remain PHP's concern.
1 < 2;
new \stdClass() == new \stdClass();
$meters + $feet;
$nativeMeters = \jbboehr\Yumemi\unit(1, 'meter');
$nativeFeet = \jbboehr\Yumemi\unit(1, 'foot');
$nativeMeters == $nativeFeet;

function compareCompletelyUnknown(mixed $left, mixed $right): bool
{
    return ($left <=> $right) === 0;
}

/** @param Quantity<'meter'>|null $value */
function quantityIsAbsent(?Quantity $value): bool
{
    $looselyAbsent = $value == null;
    $looselyPresent = null != $value;
    $strictlyAbsent = $value === null;
    $strictlyPresent = null !== $value;

    return $looselyAbsent || $looselyPresent || $strictlyAbsent || $strictlyPresent;
}

/** @param PointQuantity<'celsius'>|null $value */
function pointQuantityIsPresent(?PointQuantity $value): bool
{
    $looselyAbsent = null == $value;
    $looselyPresent = $value != null;
    $strictlyAbsent = null === $value;
    $strictlyPresent = $value !== null;

    return $looselyAbsent || $looselyPresent || $strictlyAbsent || $strictlyPresent;
}

/** @param Quantity<'meter'>|null $value */
function orderNullableQuantityAgainstNull(?Quantity $value): int
{
    $value < null;
    null <= $value;
    $value > null;
    null >= $value;

    return $value <=> null;
}

/** @param PointQuantity<'celsius'>|null $value */
function orderNullAgainstNullablePointQuantity(?PointQuantity $value): int
{
    null < $value;
    $value <= null;
    null > $value;
    $value >= null;

    return null <=> $value;
}

/** @param Quantity<'meter'>|null $quantity */
function compareQuantityToNullableScalar(?Quantity $quantity, ?int $scalar): bool
{
    return $quantity === $scalar;
}

/** @param PointQuantity<'celsius'>|null $point */
function compareNullableScalarToPoint(?string $scalar, ?PointQuantity $point): bool
{
    return $scalar != $point;
}

/**
 * @param Quantity<'meter'>|null $quantity
 * @param PointQuantity<'celsius'>|null $point
 */
function compareNullableRuntimeQuantityModels(?Quantity $quantity, ?PointQuantity $point): bool
{
    return $quantity == $point;
}

/** @param Quantity<'meter'>|int|null $value */
function widerQuantityUnionIsAbsent(Quantity|int|null $value): bool
{
    return null == $value;
}

/** @param PointQuantity<'celsius'>|string|null $value */
function widerPointQuantityUnionIsPresent(PointQuantity|string|null $value): bool
{
    return $value !== null;
}

// @phpstan-ignore yumemi.nativeQuantityComparison (exercise identifier-specific suppression)
$meters == $feet;
