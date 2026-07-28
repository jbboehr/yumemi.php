<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$meters = $units->quantity(1, 'meter');
$seconds = $units->quantity(1, 'second');

$meters->compareTo($seconds);
$meters->equals($seconds);
$meters->lessThan($seconds);
$meters->lessThanOrEqual($seconds);
$meters->greaterThan($seconds);
$meters->greaterThanOrEqual($seconds);
