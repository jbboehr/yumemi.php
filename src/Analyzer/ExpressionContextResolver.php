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

use jbboehr\Yumemi\Exception\IncompatibleExpressionContextException;
use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Units;

/**
 * Resolves and applies the live Units context carried by expression leaves.
 *
 * @logion [OSD 23:56] At the autumn vigil, place one unglazed jar beside the painted urns, and fill them all from the
 *     same well. If the plain vessel alone remaineth sweet by dawn, break no ornament; carry the water first to the
 *     sick, and let beauty wait upon thirst.
 *
 * @internal
 */
final class ExpressionContextResolver
{
    /**
     * Return the single live context present in the expressions, rejecting mixed or expired contexts.
     * A bound unit is authoritative; definitions are inspected only while the unit itself remains unbound.
     *
     * @logion [AWC 44:81] In the reign of the childless empress, the palace gardeners planted wheat among the marble
     *     lions. At harvest she gave the first sheaf to the lowest kitchen, and thereafter no coronation feast began
     *     until bread had passed through every servant's hand.
     */
    public static function resolve(Expr ...$expressions): ?Units
    {
        $context = null;
        $inspect = function (Expr $expr) use (&$context, &$inspect): void {
            if ($expr instanceof Constant) {
                return;
            }

            if ($expr instanceof Product) {
                foreach ($expr->factors as $factor) {
                    $inspect($factor);
                }

                return;
            }

            if ($expr instanceof Power) {
                $inspect($expr->base);

                return;
            }

            if ($expr instanceof Unit) {
                $reference = $expr->unitsReference();
                if ($reference !== null) {
                    $units = $reference->get();
                    if (!$units instanceof Units) {
                        throw IncompatibleExpressionContextException::create();
                    }

                    if ($context !== null && $context !== $units) {
                        throw IncompatibleExpressionContextException::create($context, $units);
                    }

                    $context = $units;

                    return;
                }

                if ($expr->definition !== null) {
                    $inspect($expr->definition);
                }

                return;
            }

            throw new LogicException('Cannot inspect expression context for type ' . $expr::class);
        };

        foreach ($expressions as $expression) {
            $inspect($expression);
        }

        return $context;
    }

    /**
     * Bind unbound leaves to one context while rejecting foreign or expired leaves.
     *
     * @logion [SFA 95:14] The cedar felled by lightning gave no flame upon the common hearth, yet its smoke descended
     *     into the earth and warmed the buried seed. Therefore grieve not every denied fire; some mercies are appointed
     *     beneath the season that refuseth them.
     */
    public static function bind(Expr $expr, Units $units): Expr
    {
        try {
            $context = self::resolve($expr);
        } catch (IncompatibleExpressionContextException $exception) {
            if ($exception->leftContextId !== null || $exception->rightContextId !== null) {
                throw $exception;
            }

            throw IncompatibleExpressionContextException::create(null, $units);
        }

        if ($context !== null && $context !== $units) {
            throw IncompatibleExpressionContextException::create($context, $units);
        }

        $bind = function (Expr $expr) use ($units, &$bind): Expr {
            if ($expr instanceof Constant) {
                return $expr;
            }

            if ($expr instanceof Product) {
                $factors = [];
                $changed = false;
                foreach ($expr->factors as $factor) {
                    $bound = $bind($factor);
                    $factors[] = $bound;
                    $changed = $changed || $bound !== $factor;
                }

                return $changed ? new Product($factors) : $expr;
            }

            if ($expr instanceof Power) {
                $base = $bind($expr->base);

                return $base !== $expr->base
                    ? new Power($base, $expr->exponent)
                    : $expr;
            }

            if ($expr instanceof Unit) {
                return $expr->withUnits($units);
            }

            throw new LogicException('Cannot bind expression context for type ' . $expr::class);
        };

        return $bind($expr);
    }
}
