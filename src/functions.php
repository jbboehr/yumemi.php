<?php

namespace jbboehr\IudexMensurarumMysteriorum;

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
