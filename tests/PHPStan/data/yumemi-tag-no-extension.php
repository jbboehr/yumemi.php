<?php

use function jbboehr\Yumemi\unit;

/**
 * @param int $length
 * @yumemi-param unit_int<'meter'> $length
 */
function acceptsPlainLength(int $length): void
{
}

acceptsPlainLength(1);

/**
 * @return int
 * @yumemi-return unit_int<'meter'>
 */
function plainLength(): int
{
    return 1;
}

/**
 * @var int $duration
 * @yumemi-var unit_int<'second'> $duration
 */
$duration = plainLength();
acceptsPlainLength($duration);

/**
 * This transform is intentionally invalid. Without the opt-in config it remains an inert tag.
 *
 * @param float $length
 * @yumemi-param unit_int<'meter'> $length
 */
function acceptsMismatchedFallback(float $length): void
{
}

acceptsMismatchedFallback(1.0);

/**
 * This payload is intentionally malformed. Without the opt-in config it remains an inert tag.
 *
 * @param int $length
 * @yumemi-param unit_int<'meter'>
 */
function acceptsMalformedTag(int $length): void
{
}

acceptsMalformedTag(1);

/** @param unit_int<'meter'> $length */
function acceptsDirectYumemiType(int $length): void
{
}

// The core extension remains active even though parser-level tag promotion is not.
acceptsDirectYumemiType(unit(1, 'meter'));
