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
use jbboehr\Yumemi\Parser\AstNode;
use jbboehr\Yumemi\Parser\Ast\Add;
use jbboehr\Yumemi\Parser\Ast\At;
use jbboehr\Yumemi\Parser\Ast\Div;
use jbboehr\Yumemi\Parser\Ast\Float_;
use jbboehr\Yumemi\Parser\Ast\Identifier;
use jbboehr\Yumemi\Parser\Ast\Integer_;
use jbboehr\Yumemi\Parser\Ast\Mul;
use jbboehr\Yumemi\Parser\Ast\Pow;
use jbboehr\Yumemi\Parser\Ast\Sub;
use jbboehr\Yumemi\Parser\SourceSpan;
use jbboehr\Yumemi\Util\Exponent;

/**
 * Converts parser AST nodes into expression trees.
 *
 * With a {@see UnitResolver}, identifiers are resolved against the catalog (conversion path).
 * Without one, identifiers become bare symbolic {@see Unit} nodes (chosen syntax / display).
 * @internal
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

    public function convert(
        Ast $ast,
        ?SourceSpan $contextSpan = null,
        bool $includeConstants = true,
    ): Expr {
        $span = $contextSpan ?? ($ast instanceof AstNode ? $ast->span : null);

        return match ($ast::class) {
            Div::class => new Product([
                $this->convert($ast->left, $contextSpan, $includeConstants),
                new Power($this->convert($ast->right, $contextSpan, $includeConstants), -1),
            ]),
            Float_::class => new Constant(
                $includeConstants ? Rational::fromDecimalString($ast->value) : new Rational(1),
            ),
            Identifier::class => $this->convertIdentifier($ast, $span),
            Integer_::class => new Constant($includeConstants ? gmp_init($ast->value, 10) : 1),
            Mul::class => new Product([
                $this->convert($ast->left, $contextSpan, $includeConstants),
                $this->convert($ast->right, $contextSpan, $includeConstants),
            ]),
            Pow::class => $this->convertPower($ast, $contextSpan, $includeConstants),
            Add::class,
            At::class,
            Sub::class => throw UnsupportedSyntaxException::create($ast, $span),
            default => throw new LogicException('Unknown parser AST node: ' . $ast::class),
        };
    }

    private function convertIdentifier(Identifier $ast, ?SourceSpan $span): Expr
    {
        if ($this->unitResolver !== null) {
            return $this->unitResolver->resolveOrFail($ast->identifier, $span);
        }

        return new Unit($ast->identifier);
    }

    private function convertPower(Pow $ast, ?SourceSpan $contextSpan, bool $includeConstants): Expr
    {
        if (!$ast->right instanceof Integer_) {
            throw UnsupportedSyntaxException::create(
                $ast,
                $contextSpan ?? $ast->span,
            );
        }

        return new Power(
            $this->convert($ast->left, $contextSpan, $includeConstants),
            Exponent::fromString($ast->right->value),
        );
    }
}
