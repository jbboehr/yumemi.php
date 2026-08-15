<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;

deg2rad(unit(1.0, 'radian'));
deg2rad(num: unit(1.0, 'radian'));
sin(unit(1.0, 'arc_degree'));
cos(num: unit(1.0, 'steradian'));
asin(unit(0.5, 'percent'));
atan(num: unit(1.0, 'radian'));

// @phpstan-ignore yumemi.invalidUnitAngleFunction (exercise identifier-specific suppression)
rad2deg(unit(1.0, 'arc_degree'));

// @phpstan-ignore yumemi.invalidUnitAngleFunction (exercise inverse-function suppression)
acos(unit(0.5, 'count'));
