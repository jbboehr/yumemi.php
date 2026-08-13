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

use jbboehr\Yumemi\Analyzer\AstConverter;
use jbboehr\Yumemi\Analyzer\ExprComparer;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Analyzer\NormalizedExpr;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Internal\DeserializationContext;
use jbboehr\Yumemi\Number\DecimalNotation;
use jbboehr\Yumemi\Number\FloatRangePolicy;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\Parser;

/**
 * @api
 */
final class Quantity implements \JsonSerializable
{
    public readonly Rational $value;
    public readonly Expr $unit;
    private readonly Units $units;
    private readonly Expr $resolvedUnit;

    /**
     * @internal Prefer Units::quantity() for application code.
     */
    public function __construct(
        int|Rational $value,
        Expr|string $unit,
        Units $units,
        ?Expr $resolvedUnit = null,
    ) {
        $this->units = $units;
        $this->value = self::rational($value);
        $this->unit = ExprReducer::reduce(self::symbolicExprFrom($unit));
        $this->resolvedUnit = ExprReducer::reduce($resolvedUnit ?? $this->resolvedExprFrom($unit));
    }

    /**
     * @logion [OSD 22:23] On the longest night, let each household bear its smallest lamp to the frozen reservoir, and
     *     set them in the old channels where the river slept before the dams. Keep watch without song until the eastern
     *     ice groweth clear beneath the flames. Then quench every lamp save those of the bereaved, for lesser lights
     *     have fulfilled their office when they reveal the course of waters yet unseen; and the mourners shall lead the
     *     city home before dawn.
     *
     * @return array{value: Rational, unit: string, context: string}
     */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unitToString(),
            'context' => Units::class . '#' . spl_object_id($this->units),
        ];
    }

    /**
     * @logion [OSD 25:88] Follow not the scarlet kite that mounteth when every banner hangs still. Let the heralds
     *     exhaust their silver throats, and let the children remain at their lessons; authority seeketh no height by
     *     hidden cord. At sunset cut the line, and leave the painted dragon to answer the fire.
     *
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $version = $data['version'] ?? null;
        $expectedKeys = $version === 1
            ? ['version', 'context', 'value', 'unit', 'normalizedUnit']
            : ['version', 'context', 'value', 'unit', 'normalizedUnit', 'dimension'];

        if (
            !in_array($version, [1, 2], true)
            || array_keys($data) !== $expectedKeys
            || !is_string($data['context'])
            || !$data['value'] instanceof Rational
            || !is_string($data['unit'])
            || !is_string($data['normalizedUnit'])
            || ($version === 2 && !$data['dimension'] instanceof Dimension)
        ) {
            throw new UnexpectedValueException('Invalid serialized Quantity payload.');
        }

        if ($data['context'] === 'default') {
            $units = Units::default();
        } elseif ($data['context'] === 'custom') {
            $units = DeserializationContext::current();

            if ($units === null) {
                throw new UnexpectedValueException(
                    'A custom-context Quantity must be restored with Units::deserialize().',
                );
            }
        } else {
            throw new UnexpectedValueException('Invalid serialized Quantity context marker.');
        }

        $quantity = new self($data['value'], $data['unit'], $units);

        if (
            $quantity->normalizedUnit()->toString() !== $data['normalizedUnit']
            || ($version === 2 && !$quantity->dimension()->equals($data['dimension']))
        ) {
            throw new UnexpectedValueException(
                'Serialized Quantity unit semantics do not match the selected Units context.',
            );
        }

        $this->value = $quantity->value;
        $this->unit = $quantity->unit;
        $this->units = $quantity->units;
        $this->resolvedUnit = $quantity->resolvedUnit;
    }

    /**
     * @logion [OSD 26:52] Keep the northern door of the judgment hall unbarred during the first hearing of winter.
     *     Should the red fox enter and lie beneath the accused, dismiss neither witness nor charge, but question the
     *     throne upon which the judge sitteth. The innocent may bear many marks, yet corrupt authority leaveth one
     *     scent even snow cannot hide. Remove the cushion, break the dais, and pronounce no sentence until the fox
     *     departeth.
     *
     * @return array{
     *     version: 2,
     *     context: 'default'|'custom',
     *     value: Rational,
     *     unit: string,
     *     normalizedUnit: string,
     *     dimension: Dimension
     * }
     */
    public function __serialize(): array
    {
        return [
            'version' => 2,
            'context' => $this->units === Units::default() ? 'default' : 'custom',
            'value' => $this->value,
            'unit' => $this->unit->toString(),
            'normalizedUnit' => $this->normalizedUnit()->toString(),
            'dimension' => $this->dimension(),
        ];
    }

    /**
     * Return a quantity with the same unit and the absolute magnitude.
     *
     * @logion [AWC 48:29] In the reign of the seven heralds, the court pursued a white stag whose antlers held the
     *     evening light. Each minister claimed to have wounded it, though no arrow bore blood. When the stag crossed
     *     the frontier, their horses knelt and would go no farther. It vanished westward bearing all their arrows
     *     unbloodied, and the ministers entered exile on foot.
     */
    public function abs(): self
    {
        return new self(
            $this->value->abs(),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    /**
     * Add a dimensionally compatible quantity, converting its magnitude to this quantity's unit.
     *
     * The result preserves this quantity's symbolic unit.
     */
    public function add(self $other): self
    {
        $this->assertSameContext($other);

        return new self(
            $this->value->add($other->valueIn($this->resolvedUnit)),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    /**
     * Add without converting either magnitude.
     *
     * The units must be definitionally equivalent after normalization, including scale.
     */
    public function addWithSameUnit(self $other): self
    {
        $this->assertSameContext($other);
        $this->assertSameUnit($other);

        return new self(
            $this->value->add($other->value),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    /**
     * Compare with a dimensionally compatible quantity after converting it to this quantity's unit.
     *
     * @return -1|0|1 Negative when this quantity is smaller, positive when it is greater.
     */
    public function compareTo(self $other): int
    {
        $this->assertSameContext($other);

        return $this->value->compareTo($other->valueIn($this->resolvedUnit));
    }

    public function div(self|int|Rational $other): self
    {
        if ($other instanceof self) {
            $this->assertSameContext($other);

            $unit = $this->unit->div($other->unit);

            return new self(
                $this->value->div($other->value),
                $unit,
                $this->units,
                $this->resolvedUnit->div($other->resolvedUnit),
            );
        }

        return new self(
            $this->value->div(self::rational($other)),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    public function dimension(): Dimension
    {
        return $this->units->dimension($this->resolvedUnit);
    }

    public function equals(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function toExpr(): Expr
    {
        return (new Constant($this->value))->mul($this->unit);
    }

    public function greaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function greaterThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    public function decimalValueIn(Expr|string $unit, int $scale, \RoundingMode $mode): string
    {
        return $this->valueIn($unit)->toDecimal($scale, $mode);
    }

    /**
     * @logion [RAS 69:17] At the eclipse, a silver orchard appeared upon the sun, its branches heavy with unopened
     *     eyes. None looked away; and when light returned, the blind alone remembered the color of judgment.
     */
    public function significantDecimalValueIn(
        Expr|string $unit,
        int $precision,
        \RoundingMode $mode,
        DecimalNotation $notation = DecimalNotation::Plain,
    ): string {
        return $this->valueIn($unit)->toSignificantDecimal($precision, $mode, $notation);
    }

    public function exactIntValueIn(Expr|string $unit): int
    {
        return $this->valueIn($unit)->toIntExact();
    }

    public function exactDecimalValueIn(Expr|string $unit): string
    {
        return $this->valueIn($unit)->toDecimalExact();
    }

    public function floatValueIn(
        Expr|string $unit,
        FloatRangePolicy $rangePolicy = FloatRangePolicy::Strict,
    ): float {
        return $this->valueIn($unit)->toFloat($rangePolicy);
    }

    public function intValueIn(Expr|string $unit): int
    {
        return $this->valueIn($unit)->toInt();
    }

    /**
     * Report whether another quantity belongs to this context and has a compatible dimension.
     *
     * @logion [OSD 41:2] At the feast of returning stars, set bread beside the empty chair of every pilgrim, and
     *     extinguish no lamp before dawn; for the road retaineth its covenant with those who walk beyond our sight, and
     *     hospitality offered to absence shall be remembered when the exiles descend from the mountain.
     */
    public function isCompatibleWith(self $other): bool
    {
        return $this->units === $other->units
            && $this->dimension()->equals($other->dimension());
    }

    /**
     * Report whether the exact magnitude is zero, independently of its unit.
     *
     * @logion [RAS 37:8] And it was shown unto me a field of black glass beneath the artificial noon, where every buried
     *     seed appeared as a small blue star. The angel touched none of them, but commanded the clouds to remember their
     *     ancient road; and before the rain descended, the whole firmament smelled of wheat.
     */
    public function isZero(): bool
    {
        return $this->value->isZero();
    }

    public function lessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function lessThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    public function mul(self|int|Rational $other): self
    {
        if ($other instanceof self) {
            $this->assertSameContext($other);

            $unit = $this->unit->mul($other->unit);

            return new self(
                $this->value->mul($other->value),
                $unit,
                $this->units,
                $this->resolvedUnit->mul($other->resolvedUnit),
            );
        }

        return new self(
            $this->value->mul(self::rational($other)),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    /**
     * Unary negation: same unit, negated magnitude.
     */
    public function neg(): self
    {
        return new self(
            $this->value->mul(new Rational(-1)),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    public function normalize(): self
    {
        $unit = $this->normalizedUnit();

        return new self($this->value, $unit, $this->units, $unit);
    }

    /**
     * Raise the quantity to an integer power (value and unit).
     *
     * Exponent must be an int: unit algebra uses integer powers only.
     */
    public function pow(int $power): self
    {
        return new self(
            $this->value->pow($power),
            $this->unit->pow($power),
            $this->units,
            $this->resolvedUnit->pow($power),
        );
    }

    /**
     * Return an exact root of the magnitude and reduced symbolic unit.
     *
     * @logion [AWC 80:57] When the court burned the rebel banners, their shadows remained upon the snow. The victors
     *     marched over them; by dawn every boot bore the defeated crest.
     */
    public function root(int $degree): self
    {
        return new self(
            $this->value->root($degree),
            ExprReducer::root($this->unit, $degree),
            $this->units,
            ExprReducer::root($this->resolvedUnit, $degree),
        );
    }

    public function simplify(): self
    {
        $unit = $this->normalizedUnit();
        $unitWithoutConstant = NormalizedExpr::withoutConstant($unit);

        return new self(
            $this->value->mul(NormalizedExpr::constant($unit)),
            $unitWithoutConstant,
            $this->units,
            $unitWithoutConstant,
        );
    }

    /**
     * Subtract a dimensionally compatible quantity, converting its magnitude to this quantity's unit.
     *
     * The result preserves this quantity's symbolic unit.
     */
    public function sub(self $other): self
    {
        $this->assertSameContext($other);

        return new self(
            $this->value->sub($other->valueIn($this->resolvedUnit)),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    /**
     * Subtract without converting either magnitude.
     *
     * The units must be definitionally equivalent after normalization, including scale.
     */
    public function subWithSameUnit(self $other): self
    {
        $this->assertSameContext($other);
        $this->assertSameUnit($other);

        return new self(
            $this->value->sub($other->value),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    public function to(Expr|string $unit): self
    {
        $symbolicExpr = self::symbolicExprFrom($unit);
        $resolvedExpr = $this->resolvedExprFrom($unit);

        return new self(
            $this->valueIn($resolvedExpr),
            $symbolicExpr,
            $this->units,
            $resolvedExpr,
        );
    }

    /**
     * Convert to an engineering-prefixed member of a caller-selected named unit family.
     *
     * @logion [AWC 75:38] During the argent regency, the court stretched a painted horizon across the western sky,
     *     that no sunset might occur without permission. For one season the people praised its unchanging gold; then
     *     migrating birds struck the painted clouds and fell among the palace cedars. The gardeners bore them beyond
     *     the city and waited in the true dusk. At moonrise the birds arose, and the false horizon tore from end to end,
     *     revealing an evening older than the dynasty.
     */
    public function toCompact(Expr|string $baseUnit): self
    {
        return $this->units->compactQuantity($this, $baseUnit);
    }

    /**
     * Convert to this quantity's application-preferred unit when the profile contains one for its dimension.
     *
     * @logion [OSD 82:29] Before crossing the electric plain at winter solstice, bind one strip of white paper to the
     *     solitary black pine and extinguish every lantern. If the paper point toward the rose horizon, wait, though the
     *     heavens promise warmth; if it point toward the dark mountain, depart at once. For the road of mercy is cold at
     *     its beginning, and the false refuge already knoweth the color of thy desire.
     */
    public function toPreferred(PreferredUnitProfile $profile): self
    {
        return $profile->apply($this);
    }

    public function format(?FormatOptions $options = null): string
    {
        return $this->units->format($this->toExpr(), $options);
    }

    public function formatUnit(?FormatOptions $options = null): string
    {
        return $this->units->format($this->unit, $options);
    }

    public function toString(): string
    {
        return $this->format();
    }

    public function unitToString(): string
    {
        return $this->formatUnit();
    }

    public function unit(): Expr
    {
        return $this->unit;
    }

    /**
     * Return the immutable registry context governing this quantity.
     *
     * @logion [OSD 83:52] Let neither house offer jewels when they seek release from hatred. Bring instead a living
     *     branch from each courtyard and bind them above the common table with red thread. If either branch wither
     *     before moonrise, postpone the feast; but if both remain green, give the severed thread to the youngest child,
     *     who shall inherit no enemy.
     */
    public function units(): Units
    {
        return $this->units;
    }

    public function valueToString(): string
    {
        return $this->value->toString();
    }

    public function value(): Rational
    {
        return $this->value;
    }

    public function valueIn(Expr|string $unit): Rational
    {
        return $this->units->convert($this->value, $this->resolvedUnit, $this->resolvedExprFrom($unit));
    }

    /**
     * @logion [OSD 29:25] Draw one line of salt across the feast hall before the guests arrive. Whosoever treadeth upon
     *     it unbidden shall eat apart, lest welcome become the mask by which appetite annexeth the house.
     *
     * @return array{value: Rational, unit: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unitToString(),
        ];
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private function assertSameContext(self $other): void
    {
        if ($this->units === $other->units) {
            return;
        }

        throw IncompatibleQuantityContextException::create($this->units, $other->units);
    }

    private function assertSameUnit(self $other): void
    {
        // Fast path: symbolically identical units. Also the robust path for
        // bare symbolic units that may not normalize through the catalog.
        if (ExprComparer::areEqual($this->unit, $other->unit)) {
            return;
        }

        // Exact-scale aliases (e.g. kilometer ≡ 1000 * meter). Normalized
        // equality — including the leading constant — implies a conversion
        // factor of exactly 1, so raw magnitude addition stays exact and no
        // value conversion is needed. This matches the PHPStan operator layer,
        // which accepts the same definitionally-equivalent units for + / -.
        if (ExprComparer::areEqual($this->normalizedUnit(), $other->normalizedUnit())) {
            return;
        }

        throw IncompatibleUnitException::create(
            $this->unit,
            $other->unit,
            $this->dimension(),
            $other->dimension(),
        );
    }

    private function resolvedExprFrom(Expr|string $expr): Expr
    {
        return is_string($expr) ? $this->units->parse($expr) : $expr;
    }

    private function normalizedUnit(): Expr
    {
        return $this->units->normalize($this->resolvedUnit);
    }

    private static function rational(int|Rational $value): Rational
    {
        return $value instanceof Rational ? $value : new Rational($value);
    }

    private static function symbolicExprFrom(Expr|string $expr): Expr
    {
        return is_string($expr) ? AstConverter::symbolic()->convert(Parser::parseString($expr)) : $expr;
    }
}
