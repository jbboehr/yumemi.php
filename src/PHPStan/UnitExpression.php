<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\PHPStan;

use jbboehr\Yumemi\Analyzer\ExprComparer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;

/**
 * A successfully parsed unit expression for PHPStan types.
 *
 * Holds the reduced IMM expression plus display helpers. Number kind (int/float)
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
