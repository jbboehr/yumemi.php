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

use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Exception\NonExactRootException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Util\Exponent;

/**
 * @internal
 */
final class ExprReducer
{
    public static function reduce(Expr $expr): Expr
    {
        $state = new ReductionState();
        self::collect($expr, 1, $state);

        return self::build($state);
    }

    /**
     * Return an exact root while preserving the expression's reduced symbolic names.
     *
     * @logion [OSD 73:11] The keepers divided the harvest according to the ancient lots,
     *     yet the first sheaf remained before the altar as witness that abundance came by covenant.
     */
    public static function root(Expr $expr, int $degree): Expr
    {
        $degree = Exponent::checkedRootDegree($degree);
        $state = new ReductionState();
        self::collect($expr, 1, $state);

        $reduced = self::build($state);
        foreach ($state->units as $data) {
            if ($data['power'] % $degree !== 0) {
                throw new NonExactRootException(sprintf(
                    'Unit expression %s has no exact symbolic root of degree %d.',
                    $reduced->toString(),
                    $degree,
                ));
            }
        }

        $state->constant = $state->constant->root($degree);
        foreach ($state->units as &$data) {
            $data['power'] = intdiv($data['power'], $degree);
        }
        unset($data);

        return self::build($state);
    }

    private static function build(ReductionState $state): Expr
    {
        ksort($state->units);

        $exprs = [];

        if (!$state->constant->isOne() || count($state->units) === 0) {
            $exprs[] = new Constant($state->constant);
        }

        foreach ($state->units as $data) {
            $power = $data['power'];

            if ($power === 0) {
                continue;
            }

            $unit = $data['unit'];
            $exprs[] = $power === 1 ? $unit : new Power($unit, $power);
        }

        if (count($exprs) === 1) {
            return $exprs[0];
        }

        return new Product($exprs);
    }

    private static function collect(Expr $expr, int $power, ReductionState $state): void
    {
        if ($power === 0) {
            return;
        }

        if ($expr instanceof Product) {
            foreach ($expr->factors as $subexpr) {
                self::collect($subexpr, $power, $state);
            }

            return;
        }

        if ($expr instanceof Power) {
            self::collect($expr->base, Exponent::multiply($power, $expr->exponent), $state);
            return;
        }

        if ($expr instanceof Constant) {
            $state->constant = $state->constant->mul($expr->value->pow($power));
            return;
        }

        if ($expr instanceof Unit) {
            $data = $state->units[$expr->name] ?? [
                'unit' => $expr,
                'power' => 0,
            ];

            if ($data['unit']->isBase() && !$expr->isBase()) {
                $data['unit'] = $expr;
            }

            $data['power'] = Exponent::add($data['power'], $power);

            if ($data['power'] === 0) {
                unset($state->units[$expr->name]);
            } else {
                $state->units[$expr->name] = $data;
            }

            return;
        }

        throw new LogicException('Cannot reduce expression of type ' . $expr::class);
    }
}
