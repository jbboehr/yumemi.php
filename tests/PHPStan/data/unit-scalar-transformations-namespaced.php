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

function floatval(mixed $value): string
{
    return 'shadowed';
}

function doubleval(mixed $value): string
{
    return 'shadowed';
}

function intval(mixed $value, int $base = 10): string
{
    return 'shadowed:' . $base;
}

function fdiv(int|float $num1, int|float $num2): string
{
    return (string) ($num1 / $num2);
}

function fmod(int|float $num1, int|float $num2): string
{
    return (string) ($num1 % $num2);
}

function hypot(int|float $x, int|float $y): string
{
    return (string) \hypot($x, $y);
}

function sqrt(int|float $num): string
{
    return (string) $num;
}

function deg2rad(int|float $num): string
{
    return (string) $num;
}

function rad2deg(int|float $num): string
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
$degrees = unit(180, 'arc_degree');
$radians = unit(M_PI, 'radian');

assertType('string', abs($value));
assertType('string', round($value));
assertType('string', floatval($value));
assertType('string', doubleval($value));
assertType('string', intval($value));
assertType('string', fdiv($value, $value));
assertType('string', fmod($value, $value));
assertType('string', hypot($value, $value));
assertType('string', sqrt($area));
assertType('string', deg2rad($degrees));
assertType('string', rad2deg($radians));
assertType('string', min($value, $value));
assertType('string', max($value, $value));
assertType("3&unit_int<'meter'>", \abs($value));
assertType("-3.0&unit_float<'meter'>", ceil($value));
assertType("-3.0&unit_float<'meter'>", \floatval($value));
assertType("-3.0&unit_float<'meter'>", \doubleval($value));
assertType("-3&unit_int<'meter'>", \intval($value));
assertType("1.0&unit_float<'1'>", \fdiv($value, $value));
assertType("-0.0&unit_float<'meter'>", \fmod($value, $value));
assertType("4.242640687119285&unit_float<'meter'>", \hypot($value, $value));
assertType("2.0&unit_float<'meter'>", \sqrt($area));
assertType("3.141592653589793&unit_float<'radian'>", \deg2rad($degrees));
assertType("180.0&unit_float<'arc_degree'>", \rad2deg($radians));
assertType("-3&unit_int<'meter'>", \min($value, $value));
assertType("-3&unit_int<'meter'>", \max($value, $value));
