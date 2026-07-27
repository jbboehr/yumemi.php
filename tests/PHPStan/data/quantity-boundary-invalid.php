<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Data\QuantityBoundaryInvalid;

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;

$units = Units::default();
$units->quantity(unit(3, 'foot'), 'meter');

$meters = $units->quantity(1, 'meter');
$meters->to('second');
