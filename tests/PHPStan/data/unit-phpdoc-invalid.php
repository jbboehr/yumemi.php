<?php

/**
 * Fixture: invalid unit PHPDoc should produce PHPStan errors.
 */

/** @var unit_int<'mass'> $bad */
$bad = 0;

/** @var unit_int<'meter'> $length */
$length = 0;

/** @var unit_int<'second'> $time */
$time = 0;

// Different units: assignment should fail once types are resolved.
$length = $time;
