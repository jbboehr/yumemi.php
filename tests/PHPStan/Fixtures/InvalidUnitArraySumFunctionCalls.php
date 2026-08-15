<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

$meter = unit(1, 'meter');
$foot = unit(2, 'foot');
$bare = 3.5;

array_sum([$meter, $foot]);
array_sum([$meter, $bare]);

/** @var list<unit_int<'meter'>|unit_int<'foot'>> $mixedUnits */
$mixedUnits = [$meter, $foot];
array_sum($mixedUnits);

/** @var list<unit_int<'meter'>|float> $mixedBare */
$mixedBare = [$meter, $bare];
array_sum($mixedBare);

array_sum([unit(1, 'meter'), unit(2, 'm')]);
array_sum([1, 2]);
array_sum([]);
array_sum([[$meter]]);

// @phpstan-ignore yumemi.invalidUnitAggregation (exercise identifier-specific suppression)
array_sum([$meter, $foot]);
