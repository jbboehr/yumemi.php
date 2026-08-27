<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$meters = $units->quantity(1, 'meter');
$seconds = $units->quantity(1, 'second');

$incompatible = $meters + $seconds;
$scalarAdd = 1 + $meters;
$floatMultiply = $meters * 1.5;
$floatPower = $meters ** 1.5;
$scalarPower = 2 ** $meters;
$modulo = $meters % $meters;
