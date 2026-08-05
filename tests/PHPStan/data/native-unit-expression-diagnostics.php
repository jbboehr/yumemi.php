<?php

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_factor;
use function jbboehr\Yumemi\unit_to;

/** @param 'foot'|'meter' $ambiguous */
function exerciseNativeUnitExpressionDiagnostics(string $dynamic, string $ambiguous): void
{
    unit(1.0, $dynamic);
    unit_factor($dynamic, 'meter');
    unit_to(1.0, $dynamic, 'meter');
    unit(1.0, $ambiguous);

    // @phpstan-ignore yumemi.dynamicUnitExpression (deliberately dynamic runtime assertion)
    unit(1.0, $dynamic);
    // @phpstan-ignore yumemi.ambiguousUnitExpression (deliberately retained union)
    unit(1.0, $ambiguous);

    $units = Units::default();
    $quantity = $units->quantity(1, $dynamic);
    $quantity->to($dynamic);
}
