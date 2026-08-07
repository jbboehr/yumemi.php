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

function sqrt(int|float $num): string
{
    return (string) $num;
}

function min(int|float ...$values): string
{
    return implode(',', $values);
}

function max(int|float ...$values): string
{
    return implode(',', $values);
}

$value = unit(-3, 'meter');
$area = unit(4, 'meter^2');

assertType('string', abs($value));
assertType('string', round($value));
assertType('string', sqrt($area));
assertType('string', min($value, $value));
assertType('string', max($value, $value));
assertType("3&unit_int<'meter'>", \abs($value));
assertType("unit_float<'meter'>", ceil($value));
assertType("unit_float<'meter'>", \sqrt($area));
assertType("-3&unit_int<'meter'>", \min($value, $value));
assertType("-3&unit_int<'meter'>", \max($value, $value));
