<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

/**
 * Fixture with a @yumemi-param on a constructor and a static method, for the `new` and static-call
 * branches of the rule. PSR-4 autoloaded, so it needs no require.
 */
final class YumemiTagParamStaticNew
{
    /**
     * @param int $length
     *
     * @yumemi-param unit_int<'meter'> $length
     */
    public function __construct(int $length)
    {
        unset($length);
    }

    /**
     * @param int $length
     *
     * @yumemi-param unit_int<'meter'> $length
     */
    public static function staticMeters(int $length): int
    {
        return $length;
    }
}
