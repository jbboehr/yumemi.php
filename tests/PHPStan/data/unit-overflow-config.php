<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;
use function PHPStan\Testing\assertType;

$distance = unit(2, 'meter');
$otherDistance = unit(3, 'meter');
$duration = unit(4, 'second');

assertType("unit_int<'meter'>", $distance + $otherDistance);
assertType("unit_int<'meter'>", -$distance);
assertType("unit_int<'meter * second'>", $distance * $duration);
assertType("unit_int<'meter ^ 2'>", $distance ** 2);
