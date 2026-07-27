<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Data;

use jbboehr\Yumemi\Quantity;

/**
 * @yumemi-param unit_int<'meter'> $length a description is allowed
 * @yumemi-return unit_int<'meter'>
 */
function validIntTags(int $length): int
{
    return $length;
}

/**
 * @yumemi-param Quantity<'meter'> $quantity
 * @yumemi-return Quantity<'meter'>
 */
function validQuantityTags(Quantity $quantity): Quantity
{
    return $quantity;
}

/** @yumemi-param unit_int<'meter'> */
function malformedParam(int $length): int
{
    return $length;
}

/** @yumemi-param unit_int<'not_a_real_unit_xyz'> $length */
function invalidParamUnit(int $length): int
{
    return $length;
}

/** @yumemi-param int $length */
function plainParamType(int $length): int
{
    return $length;
}

/** @yumemi-param unit_int<'meter'> $missing */
function unknownParam(int $length): int
{
    return $length;
}

/**
 * @yumemi-param unit_int<'meter'> $length
 * @yumemi-param unit_int<'foot'> $length
 */
function duplicateParam(int $length): int
{
    return $length;
}

/** @yumemi-param unit_int<'meter'> $length */
function wrongParamKind(float $length): float
{
    return $length;
}

/** @yumemi-param unit_int<'meter'> $length */
function nullableParam(?int $length): ?int
{
    return $length;
}

/** @yumemi-param unit_float<'meter'> $length */
function unionParam(int|float $length): int|float
{
    return $length;
}

/** @yumemi-return unit_int<'meter'> trailing prose */
function malformedReturn(): int
{
    return 1;
}

/** @yumemi-return unit_int<'not_a_real_unit_xyz'> */
function invalidReturnUnit(): int
{
    return 1;
}

/** @yumemi-return int */
function plainReturnType(): int
{
    return 1;
}

/** @yumemi-return */
function emptyReturnType(): int
{
    return 1;
}

/**
 * @yumemi-return unit_int<'meter'>
 * @yumemi-return unit_int<'foot'>
 */
function duplicateReturn(): int
{
    return 1;
}

/** @yumemi-return unit_float<'meter'> */
function wrongReturnKind(): int
{
    return 1;
}

/** @yumemi-return Quantity<'meter'> */
function wrongQuantityReturnKind(): object
{
    return new \stdClass();
}
