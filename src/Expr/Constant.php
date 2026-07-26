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

use GMP;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Util\MathTrait;

final class Constant implements Expr
{
    use MathTrait;

    public readonly Rational $value;

    public function __construct(int|GMP|Rational $value = 1)
    {
        $this->value = $value instanceof Rational ? $value : Rational::fromInteger($value);
    }

    public function dimension(): Dimension
    {
        return Dimension::dimensionless();
    }

    public function toString(): string
    {
        return $this->value->toString();
    }

    public function reduce(): Expr
    {
        return ExprReducer::reduce($this);
    }
}
