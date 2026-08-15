<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;

deg2rad(unit(1.0, 'radian'));
deg2rad(num: unit(1.0, 'radian'));

// @phpstan-ignore yumemi.invalidUnitAngleFunction (exercise identifier-specific suppression)
rad2deg(unit(1.0, 'arc_degree'));
