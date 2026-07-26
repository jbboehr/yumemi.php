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
