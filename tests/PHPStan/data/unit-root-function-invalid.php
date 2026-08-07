<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;

$length = unit(1.0, 'meter');

$invalidRoot = sqrt($length);

// @phpstan-ignore yumemi.invalidUnitRoot
$ignoredInvalidRoot = sqrt($length);

$validRoot = sqrt(unit(4.0, 'meter^2'));
$ordinaryRoot = sqrt(4.0);
