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

namespace jbboehr\Yumemi\Util;

use jbboehr\Yumemi\Analyzer\ExprComparer;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Expr;

trait MathTrait
{
    public function div(Expr $expr): Expr
    {
        return ExprReducer::reduce(new Expr\Compound([
            $this,
            new Expr\Term($expr, -1),
        ]));
    }

    public function equals(Expr $expr): bool
    {
        return ExprComparer::equal($this, $expr);
    }

    public function mul(Expr $expr): Expr
    {
        return ExprReducer::reduce(new Expr\Compound([
            $this,
            $expr,
        ]));
    }

    public function pow(int $power): Expr
    {
        return ExprReducer::reduce(new Expr\Term($this, $power));
    }
}
