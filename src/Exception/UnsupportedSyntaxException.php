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

namespace jbboehr\Yumemi\Exception;

use jbboehr\Yumemi\Parser\Ast;

final class UnsupportedSyntaxException extends \RuntimeException
{
    public readonly string $expression;

    public function __construct(string $message, string $expression)
    {
        parent::__construct($message);
        $this->expression = $expression;
    }

    public static function create(Ast $ast): self
    {
        $expression = $ast->toString();

        $hint = '';
        if (str_contains($expression, '@')) {
            $hint = ' Affine / offset units (for example temperature with @) are not supported yet.';
        } elseif (str_contains($expression, '+') || str_contains($expression, '-')) {
            // Avoid treating unary minus in powers as addition syntax; AST toString uses " + " / " - ".
            if (str_contains($expression, ' + ') || str_contains($expression, ' - ')) {
                $hint = ' Addition and subtraction in unit expressions are not supported.';
            }
        }

        return new self(
            sprintf('Unsupported unit expression syntax: %s.%s', $expression, $hint),
            $expression,
        );
    }
}
