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

function pow(int|float $num, int|float $exponent): string
{
    return (string) ($num ** $exponent);
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

function sin(int|float $num): string
{
    return (string) $num;
}

function cos(int|float $num): string
{
    return (string) $num;
}

function tan(int|float $num): string
{
    return (string) $num;
}

function asin(int|float $num): string
{
    return (string) $num;
}

function acos(int|float $num): string
{
    return (string) $num;
}

function atan(int|float $num): string
{
    return (string) $num;
}

function atan2(int|float $y, int|float $x): string
{
    return (string) ($y / $x);
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
$ratio = unit(0.5, '1');

assertType('string', abs($value));
assertType('string', round($value));
assertType('string', floatval($value));
assertType('string', doubleval($value));
assertType('string', intval($value));
assertType('string', fdiv($value, $value));
assertType('string', fmod($value, $value));
assertType('string', hypot($value, $value));
assertType('string', pow($value, 2));
assertType('string', sqrt($area));
assertType('string', deg2rad($degrees));
assertType('string', rad2deg($radians));
assertType('string', sin($radians));
assertType('string', cos($radians));
assertType('string', tan($radians));
assertType('string', asin($ratio));
assertType('string', acos($ratio));
assertType('string', atan($ratio));
assertType('string', atan2($value, $value));
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
assertType("9&unit_int<'meter ^ 2'>", \pow($value, 2));
assertType("2.0&unit_float<'meter'>", \sqrt($area));
assertType("3.141592653589793&unit_float<'radian'>", \deg2rad($degrees));
assertType("180.0&unit_float<'arc_degree'>", \rad2deg($radians));
assertType("1.2246467991473532E-16&unit_float<'1'>", \sin($radians));
assertType("-1.0&unit_float<'1'>", \cos($radians));
assertType("-1.2246467991473532E-16&unit_float<'1'>", \tan($radians));
assertType("0.5235987755982989&unit_float<'radian'>", \asin($ratio));
assertType("1.0471975511965979&unit_float<'radian'>", \acos($ratio));
assertType("0.4636476090008061&unit_float<'radian'>", \atan($ratio));
assertType("-2.356194490192345&unit_float<'radian'>", \atan2($value, $value));
assertType("-3&unit_int<'meter'>", \min($value, $value));
assertType("-3&unit_int<'meter'>", \max($value, $value));
