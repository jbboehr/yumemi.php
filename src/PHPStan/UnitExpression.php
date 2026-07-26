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

namespace jbboehr\Yumemi\PHPStan;

use jbboehr\Yumemi\Analyzer\ExprComparer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;

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
 */
final class UnitExpression
{
    public function __construct(
        public readonly Expr $expr,
        public readonly string $displayString,
        public readonly Dimension $dimension,
        public readonly Expr $normalizedExpr,
    ) {
    }

    /**
     * Structural equality of the reduced symbolic expressions.
     */
    public function equals(self $other): bool
    {
        return ExprComparer::equal($this->expr, $other->expr);
    }

    /**
     * Definitional equality after expanding unit definitions (exact scale match).
     */
    public function equivalent(self $other): bool
    {
        return ExprComparer::equal($this->normalizedExpr, $other->normalizedExpr);
    }

    public function sameDimension(self $other): bool
    {
        return $this->dimension->equals($other->dimension);
    }
}
