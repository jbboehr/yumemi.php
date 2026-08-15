<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

fmod(unit(7.0, 'meter'), unit(3.0, 'second'));
hypot(unit(3.0, 'meter'), 4.0);

/** @var unit_int<'meter'>|float $mixed */
$mixed = 3.0;
hypot($mixed, unit(4.0, 'meter'));

fdiv(unit(3.0, 'meter'), 2.0);
fdiv(unit(1.0, 'meter ^ 10000'), unit(1.0, 'meter ^ -10000'));
pow(unit(2.0, 'meter'), 2.0);
pow(unit(2.0, 'meter'), unit(2, 'second'));
pow(unit(2.0, 'meter ^ 10000'), 2);

function raiseWithDynamicExponent(int $exponent): void
{
    pow(unit(2.0, 'meter'), $exponent);
}

// @phpstan-ignore yumemi.invalidUnitMathFunction (exercise identifier-specific suppression)
fmod(unit(7.0, 'second'), unit(3.0, 'meter'));

// @phpstan-ignore yumemi.invalidUnitMathFunction (exercise power suppression)
pow(unit(2.0, 'meter'), 0.5);
