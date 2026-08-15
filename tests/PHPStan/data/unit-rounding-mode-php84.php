<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;
use function PHPStan\Testing\assertType;

assertType(
    "4.0&unit_float<'meter'>",
    round(num: unit(3.5, 'meter'), precision: 0, mode: RoundingMode::HalfEven),
);
assertType(
    "1.0&unit_float<'meter'>",
    round(unit(1.5, 'meter'), 0, RoundingMode::HalfTowardsZero),
);
