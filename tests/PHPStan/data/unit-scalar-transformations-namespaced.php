<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures\ScalarFunctionShadow;

use function jbboehr\Yumemi\unit;
use function PHPStan\Testing\assertType;

function abs(int|float $num): string
{
    return (string) $num;
}

function round(int|float $num): string
{
    return (string) $num;
}

$value = unit(-3, 'meter');

assertType('string', abs($value));
assertType('string', round($value));
assertType("3&unit_int<'meter'>", \abs($value));
assertType("unit_float<'meter'>", ceil($value));
