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

namespace jbboehr\Yumemi\Benchmarks;

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['runtime', 'boundary', 'persistence'])]
final class RuntimeBoundaryBench
{
    private Units $units;
    private Expr $compoundUnit;
    private Quantity $quantity;
    private PointQuantity $point;
    private string $serializedQuantity;
    private string $serializedPoint;

    public function setUp(): void
    {
        $this->units = new Units(new Udunits2UnitRegistry());
        $this->compoundUnit = $this->units->parse('kilometer / hour');
        $this->quantity = $this->units->quantity(90, $this->compoundUnit);
        $this->point = $this->units->point(20, 'celsius');
        $this->serializedQuantity = serialize($this->quantity);
        $this->serializedPoint = serialize($this->point);

        $this->units->dimension('kilometer / hour');
        $this->units->areCompatible('kilometer / hour', 'meter / second');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100)]
    public function benchNormalizeString(): Expr
    {
        return $this->units->normalize('kilometer / hour');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchNormalizeParsedExpression(): Expr
    {
        return $this->units->normalize($this->compoundUnit);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchFormatString(): string
    {
        return $this->units->format('kilometer / hour');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(500)]
    public function benchFormatParsedExpression(): string
    {
        return $this->units->format($this->compoundUnit);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100)]
    public function benchParseQuantity(): Quantity
    {
        return $this->units->parseQuantity('90 kilometer / hour');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchConstructPointQuantity(): PointQuantity
    {
        return $this->units->point(20, 'celsius');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchResolveAffineDeltaUnit(): Expr
    {
        return $this->units->deltaUnit('celsius');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchDimensionOfCachedString(): Dimension
    {
        return $this->units->dimension('kilometer / hour');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(500)]
    public function benchCompatibilityOfCachedStrings(): bool
    {
        return $this->units->areCompatible('kilometer / hour', 'meter / second');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchSerializeQuantity(): string
    {
        return serialize($this->quantity);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100)]
    public function benchDeserializeQuantity(): Quantity
    {
        $value = $this->units->deserialize($this->serializedQuantity);
        assert($value instanceof Quantity);

        return $value;
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100)]
    public function benchSerializePointQuantity(): string
    {
        return serialize($this->point);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(50)]
    public function benchDeserializePointQuantity(): PointQuantity
    {
        $value = $this->units->deserialize($this->serializedPoint);
        assert($value instanceof PointQuantity);

        return $value;
    }
}
