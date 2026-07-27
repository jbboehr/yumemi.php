<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Data\ConfiguredUnitRegistry;

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;
use function PHPStan\Testing\assertType;

$widget = unit(2, 'widget');
assertType("unit_int<'widget'>", $widget);

$widgets = unit(3, 'widgets');
assertType("unit_int<'widget'>", $widgets);

$meters = unit_to($widget, 'widget', 'meter');
assertType("unit_float<'meter'>", $meters);

$area = $widget * $widgets;
assertType("unit_int<'widget ^ 2'>", $area);

$quantity = Units::default()->quantity(1, 'widget');
assertType("Quantity<'widget'>", $quantity);
