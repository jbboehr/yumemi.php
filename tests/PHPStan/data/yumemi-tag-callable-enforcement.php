<?php

use function jbboehr\Yumemi\unit;

/**
 * @param int|(\Closure(int, mixed): int) $sleepMilliseconds
 * @yumemi-param unit_int<'millisecond'>|(\Closure(int, mixed): unit_int<'millisecond'>) $sleepMilliseconds
 */
function retryWithMilliseconds(\Closure|int $sleepMilliseconds): void
{
}

retryWithMilliseconds(unit(250, 'millisecond'));
retryWithMilliseconds(static fn (int $attempt, mixed $exception) => unit(250, 'millisecond'));
retryWithMilliseconds(static fn (int $attempt, mixed $exception): int => 250);
