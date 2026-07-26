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
use jbboehr\Yumemi\Exception\UnsupportedUnitDimensionException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;

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
    ) {
    }

    public function resolve(Expr $expr): Dimension
    {
        return self::resolveNormalized($this->unitNormalizer->normalize($expr));
    }

    public static function resolveNormalized(Expr $expr): Dimension
    {
        return self::collect(ExprReducer::reduce($expr));
    }

    private static function collect(Expr $expr): Dimension
    {
        if ($expr instanceof Compound) {
            $dimension = Dimension::dimensionless();

            foreach ($expr->exprs as $subexpr) {
                $dimension = $dimension->mul(self::collect($subexpr));
            }

            return $dimension;
        }

        if ($expr instanceof Constant) {
            return Dimension::dimensionless();
        }

        if ($expr instanceof Term) {
            return self::collect($expr->value)->pow($expr->power);
        }

        if ($expr instanceof Unit) {
            return self::unitDimension($expr);
        }

        throw new \LogicException('Cannot resolve dimension for expression of type ' . $expr::class);
    }

    private static function unitDimension(Unit $unit): Dimension
    {
        $powers = self::BASE_DIMENSIONS[$unit->name] ?? null;

        if ($powers === null) {
            throw UnsupportedUnitDimensionException::create($unit->name);
        }

        return Dimension::fromPowers($powers);
    }
}
