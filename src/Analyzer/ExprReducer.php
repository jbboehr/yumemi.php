<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
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

use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;

final class ExprReducer
{
    public static function reduce(Expr $expr): Expr
    {
        $state = new ReductionState();
        self::collect($expr, 1, $state);

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
            $exprs[] = $power === 1 ? $unit : new Term($unit, $power);
        }

        if (count($exprs) === 1) {
            return $exprs[0];
        }

        return new Compound($exprs);
    }

    private static function collect(Expr $expr, int $power, ReductionState $state): void
    {
        if ($power === 0) {
            return;
        }

        if ($expr instanceof Compound) {
            foreach ($expr->exprs as $subexpr) {
                self::collect($subexpr, $power, $state);
            }

            return;
        }

        if ($expr instanceof Term) {
            self::collect($expr->value, $power * $expr->power, $state);
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

            $data['power'] += $power;

            if ($data['power'] === 0) {
                unset($state->units[$expr->name]);
            } else {
                $state->units[$expr->name] = $data;
            }

            return;
        }

        throw new \LogicException('Cannot reduce expression of type ' . $expr::class);
    }
}
