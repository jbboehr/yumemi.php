<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;
use function PHPStan\Testing\assertType;

$distance = unit(2, 'meter');
$otherDistance = unit(3, 'meter');
$duration = unit(4, 'second');

assertType("5&unit_int<'meter'>", $distance + $otherDistance);
assertType("-2&unit_int<'meter'>", -$distance);
assertType("8&unit_int<'meter * second'>", $distance * $duration);
assertType("4&unit_int<'meter ^ 2'>", $distance ** 2);
