<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Data\ConfiguredUnitRegistry;

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_factor;
use function jbboehr\Yumemi\unit_to;
use function PHPStan\Testing\assertType;

$widget = unit(2, 'widget');
assertType("2&unit_int<'widget'>", $widget);

$widgets = unit(3, 'widgets');
assertType("3&unit_int<'widget'>", $widgets);

/** @param 'widget'|'meter' $unit */
function configuredFiniteUnits(string $unit): void
{
    // @phpstan-ignore yumemi.ambiguousUnitExpression (exercise configured-registry union inference)
    assertType("1&unit_int<'meter'>|1&unit_int<'widget'>", unit(1, $unit));
}

/** @param 'widget'|'widgets' $unit */
function configuredEquivalentUnits(string $unit): void
{
    assertType("1&unit_int<'widget'>", unit(1, $unit));
}

$meters = unit_to($widget, 'widget', 'meter');
assertType("unit_float<'meter'>", $meters);

$widgetToMeter = unit_factor('widget', 'meter');
assertType("unit_float<'meter / widget'>", $widgetToMeter);
assertType("unit_float<'meter'>", $widget * $widgetToMeter);

assertType("unit_float<'kelvin'>", unit_to(0, 'degree_widget', 'kelvin'));
assertType('float', unit_to(100, 'kelvin', 'degree_widget'));

$euros = unit(107, 'EUR');
assertType("107&unit_int<'EUR'>", $euros);
assertType("unit_float<'USD'>", unit_to($euros, 'EUR', 'USD'));
assertType("unit_float<'USD / EUR'>", unit_factor('EUR', 'USD'));

$dollars = Units::default()->quantity(100, 'USD');
$euroQuantity = Units::default()->quantity(107, 'EUR');
assertType("Quantity<'USD'>", $dollars->add($euroQuantity));
assertType("Quantity<'USD'>", $euroQuantity->to('USD'));

$area = $widget * $widgets;
assertType("6&unit_int<'widget ^ 2'>", $area);

$quantity = Units::default()->quantity(1, 'widget');
assertType("Quantity<'widget'>", $quantity);

$quantityFromBrandedValue = Units::default()->quantity($widget, 'widgets');
assertType("Quantity<'widget'>", $quantityFromBrandedValue);

assertType("Quantity<'meter'>", $quantity->to('meter'));
assertType("unit_int<'meter'>", $quantity->intValueIn('meter'));
assertType("unit_int<'widget'>", $quantity->exactIntValueIn('widgets'));

/** @param 'widget'|'meter' $unit */
function configuredFiniteTargets(Units $units, string $unit): void
{
    assertType("Quantity<'meter'>|Quantity<'widget'>", $units->quantity(1, $unit));

    $quantity = $units->quantity(1, 'widget');
    assertType("Quantity<'meter'>|Quantity<'widget'>", $quantity->to($unit));
    assertType("unit_int<'meter'>|unit_int<'widget'>", $quantity->intValueIn($unit));
}

/** @param 'widget'|'widgets' $unit */
function configuredEquivalentTargets(Units $units, string $unit): void
{
    assertType("Quantity<'widget'>", $units->quantity(unit(1, 'widget'), $unit));
}
