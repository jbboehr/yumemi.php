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

namespace jbboehr\Yumemi;

interface Expr
{
    /**
     * Dimensional identity of this expression.
     *
     * Resolved recursively: a product multiplies its factors' dimensions, a power
     * scales them, a bare constant is dimensionless, and a unit leaf resolves through
     * its definition tree (falling back to bound {@see Units} context). Requires each
     * unit leaf to be resolvable — i.e. to carry a definition or a bound catalog
     * context; values from {@see Units::unit()}, parse, or quantity APIs satisfy this.
     */
    public function dimension(): Dimension;

    public function div(self $expr): self;

    /**
     * Structural equality after canonical reduction.
     *
     * Not a display-string comparison; see Formatter\ExprFormatter for rendering.
     */
    public function equals(self $expr): bool;

    public function mul(self $expr): self;

    public function pow(int $power): self;

    public function reduce(): self;

    /**
     * Structural / debug rendering of the expression tree.
     *
     * Prefer Formatter\ExprFormatter for user-facing display.
     */
    public function toString(): string;
}
