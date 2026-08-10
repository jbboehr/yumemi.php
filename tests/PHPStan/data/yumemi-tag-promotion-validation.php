<?php

namespace jbboehr\Yumemi\Tests\PHPStan\PromotionValidation;

use DateTimeInterface;

/**
 * Union order and nullable spelling do not matter to an exact transform.
 *
 * @param DateTimeInterface|int|null $value fallback description
 * @return int|null fallback description
 *
 * @yumemi-param null|unit_int<'second'>|DateTimeInterface $value
 * @yumemi-return ?unit_int<'second'>
 */
function validComposite(DateTimeInterface|int|null $value): ?int
{
    return is_int($value) ? $value : null;
}

if (false) {
    $validMethod = new class {
    /**
     * @yumemi-param unit_float<'meter'> $length
     * @yumemi-return unit_float<'meter'>
     */
        public function roundTrip(float $length): float
        {
            return $length;
        }
    };
}

/** @yumemi-param unit_int<'meter'> */
function invalidParamSyntax(int $length): void
{
}

/** @yumemi-param unit_int<'not_a_real_unit_xyz'> $length */
function invalidUnit(int $length): void
{
}

/** @yumemi-return int */
function missingUnitType(): int
{
    return 1;
}

/** @yumemi-param unit_int<'meter'> $missing */
function unknownParameter(int $length): void
{
}

/**
 * @yumemi-param unit_int<'meter'> $length
 * @yumemi-param unit_int<'foot'> $length
 */
function duplicate(int $length): void
{
}

/**
 * @param float|null $length
 * @yumemi-param unit_int<'meter'>|null $length
 */
function paramFallbackMismatch(float|null $length): void
{
}

/**
 * @param int $length
 * @phpstan-param float $length
 * @yumemi-param unit_int<'meter'> $length
 */
function phpstanFallbackHasPriority(int|float $length): void
{
}

/**
 * @return float|null
 * @yumemi-return unit_int<'meter'>|null
 */
function returnFallbackMismatch(): float|null
{
    return null;
}

/**
 * @param int &$length
 * @yumemi-param unit_int<'meter'> $length
 */
function markerMismatch(int &$length): void
{
}

/**
 * @var float $length
 * @yumemi-var unit_int<'meter'> $length
 */
$length = 1;

/**
 * @var int
 * @var int
 * @yumemi-var unit_int<'meter'>
 */
$ambiguous = 1;

if (false) {
    $invalidTarget = new class {
        /** @yumemi-param unit_int<'meter'> $length */
        public int $length = 1;
    };
}

/**
 * @param int $seconds
 * @phpstan-param unit_int<'second'> $seconds
 * @yumemi-param unit_int<'second'> $seconds
 */
function validAlreadyPromoted(int $seconds): void
{
}

/**
 * @return string
 * @yumemi-return unit_numeric_string<'second'>
 */
function numericStringFallbackMismatch(): string
{
    return '30';
}
