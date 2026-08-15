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
atan2(unit(1.0, 'meter'), unit(1.0, 'second'));
atan2(unit(1.0, 'meter'), unit(1.0, 'foot'));
atan2(y: unit(1.0, 'meter'), x: 1.0);

deg2rad(unit(90, 'degree'));
rad2deg(unit(1.0, 'rad'));
sin(unit(0.0, 'radian'));
acos(unit(0.5, 'meter / meter'));
deg2rad(90);
sin(0.5);
atan2(unit(3, 'meter'), unit(4, '100 * centimeter'));
atan2(1.0, 1.0);
$angleFunction = deg2rad(...);
$trigFunction = sin(...);
$directionFunction = atan2(...);

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

/** @param unit_float<'meter'>|unit_float<'second'> $y */
function directPossibleIncompatibleDirection(float $y): void
{
    atan2($y, unit(1.0, 'meter'));
}

// @phpstan-ignore yumemi.invalidUnitAngleFunction (exercise identifier-specific suppression)
rad2deg(unit(1.0, 'steradian'));

// @phpstan-ignore yumemi.invalidUnitAngleFunction (exercise inverse-function suppression)
acos(unit(0.5, 'count'));

// @phpstan-ignore yumemi.invalidUnitAngleFunction (exercise binary-function suppression)
atan2(unit(1.0, 'meter'), unit(1.0, 'foot'));
