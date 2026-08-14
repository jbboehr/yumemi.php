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

// @phpstan-ignore yumemi.invalidUnitMathFunction (exercise identifier-specific suppression)
fmod(unit(7.0, 'second'), unit(3.0, 'meter'));
