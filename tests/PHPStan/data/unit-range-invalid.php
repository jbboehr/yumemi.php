<?php

use function jbboehr\Yumemi\unit;

/** @param unit_int<'meter'>&int<0, 100> $length */
function acceptBoundedLength(int $length): void
{
}

acceptBoundedLength(unit(101, 'meter'));
acceptBoundedLength(unit(50, 'second'));
acceptBoundedLength(50);

/** @var unit_int<'meter'> $unknownLength */
$unknownLength = 50;
acceptBoundedLength($unknownLength);
