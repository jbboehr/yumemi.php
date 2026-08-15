<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

deg2rad(unit(1.0, 'radian'));
rad2deg(unit(1.0, 'arc_degree'));
deg2rad(num: unit(1.0, 'radian'));

deg2rad(unit(90, 'degree'));
rad2deg(unit(1.0, 'rad'));
deg2rad(90);
$angleFunction = deg2rad(...);

/** @var unit_int<'arc_degree'>|unit_float<'degree_north'> $mixed */
$mixed = unit(90, 'arc_degree');
deg2rad($mixed);

/** @param unit_float<'arc_degree'>|unit_float<'degree_north'> $angle */
function convertPossibleDirectionalAngle(float $angle): void
{
    deg2rad($angle);
}

/** @param unit_float<'degree_north'>|unit_float<'arc_degree'> $angle */
function convertReversedPossibleDirectionalAngle(float $angle): void
{
    deg2rad($angle);
}

// @phpstan-ignore yumemi.invalidUnitAngleFunction (exercise identifier-specific suppression)
rad2deg(unit(1.0, 'steradian'));
