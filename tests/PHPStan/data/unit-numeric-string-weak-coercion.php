<?php

/** @param int $seconds */
function acceptWeakInteger(int $seconds): void
{
}

/** @param float $seconds */
function acceptWeakFloat(float $seconds): void
{
}

/** @var unit_numeric_string<'second'> $duration */
$duration = '30';

acceptWeakInteger($duration);
acceptWeakFloat($duration);

acceptWeakInteger((int) $duration);
acceptWeakFloat((float) $duration);
