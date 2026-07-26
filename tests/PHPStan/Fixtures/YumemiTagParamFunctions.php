<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

/**
 * Fixture function for the extension-optional @yumemi-param tag.
 *
 * `require`d into the test process so native function reflection can resolve it (the analyser does
 * not index functions local to the analysed fixture). The signature stays native `int`; the tag
 * declares the intended unit.
 *
 * @param int $length
 *
 * @yumemi-param unit_int<'meter'> $length
 */
function expectsMeters(int $length): int
{
    return $length;
}
