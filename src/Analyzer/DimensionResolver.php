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

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Exception\UnresolvableUnitDimensionException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Registry\UnitRegistry;

/**
 * @internal
 */
final class DimensionResolver
{
    /** @var array<string, array{int, int, int, int, int, int, int}> */
    private const BASE_DIMENSIONS = [
        'meter' => [1, 0, 0, 0, 0, 0, 0],
        'kilogram' => [0, 1, 0, 0, 0, 0, 0],
        'second' => [0, 0, 1, 0, 0, 0, 0],
        'ampere' => [0, 0, 0, 1, 0, 0, 0],
        'kelvin' => [0, 0, 0, 0, 1, 0, 0],
        'mole' => [0, 0, 0, 0, 0, 1, 0],
        'candela' => [0, 0, 0, 0, 0, 0, 1],
    ];

    public function __construct(
        private readonly UnitNormalizer $unitNormalizer,
        private readonly ?UnitRegistry $unitRegistry = null,
    ) {
    }

    public function resolve(Expr $expr): Dimension
    {
        return self::resolveNormalized($this->unitNormalizer->normalize($expr), $this->unitRegistry);
    }

    public static function resolveNormalized(Expr $expr, ?UnitRegistry $unitRegistry = null): Dimension
    {
        return self::collect(ExprReducer::reduce($expr), $unitRegistry);
    }

    private static function collect(Expr $expr, ?UnitRegistry $unitRegistry): Dimension
    {
        if ($expr instanceof Product) {
            $dimension = Dimension::dimensionless();

            foreach ($expr->factors as $subexpr) {
                $dimension = $dimension->mul(self::collect($subexpr, $unitRegistry));
            }

            return $dimension;
        }

        if ($expr instanceof Constant) {
            return Dimension::dimensionless();
        }

        if ($expr instanceof Power) {
            return self::collect($expr->base, $unitRegistry)->pow($expr->exponent);
        }

        if ($expr instanceof Unit) {
            return self::unitDimension($expr, $unitRegistry);
        }

        throw new LogicException('Cannot resolve dimension for expression of type ' . $expr::class);
    }

    private static function unitDimension(Unit $unit, ?UnitRegistry $unitRegistry): Dimension
    {
        $primitiveDimension = $unitRegistry?->findPrimitiveDimension($unit->name);
        if ($primitiveDimension !== null) {
            return Dimension::fromNamedPowers([$primitiveDimension => 1]);
        }

        $powers = self::BASE_DIMENSIONS[$unit->name] ?? null;

        if ($powers === null) {
            throw UnresolvableUnitDimensionException::create($unit->name);
        }

        return Dimension::fromPowers($powers);
    }
}
