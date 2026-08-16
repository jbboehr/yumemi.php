<?php

/**
 * Fixture for operator inference on unit types (PHPStan analyse).
 */

/** @var unit_int<'meter'> $a */
$a = 0;
/** @var unit_int<'meter'> $b */
$b = 0;
/** @var unit_int<'second'> $t */
$t = 0;
/** @var unit_int<'meter / second'> $v */
$v = 0;
/** @var unit_float<'meter'> $floatDistance */
$floatDistance = 0.0;

$sum = $a + $b;
$distance = $v * $t;
$speed = $a / $t;
$scaled = $a * 2;
$rate = 1 / $t;

// Should error: units are not definitionally equivalent.
$bad = $a + $t;

// Should error: modulo requires unit_int operands
$badModulo = $floatDistance % $floatDistance;

// Should error without aborting analysis: the resulting exponent exceeds Yumemi's limit.
/** @var unit_int<'meter ^ 10000'> $highPower */
$highPower = 1;
/** @var unit_int<'1 / meter'> $inverseDistance */
$inverseDistance = 1;
$badProduct = $highPower * $a;
$badQuotient = $highPower / $inverseDistance;
