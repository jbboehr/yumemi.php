<?php

/**
 * Proves a @yumemi-return brand flows into PHPStan's ordinary argument checking, not just
 * assertType(): a value branded unit_int<'international_foot'> is rejected where a native-position
 * unit_int<'meter'> parameter is required.
 */

/**
 * @yumemi-return unit_int<'foot'>
 */
function measuredFeetForCall(): int
{
    return 3;
}

/**
 * @param unit_int<'meter'> $length
 */
function consumeMeters(int $length): void
{
}

consumeMeters(measuredFeetForCall());
