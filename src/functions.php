<?php

namespace jbboehr\Yumemi;

use jbboehr\Yumemi\Exception\IncompatibleUnitException;

/**
 * Brand a native int/float with a unit for static analysis (and light runtime checks).
 *
 * At runtime the magnitude is returned unchanged after validating that {@see $unit}
 * parses in the default catalog. PHPStan infers `unit_int<'…'>` or `unit_float<'…'>`
 * when the unit string is a constant.
 *
 * @param int|float $value
 * @return ($value is int ? int : float)
 */
function unit(int|float $value, string $unit): int|float
{
    try {
        Units::default()->parse($unit);
    } catch (\Throwable $exception) {
        throw new \InvalidArgumentException(
            'Invalid unit expression for unit(): ' . $exception->getMessage(),
            0,
            $exception,
        );
    }

    return $value;
}

/**
 * Convert a native magnitude from one unit to another (catalog scale factors).
 *
 * Use when units share a dimension but not a normalized form (e.g. foot → meter,
 * mile/hour → meter/second). Definitionally identical units (kilometer vs 1000*meter)
 * do not require this for PHPStan assignment; conversion still yields a float.
 *
 * Always returns float (conversion factors are often non-integral). PHPStan brands
 * the result as unit_float<'$to'> when both unit strings are constants and compatible.
 *
 * @param int|float $value
 */
function unit_to(int|float $value, string $from, string $to): float
{
    $units = Units::default();

    try {
        $factor = $units->conversionFactor($from, $to);
    } catch (IncompatibleUnitException $exception) {
        throw new \InvalidArgumentException(
            'Cannot convert with unit_to(): ' . $exception->getMessage(),
            0,
            $exception,
        );
    } catch (\Throwable $exception) {
        throw new \InvalidArgumentException(
            'Invalid unit expression for unit_to(): ' . $exception->getMessage(),
            0,
            $exception,
        );
    }

    $numerator = (float) gmp_strval($factor->numerator);
    $denominator = (float) gmp_strval($factor->denominator);

    return ((float) $value) * ($numerator / $denominator);
}
