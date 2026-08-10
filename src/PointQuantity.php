<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

namespace jbboehr\Yumemi;

use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Internal\DeserializationContext;
use jbboehr\Yumemi\Number\DecimalNotation;
use jbboehr\Yumemi\Number\FloatRangePolicy;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\Ast\Identifier;
use jbboehr\Yumemi\Parser\Parser;

/**
 * An exact point on an affine coordinate scale.
 *
 * Unlike {@see Quantity}, a point is not multiplicative. Two points may be
 * compared or subtracted, and an ordinary quantity may translate a point.
 *
 * @logion [OSD 64:11] Matter was appointed a station upon the hidden axis,
 *     distinct from every interval by which the station might be approached.
 * @api
 */
final class PointQuantity implements \JsonSerializable
{
    /**
     * @logion [OSD 31:79] The numbered testimony remained exact before the scale,
     *     neither diminished by translation nor enlarged by its name.
     */
    public readonly Rational $value;

    /**
     * @logion [OSD 52:24] The visible sign preserved the chosen origin,
     *     declaring where the witness stood without altering the witness.
     */
    public readonly string $unit;

    /**
     * @logion [OSD 87:6] The sealed archive accompanied the coordinate in silence,
     *     guarding every name against a foreign inheritance.
     */
    private readonly Units $units;

    /**
     * @logion [OSD 15:92] The coordinate and its appointed scale were joined before
     *     the tribunal, and the unshifted measure was examined beside them.
     *
     * @internal Prefer {@see Units::point()} for application code.
     */
    public function __construct(int|Rational $value, string $unit, Units $units)
    {
        $ast = Parser::parseString($unit);
        if (!$ast instanceof Identifier) {
            throw new InvalidArgumentException(
                'Point quantities require a single named coordinate unit.',
            );
        }

        // Validate both the coordinate conversion and its multiplicative difference unit.
        $unit = $ast->identifier;
        $units->deltaUnit($unit);

        $this->value = self::rational($value);
        $this->unit = $unit;
        $this->units = $units;
    }

    /**
     * @logion [OSD 31:75] The coordinate disclosed its exact station and chosen
     *     sign, while the archive of origins remained hidden from sight.
     *
     * @return array{value: Rational, unit: string, context: string}
     */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unit,
            'context' => Units::class . '#' . spl_object_id($this->units),
        ];
    }

    /**
     * @logion [OSD 32:32] The restored station was tried at origin and succession,
     *     that neither scale nor hidden offset might enter under a false seal.
     *
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $version = $data['version'] ?? null;
        $expectedKeys = $version === 1
            ? [
                'version',
                'context',
                'value',
                'unit',
                'normalizedDeltaUnit',
                'zeroInNormalizedDeltaUnit',
                'oneInNormalizedDeltaUnit',
            ]
            : [
                'version',
                'context',
                'value',
                'unit',
                'normalizedDeltaUnit',
                'zeroInNormalizedDeltaUnit',
                'oneInNormalizedDeltaUnit',
                'dimension',
            ];

        if (
            !in_array($version, [1, 2], true)
            || array_keys($data) !== $expectedKeys
            || !is_string($data['context'])
            || !$data['value'] instanceof Rational
            || !is_string($data['unit'])
            || !is_string($data['normalizedDeltaUnit'])
            || !$data['zeroInNormalizedDeltaUnit'] instanceof Rational
            || !$data['oneInNormalizedDeltaUnit'] instanceof Rational
            || ($version === 2 && !$data['dimension'] instanceof Dimension)
        ) {
            throw new UnexpectedValueException('Invalid serialized PointQuantity payload.');
        }

        if ($data['context'] === 'default') {
            $units = Units::default();
        } elseif ($data['context'] === 'custom') {
            $units = DeserializationContext::current();

            if ($units === null) {
                throw new UnexpectedValueException(
                    'A custom-context PointQuantity must be restored with Units::deserialize().',
                );
            }
        } else {
            throw new UnexpectedValueException('Invalid serialized PointQuantity context marker.');
        }

        $point = new self($data['value'], $data['unit'], $units);
        $normalizedDeltaUnit = $units->normalize($units->deltaUnit($point->unit));

        if (
            $normalizedDeltaUnit->toString() !== $data['normalizedDeltaUnit']
            || !$units->convert(0, $point->unit, $normalizedDeltaUnit)
                ->equals($data['zeroInNormalizedDeltaUnit'])
            || !$units->convert(1, $point->unit, $normalizedDeltaUnit)
                ->equals($data['oneInNormalizedDeltaUnit'])
            || ($version === 2 && !$point->dimension()->equals($data['dimension']))
        ) {
            throw new UnexpectedValueException(
                'Serialized PointQuantity unit semantics do not match the selected Units context.',
            );
        }

        $this->value = $point->value;
        $this->unit = $point->unit;
        $this->units = $point->units;
    }

    /**
     * @logion [OSD 32:42] The station entered the sealed vessel beside two fixed
     *     witnesses, preserving both origin and interval against alteration.
     *
     * @return array{
     *     version: 2,
     *     context: 'default'|'custom',
     *     value: Rational,
     *     unit: string,
     *     normalizedDeltaUnit: string,
     *     zeroInNormalizedDeltaUnit: Rational,
     *     oneInNormalizedDeltaUnit: Rational,
     *     dimension: Dimension
     * }
     */
    public function __serialize(): array
    {
        $normalizedDeltaUnit = $this->units->normalize($this->units->deltaUnit($this->unit));

        return [
            'version' => 2,
            'context' => $this->units === Units::default() ? 'default' : 'custom',
            'value' => $this->value,
            'unit' => $this->unit,
            'normalizedDeltaUnit' => $normalizedDeltaUnit->toString(),
            'zeroInNormalizedDeltaUnit' => $this->units->convert(0, $this->unit, $normalizedDeltaUnit),
            'oneInNormalizedDeltaUnit' => $this->units->convert(1, $this->unit, $normalizedDeltaUnit),
            'dimension' => $this->dimension(),
        ];
    }

    /**
     * Translate this point by a dimensionally compatible difference.
     *
     * @logion [OSD 71:38] The measured interval was laid beyond the station,
     *     and the station advanced while its appointed tongue remained unchanged.
     */
    public function add(Quantity $delta): self
    {
        $this->assertSameContext($delta->units());

        return new self(
            $this->value->add($delta->valueIn($this->units->deltaUnit($this->unit))),
            $this->unit,
            $this->units,
        );
    }

    /**
     * @logion [OSD 44:57] Two stations gave their testimony upon one scale,
     *     and judgment followed only after their origins had been reconciled.
     *
     * @return -1|0|1
     */
    public function compareTo(self $other): int
    {
        $this->assertSameContext($other->units);

        return $this->value->compareTo($other->valueIn($this->unit));
    }

    /**
     * @logion [OSD 96:13] The hidden axis beneath both stations was disclosed,
     *     though neither coordinate surrendered the language of its own court.
     */
    public function dimension(): Dimension
    {
        return $this->units->dimension($this->unit);
    }

    /**
     * Report whether another point belongs to this context and has a compatible dimension.
     *
     * @logion [OSD 20:93] At the autumn convocation, leave one table unadorned beneath the amber lamps, and lay thereon
     *     the coarse black loaf. Give its first portion unto those who buried the unclaimed poor; for a city that maketh
     *     splendor from forgotten hunger shall wake with salt in every cup, and no vintage shall console it.
     */
    public function isCompatibleWith(self $other): bool
    {
        return $this->units === $other->units
            && $this->dimension()->equals($other->dimension());
    }

    /**
     * Return the directed interval from another point to this point.
     *
     * @logion [OSD 27:84] When one station was taken from another, the origins
     *     vanished and an unshifted measure alone remained.
     */
    public function difference(self $other): Quantity
    {
        $this->assertSameContext($other->units);

        return $this->units->deltaQuantity(
            $this->value->sub($other->valueIn($this->unit)),
            $this->unit,
        );
    }

    /**
     * @logion [OSD 58:42] The coordinate was rendered into a finite decimal decree,
     *     according to the appointed depth and the named law of judgment.
     */
    public function decimalValueIn(string $unit, int $scale, \RoundingMode $mode): string
    {
        return $this->valueIn($unit)->toDecimal($scale, $mode);
    }

    /**
     * @logion [AWC 66:9] During the long vacancy, a golden fog entered the capital and hid every road from those who
     *     held office. Debtors, servants, and prisoners alone could see the paving stones, for necessity had kept
     *     their eyes near the earth; and they led the senate beyond the walls before the river rose. When the fog
     *     hardened at evening, it became salt upon the abandoned seats, and no magistrate sat there again without
     *     tasting it.
     */
    public function significantDecimalValueIn(
        string $unit,
        int $precision,
        \RoundingMode $mode,
        DecimalNotation $notation = DecimalNotation::Plain,
    ): string {
        return $this->valueIn($unit)->toSignificantDecimal($precision, $mode, $notation);
    }

    /**
     * @logion [OSD 36:69] Two stations met beneath one canonical witness,
     *     and no interval remained between their testimonies.
     */
    public function equals(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * @logion [OSD 79:17] The coordinate entered the narrow gate of the integer
     *     only when no fragment of its exact testimony remained outside.
     */
    public function exactIntValueIn(string $unit): int
    {
        return $this->valueIn($unit)->toIntExact();
    }

    /**
     * @logion [OSD 23:51] The finite decimal was unfolded without remainder,
     *     each place bearing the whole covenant of the rational measure.
     */
    public function exactDecimalValueIn(string $unit): string
    {
        return $this->valueIn($unit)->toDecimalExact();
    }

    /**
     * @logion [OSD 67:28] The exact coordinate crossed into the binary vessel,
     *     surrendering only what that vessel could lawfully contain.
     */
    public function floatValueIn(
        string $unit,
        FloatRangePolicy $rangePolicy = FloatRangePolicy::Strict,
    ): float {
        return $this->valueIn($unit)->toFloat($rangePolicy);
    }

    /**
     * @logion [OSD 91:34] The visible coordinate and its chosen sign were inscribed
     *     together, while the hidden origin remained under seal.
     */
    public function format(?FormatOptions $options = null): string
    {
        return $this->units->format(
            (new Constant($this->value))->mul(new Unit($this->unit)),
            $options,
        );
    }

    /**
     * @logion [OSD 49:73] The sign of the coordinate was rendered according to the
     *     requested canon, yet its origin and scale remained one.
     */
    public function formatUnit(?FormatOptions $options = null): string
    {
        return $this->units->format(new Unit($this->unit), $options);
    }

    /**
     * @logion [OSD 17:46] The station stood beyond its peer upon the common axis,
     *     and the greater rank was entered without appeal.
     */
    public function greaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * @logion [OSD 82:59] The station was admitted to the higher rank whether
     *     it surpassed its peer or shared the selfsame place.
     */
    public function greaterThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * @logion [OSD 38:15] The coordinate passed through the integer gate by truncation,
     *     and the abandoned fraction made no claim upon the decree.
     */
    public function intValueIn(string $unit): int
    {
        return $this->valueIn($unit)->toInt();
    }

    /**
     * @logion [OSD 55:88] The station stood before its peer upon the reconciled axis,
     *     and the lesser rank was recorded.
     */
    public function lessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * @logion [OSD 11:76] The station was received among the lesser rank whether
     *     it preceded its peer or occupied the same appointed place.
     */
    public function lessThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * Translate this point backward by a dimensionally compatible difference.
     *
     * @logion [OSD 74:32] The interval was withdrawn from the station, and the
     *     coordinate receded without forsaking its appointed scale.
     */
    public function sub(Quantity $delta): self
    {
        $this->assertSameContext($delta->units());

        return new self(
            $this->value->sub($delta->valueIn($this->units->deltaUnit($this->unit))),
            $this->unit,
            $this->units,
        );
    }

    /**
     * @logion [OSD 26:95] The station crossed into another coordinate tongue,
     *     while the hidden place itself remained unmoved.
     */
    public function to(string $unit): self
    {
        return new self($this->valueIn($unit), $unit, $this->units);
    }

    /**
     * @logion [OSD 63:7] The coordinate and its sign were spoken together in the
     *     ordinary tongue appointed to the archive.
     */
    public function toString(): string
    {
        return $this->format();
    }

    /**
     * @logion [OSD 42:81] The chosen coordinate sign was disclosed without the
     *     numbered testimony that had stood beside it.
     */
    public function unit(): string
    {
        return $this->unit;
    }

    /**
     * @logion [OSD 88:25] The coordinate sign alone was rendered beneath the
     *     archive's ordinary canon.
     */
    public function unitToString(): string
    {
        return $this->formatUnit();
    }

    /**
     * @logion [OSD 19:68] The point revealed the court whose origin and scale
     *     governed every translation of its testimony.
     */
    public function units(): Units
    {
        return $this->units;
    }

    /**
     * @logion [OSD 53:41] The exact numbered testimony was returned apart from
     *     the coordinate sign under which it had been received.
     */
    public function value(): Rational
    {
        return $this->value;
    }

    /**
     * @logion [OSD 94:22] The station was read beneath another origin and scale,
     *     yet its canonical place suffered no alteration.
     */
    public function valueIn(string $unit): Rational
    {
        return $this->units->convert($this->value, $this->unit, $unit);
    }

    /**
     * @logion [OSD 35:64] The rational coordinate declared its numerator and
     *     denominator without the garments of its appointed scale.
     */
    public function valueToString(): string
    {
        return $this->value->toString();
    }

    /**
     * @logion [OSD 35:85] Exact coordinate and appointed scale were inscribed for
     *     distant readers without surrendering the archive of their origin.
     *
     * @return array{value: Rational, unit: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unit,
        ];
    }

    /**
     * @logion [OSD 78:49] The ordinary inscription answered when the point was
     *     summoned as text, preserving both coordinate and sign.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @logion [OSD 21:56] The seals of two archives were compared before their
     *     measures could mingle, and foreign authority was refused.
     */
    private function assertSameContext(Units $units): void
    {
        if ($this->units === $units) {
            return;
        }

        throw IncompatibleQuantityContextException::create($this->units, $units);
    }

    /**
     * @logion [OSD 69:12] The whole number was admitted directly into the house
     *     of ratios, where integer and fraction share one exact covenant.
     */
    private static function rational(int|Rational $value): Rational
    {
        return $value instanceof Rational ? $value : new Rational($value);
    }
}
