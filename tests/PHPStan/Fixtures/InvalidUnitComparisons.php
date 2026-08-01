<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

$meters = unit(1, 'meter');
$seconds = unit(1, 'second');
$feet = unit(1, 'foot');

$meters == $seconds;
$meters != $seconds;
$meters === $seconds;
$meters !== $seconds;
$meters < $seconds;
$meters <= $seconds;
$meters > $seconds;
$meters >= $seconds;
$meters <=> $seconds;
$meters == $feet;
$meters == 1;
1 < $meters;

// Valid: every native comparison pair is definitionally equivalent.
$meters == unit(1, 'meter');
$meters === unit(1, '100 * centimeter');
$meters <=> unit(1.0, 'meter');

/** @param unit_int<'meter'>|unit_int<'second'> $value */
function compareUnitUnion(int $value): void
{
    $value == unit(1, 'meter');
}

/** @param int|unit_int<'meter'> $value */
function compareMixedUnion(int $value): void
{
    $value == unit(1, 'meter');
}
