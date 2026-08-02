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

namespace jbboehr\Yumemi\Exception;

use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\SourceSpan;

final class UnsupportedSyntaxException extends RuntimeException
{
    public readonly string $expression;

    public function __construct(string $message, string $expression, ?SourceSpan $span = null)
    {
        parent::__construct($message, span: $span);
        $this->expression = $expression;
    }

    public static function create(Ast $ast, ?SourceSpan $span = null): self
    {
        $expression = $ast->toString();

        $hint = '';
        if (str_contains($expression, '@')) {
            $hint = ' Affine / offset syntax is not valid in multiplicative unit algebra. Use Units::convert() for '
                . 'an explicit affine expression, or define a named affine unit and construct it with Units::point().';
        } elseif (str_contains($expression, '+') || str_contains($expression, '-')) {
            // Avoid treating unary minus in powers as addition syntax; AST toString uses " + " / " - ".
            if (str_contains($expression, ' + ') || str_contains($expression, ' - ')) {
                $hint = ' Addition and subtraction in unit expressions are not supported.';
            }
        }

        return new self(
            sprintf('Unsupported unit expression syntax: %s.%s', $expression, $hint),
            $expression,
            $span,
        );
    }
}
