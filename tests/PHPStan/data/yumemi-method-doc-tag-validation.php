<?php

namespace jbboehr\Yumemi\Tests\PHPStan\data;

use jbboehr\Yumemi\Quantity;

final class ValidMethodTags
{
    /** @yumemi-param unit_int<'meter'> $length */
    public function instance(int $length): int
    {
        return $length;
    }

    /** @yumemi-param unit_float<'meter'> $length description */
    public static function staticMethod(float $length): float
    {
        return $length;
    }

    /** @yumemi-param Quantity<'meter'> $quantity */
    public function __construct(Quantity $quantity)
    {
    }
}

final class InvalidMethodTags
{
    /** @yumemi-return unit_int<'meter'> */
    public function unsupportedReturn(): int
    {
        return 1;
    }

    /**
     * @yumemi-return unit_int<'meter'>
     * @yumemi-return unit_int<'foot'>
     */
    public static function duplicateUnsupportedReturn(): int
    {
        return 1;
    }

    /** @yumemi-param unit_int<'meter'> */
    public function malformedParam(int $length): int
    {
        return $length;
    }

    /** @yumemi-param unit_int<'meter'> $missing */
    public function unknownParam(int $length): int
    {
        return $length;
    }

    /**
     * @yumemi-param unit_int<'meter'> $length
     * @yumemi-param unit_int<'foot'> $length
     */
    public function duplicateParam(int $length): int
    {
        return $length;
    }

    /** @yumemi-param unit_float<'meter'> $length */
    public function wrongParamKind(int $length): int
    {
        return $length;
    }

    /** @yumemi-param unit_int<'meter'> $length */
    public function nullableParam(?int $length): ?int
    {
        return $length;
    }

    /** @yumemi-param Quantity<'meter'> $quantity */
    public function wrongQuantityParam(object $quantity): object
    {
        return $quantity;
    }
}

interface InvalidInheritedTag
{
    /** @yumemi-param unit_int<'not_a_real_unit_xyz'> $length */
    public function inherited(int $length): void;
}

final class InvalidInheritedTagImplementation implements InvalidInheritedTag
{
    public function inherited(int $length): void
    {
    }
}
