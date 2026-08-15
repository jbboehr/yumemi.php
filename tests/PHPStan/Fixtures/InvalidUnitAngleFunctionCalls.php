<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

deg2rad(unit(1.0, 'radian'));
rad2deg(unit(1.0, 'arc_degree'));
deg2rad(num: unit(1.0, 'radian'));
sin(unit(1.0, 'arc_degree'));
cos(num: unit(1.0, 'steradian'));
asin(unit(0.5, 'percent'));
atan(num: unit(1.0, 'radian'));
asin(unit(0.5, '2'));

deg2rad(unit(90, 'degree'));
rad2deg(unit(1.0, 'rad'));
sin(unit(0.0, 'radian'));
acos(unit(0.5, 'meter / meter'));
deg2rad(90);
sin(0.5);
$angleFunction = deg2rad(...);
$trigFunction = sin(...);

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

/** @param unit_float<'1'>|unit_float<'count'> $ratio */
function invertPossibleNamedRatio(float $ratio): void
{
    asin($ratio);
}

// @phpstan-ignore yumemi.invalidUnitAngleFunction (exercise identifier-specific suppression)
rad2deg(unit(1.0, 'steradian'));

// @phpstan-ignore yumemi.invalidUnitAngleFunction (exercise inverse-function suppression)
acos(unit(0.5, 'count'));
