<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

$length = unit(1.0, 'meter');
$mixedLengthSquare = unit(1.0, 'kilometer * millimeter');

sqrt($length);
sqrt($mixedLengthSquare);

sqrt(unit(4.0, 'meter^2'));
sqrt(4.0);
$squareRootFunction = sqrt(...);

/** @param unit_float<'second'>|unit_int<'meter'> $value */
function rejectInvalidRootUnion(float|int $value): void
{
    sqrt($value);
}

/** @param unit_int<'meter'>|float $value */
function rejectInvalidMixedRootUnion(int|float $value): void
{
    sqrt($value);
}
