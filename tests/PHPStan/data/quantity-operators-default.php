<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$meters = $units->quantity(1, 'meter');
$feet = $units->quantity(1, 'foot');

$sum = $meters + $feet;
$positive = +$meters;
$negative = -$meters;
