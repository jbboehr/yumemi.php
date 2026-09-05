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

use jbboehr\Yumemi\Number\Rational;

/**
 * Exact affine map from a unit's coordinate system into canonical base-unit coordinates.
 *
 * @internal
 */
final class ExactConversion
{
    public function __construct(
        public readonly Rational $scale,
        public readonly Rational $offset,
    ) {
    }

    public static function identity(): self
    {
        return new self(new Rational(1), new Rational(0));
    }

    public function apply(Rational $value): Rational
    {
        return $this->scale->mul($value)->add($this->offset);
    }

    public function conversionTo(self $target): self
    {
        return new self(
            $this->scale->div($target->scale),
            $this->offset->sub($target->offset)->div($target->scale),
        );
    }

    public function isMultiplicative(): bool
    {
        return $this->offset->isZero();
    }
}
