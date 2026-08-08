<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

$meter = unit(1, 'meter');
$foot = unit(2, 'foot');
$bare = 3.5;

min($meter, $foot);
max($foot, $meter);
min($meter, $bare);
max([$meter, $foot]);
min([$meter, $bare]);
max($meter, false);

/** @var array{unit_int<'meter'>}|array{unit_int<'foot'>} $constantArrayUnits */
$constantArrayUnits = random_int(0, 1) === 0 ? [$meter] : [$foot];
min($constantArrayUnits);

/** @var array{unit_int<'meter'>}|array{unit_int<'meter'>, float} $constantArrayBare */
$constantArrayBare = random_int(0, 1) === 0 ? [$meter] : [$meter, $bare];
max($constantArrayBare);

/** @var non-empty-list<unit_int<'meter'>|unit_int<'foot'>> $mixedUnits */
$mixedUnits = [$meter, $foot];
min($mixedUnits);
max(...$mixedUnits);

/** @var non-empty-list<unit_int<'meter'>|float> $mixedBare */
$mixedBare = [$meter, $bare];
min($mixedBare);
max(...$mixedBare);

min(unit(1, 'meter'), unit(2, 'm'));
max([unit(1, 'meter'), unit(2, 'm')]);
min(1, 2);
max($meter);
$minimum = min(...);

/** @var array{}|array{unit_int<'meter'>} $possiblyEmpty */
$possiblyEmpty = random_int(0, 1) === 0 ? [] : [$meter];
min($possiblyEmpty);

// @phpstan-ignore yumemi.invalidUnitSelection (exercise identifier-specific suppression)
min($meter, $foot);
