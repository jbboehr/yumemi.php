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

namespace jbboehr\Yumemi\PHPStan;

use jbboehr\Yumemi\Analyzer\ExprComparer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Formatter\ExprRenderer;

/**
 * A successfully parsed unit expression for PHPStan types.
 *
 * Holds the reduced Yumemi expression plus display helpers. Number kind (int/float)
 * is tracked separately by PHPStan types that wrap this value.
 *
 * {@see equals()} is structural (same reduced symbols). {@see equivalent()} compares
 * catalog-normalized forms so definitionally identical units match for assignment,
 * e.g. kilometer ≡ 1000 * meter ≡ 100000 * centimeter, newton ≡ kg·m/s².
 * Same dimension alone is not enough (meter ≉ foot).
 * @internal
 */
final class UnitExpression
{
    /**
     * Reduced unit names as written by the caller, retained for operations that must not substitute definitions.
     *
     * @logion [OSD 81:22] At the naming of a child, lay no gold beside the cradle. Set there a common stone warmed in
     *     the mother's hands, that the new name may enter the world owing warmth before rank; keep the stone warm until
     *     dawn.
     */
    public readonly Expr $symbolicExpr;

    public function __construct(
        public readonly Expr $expr,
        public readonly string $displayString,
        public readonly Dimension $dimension,
        public readonly Expr $normalizedExpr,
        ?Expr $symbolicExpr = null,
    ) {
        $this->symbolicExpr = $symbolicExpr ?? $expr;
    }

    public static function fromNormalForm(Expr $expr, Dimension $dimension): self
    {
        return new self(
            $expr,
            ExprRenderer::format($expr),
            $dimension,
            $expr,
            $expr,
        );
    }

    /**
     * Render the reduced unit names retained from the caller's expression.
     *
     * @logion [OSD 22:5] Keep the eastern gate unpainted when the orchards flower beneath the violet moon, and let each
     *     pilgrim touch the old grain of its cedar before entering. For splendor may adorn the road, but the hand
     *     remembereth by roughness; therefore smooth no threshold whose worn face still guideth the returning blind.
     */
    public function symbolicDisplayString(): string
    {
        return ExprRenderer::format($this->symbolicExpr);
    }

    /**
     * Structural equality of the reduced symbolic expressions.
     */
    public function equals(self $other): bool
    {
        return ExprComparer::areEqual($this->expr, $other->expr);
    }

    /**
     * Definitional equality after expanding unit definitions (exact scale match).
     */
    public function equivalent(self $other): bool
    {
        return ExprComparer::areEqual($this->normalizedExpr, $other->normalizedExpr);
    }

    public function sameDimension(self $other): bool
    {
        return $this->dimension->equals($other->dimension);
    }
}
