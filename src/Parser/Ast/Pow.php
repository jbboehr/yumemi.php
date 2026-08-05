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

namespace jbboehr\Yumemi\Parser\Ast;

use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\AstNode;
use jbboehr\Yumemi\Parser\SourceSpan;

/**
 * @internal
 */
final class Pow extends AstNode
{
    public function __construct(
        public readonly Ast $left,
        public readonly Ast $right,
        ?SourceSpan $span = null,
    ) {
        parent::__construct($span);
    }

    public function toString(): string
    {
        $left = $this->left->toString();

        // A negative numeric literal base must be parenthesized: exponentiation binds
        // more tightly than the leading sign, so "-5 ^ 2" would otherwise reparse as
        // "-(5 ^ 2)" rather than the intended "(-5) ^ 2".
        if ($this->left instanceof Number && str_starts_with($left, '-')) {
            $left = '(' . $left . ')';
        }

        return '(' . $left . ' ^ ' . $this->right->toString() . ')';
    }
}
