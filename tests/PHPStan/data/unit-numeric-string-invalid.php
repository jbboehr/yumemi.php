<?php

declare(strict_types=1);

/** @param unit_numeric_string<'second'> $duration */
function acceptNumericDuration(string $duration): void
{
}

/** @var numeric-string $bare */
$bare = '30';
acceptNumericDuration($bare);

/** @var unit_numeric_string<'meter'> $distance */
$distance = '30';
acceptNumericDuration($distance);

/** @var unit_numeric_string<'second'> $duration */
$duration = '30';
acceptNumericDuration($duration);

/** @param unit_int<'second'> $seconds */
function acceptIntegerDuration(int $seconds): void
{
}

acceptIntegerDuration($duration);
acceptIntegerDuration((int) $duration);
