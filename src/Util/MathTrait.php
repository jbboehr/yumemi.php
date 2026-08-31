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

namespace jbboehr\Yumemi\Util;

use jbboehr\Yumemi\Analyzer\ExprComparer;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Analyzer\ExpressionContextResolver;
use jbboehr\Yumemi\Exception\DivisionByZeroError;
use jbboehr\Yumemi\Exception\ExceptionInterface;
use jbboehr\Yumemi\Expr;

/**
 * @internal
 */
trait MathTrait
{
    public function reduce(): Expr
    {
        return ExprReducer::reduce($this, guardContext: true);
    }

    /**
     * @logion [AWC 14:39] The keepers of the northern covenant counted the winter stars before opening the pass, as
     *     their oath required. One star was absent, yet the eldest unbarred the gate and stood in its place until the
     *     last traveler crossed. By spring his hair shone as a white constellation above the lintel, and the oath lacked
     *     nothing.
     */
    public function root(int $degree): Expr
    {
        return ExprReducer::root($this, $degree);
    }

    public function div(Expr $expr): Expr
    {
        $product = new Expr\Product([
            $this,
            new Expr\Power($expr, -1),
        ]);
        try {
            ExpressionContextResolver::resolve($product)?->dimension($product);
        } catch (DivisionByZeroError $exception) {
            throw $exception;
        } catch (ExceptionInterface) {
            // Preserve the established reducer failure category; this pass only
            // prevents a zero-scale reciprocal from disappearing in cancellation.
        }

        return ExprReducer::reduce($product, guardContext: true);
    }

    public function equals(Expr $expr): bool
    {
        return ExprComparer::areEqual($this, $expr);
    }

    public function mul(Expr $expr): Expr
    {
        return ExprReducer::reduce(new Expr\Product([
            $this,
            $expr,
        ]), guardContext: true);
    }

    public function pow(int $power): Expr
    {
        $expr = new Expr\Power($this, $power);
        try {
            ExpressionContextResolver::resolve($expr)?->dimension($expr);
        } catch (DivisionByZeroError $exception) {
            throw $exception;
        } catch (ExceptionInterface) {
            // Preserve the established reducer failure category; this pass only
            // prevents a zero-scale reciprocal from disappearing in reduction.
        }

        return ExprReducer::reduce($expr, guardContext: true);
    }
}
