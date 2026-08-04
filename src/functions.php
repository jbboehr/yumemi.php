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

namespace jbboehr\Yumemi;

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\NonMultiplicativeConversionException;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Exception\UnderflowException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;

/**
 * Brand a native int/float with a unit for static analysis (and light runtime checks).
 *
 * At runtime the magnitude is returned unchanged after validating that {@see $unit}
 * parses in the default catalog. PHPStan infers `unit_int<'…'>` or `unit_float<'…'>`
 * when the unit string is a constant.
 *
 * @param int|float $value
 * @return ($value is int ? int : float)
 * @api
 */
function unit(int|float $value, string $unit): int|float
{
    try {
        Units::default()->parse($unit);
    } catch (\Throwable $exception) {
        throw new InvalidArgumentException(
            'Invalid unit expression for unit(): ' . $exception->getMessage(),
            0,
            $exception,
        );
    }

    return $value;
}

/**
 * Return a native conversion factor branded as the target unit divided by the source unit.
 *
 * Multiplying a source-branded value by this factor lets PHPStan reduce the result to the
 * target unit. This native helper is limited to multiplicative units; use {@see unit_to()}
 * for affine conversions and {@see Units::conversionFactor()} for an exact Rational.
 * @api
 */
function unit_factor(string $from, string $to): float
{
    $units = Units::default();

    try {
        $fromUnit = $units->parse($from);
        $toUnit = $units->parse($to);

        $factor = $units->conversionFactor($fromUnit, $toUnit);
    } catch (IncompatibleUnitException|NonMultiplicativeConversionException|UnsupportedUnitAlgebraException $exception) {
        throw new InvalidArgumentException(
            'Cannot calculate unit_factor(): ' . $exception->getMessage(),
            0,
            $exception,
        );
    } catch (\Throwable $exception) {
        throw new InvalidArgumentException(
            'Invalid unit expression for unit_factor(): ' . $exception->getMessage(),
            0,
            $exception,
        );
    }

    return $factor->toFloat();
}

/**
 * Convert a native magnitude from one unit to another.
 *
 * Use when units share a dimension but not a normalized form (e.g. foot → meter,
 * mile/hour → meter/second). Definitionally identical units (kilometer vs 1000*meter)
 * do not require this for PHPStan assignment; conversion still yields a float.
 *
 * Always returns float. PHPStan brands a multiplicative target as unit_float<'$to'>
 * when both unit strings are constants and compatible; affine targets remain plain float.
 *
 * @param int|float $value
 * @api
 */
function unit_to(int|float $value, string $from, string $to): float
{
    $units = Units::default();

    if (is_float($value) && !is_finite($value)) {
        throw new InvalidArgumentException('unit_to() requires a finite input value.');
    }

    try {
        return is_int($value)
            ? $units->convert($value, $from, $to)->toFloat()
            : $units->convertFloat($value, $from, $to);
    } catch (OverflowException|UnderflowException $exception) {
        throw $exception;
    } catch (IncompatibleUnitException|UnsupportedUnitConversionException $exception) {
        throw new InvalidArgumentException(
            'Cannot convert with unit_to(): ' . $exception->getMessage(),
            0,
            $exception,
        );
    } catch (\Throwable $exception) {
        throw new InvalidArgumentException(
            'Invalid unit expression for unit_to(): ' . $exception->getMessage(),
            0,
            $exception,
        );
    }
}
