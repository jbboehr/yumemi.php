<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

/**
 * Fixture class for the extension-optional @yumemi-param tag on a method.
 *
 * Autoloaded via PSR-4 (unlike the free function), so no `require` is needed. The parameter keeps a
 * native `int` type; the tag declares the intended unit.
 */
final class YumemiTagParamClass
{
    /**
     * @param int $length
     *
     * @yumemi-param unit_int<'meter'> $length
     */
    public function expectMeters(int $length): int
    {
        return $length;
    }
}
