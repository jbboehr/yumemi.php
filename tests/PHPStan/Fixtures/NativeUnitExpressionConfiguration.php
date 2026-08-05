<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_factor;
use function jbboehr\Yumemi\unit_to;

/** @param 'foot'|'meter' $ambiguous */
function nativeUnitExpressionConfiguration(string $dynamic, string $ambiguous): void
{
    unit(1.0, $dynamic);
    unit_factor($dynamic, 'meter');
    unit_to(1.0, $dynamic, 'meter');
    unit(1.0, $ambiguous);
    unit(1.0, 'not_a_real_unit_xyz');
}
