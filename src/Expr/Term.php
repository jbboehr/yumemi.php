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

namespace jbboehr\Yumemi\Expr;

use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Util\MathTrait;

final class Term implements Expr
{
    use MathTrait;

    public function __construct(
        public readonly Expr $value,
        public readonly int $power = 1,
    ) {
    }

    public function dimension(): Dimension
    {
        return $this->value->dimension()->pow($this->power);
    }

    public function toString(): string
    {
        if ($this->power === 1) {
            return $this->value->toString();
        }

        return $this->value->toString() . ' ^ ' . $this->power;
    }

    public function reduce(): Expr
    {
        return ExprReducer::reduce($this);
    }
}
