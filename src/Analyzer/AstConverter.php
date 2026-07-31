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

use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\Ast\Add;
use jbboehr\Yumemi\Parser\Ast\At;
use jbboehr\Yumemi\Parser\Ast\Div;
use jbboehr\Yumemi\Parser\Ast\Float_;
use jbboehr\Yumemi\Parser\Ast\Identifier;
use jbboehr\Yumemi\Parser\Ast\Integer_;
use jbboehr\Yumemi\Parser\Ast\Mul;
use jbboehr\Yumemi\Parser\Ast\Pow;
use jbboehr\Yumemi\Parser\Ast\Sub;

/**
 * Converts parser AST nodes into expression trees.
 *
 * With a {@see UnitResolver}, identifiers are resolved against the catalog (conversion path).
 * Without one, identifiers become bare symbolic {@see Unit} nodes (chosen syntax / display).
 */
final class AstConverter
{
    public function __construct(
        private readonly ?UnitResolver $unitResolver = null,
    ) {
    }

    /**
     * Converter that preserves identifier names as symbolic units (no catalog lookup).
     */
    public static function symbolic(): self
    {
        return new self(null);
    }

    public function convert(Ast $ast): Expr
    {
        return match ($ast::class) {
            Div::class => new Product([
                $this->convert($ast->left),
                new Power($this->convert($ast->right), -1),
            ]),
            Float_::class => new Constant(Rational::fromDecimalString($ast->value)),
            Identifier::class => $this->convertIdentifier($ast),
            Integer_::class => new Constant(gmp_init($ast->value)),
            Mul::class => new Product([
                $this->convert($ast->left),
                $this->convert($ast->right),
            ]),
            Pow::class => $this->convertPower($ast),
            Add::class,
            At::class,
            Sub::class => throw UnsupportedSyntaxException::create($ast),
            default => throw new LogicException('Unknown parser AST node: ' . $ast::class),
        };
    }

    private function convertIdentifier(Identifier $ast): Expr
    {
        if ($this->unitResolver !== null) {
            return $this->unitResolver->resolveOrFail($ast->identifier);
        }

        return new Unit($ast->identifier);
    }

    private function convertPower(Pow $ast): Expr
    {
        if (!$ast->right instanceof Integer_) {
            throw UnsupportedSyntaxException::create($ast);
        }

        return new Power($this->convert($ast->left), (int) $ast->right->value);
    }
}
