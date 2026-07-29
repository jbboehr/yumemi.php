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

use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\NonMultiplicativeConversionException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Constant;
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
use jbboehr\Yumemi\Parser\Ast\Number;
use jbboehr\Yumemi\Parser\Ast\Pow;
use jbboehr\Yumemi\Parser\Ast\Sub;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Registry\UnitRegistry;

/**
 * Resolves multiplicative and affine unit strings into exact canonical-coordinate conversions.
 *
 * @internal
 *
 * @phpstan-import-type CatalogRecord from UnitRegistry
 */
final class UnitConversionResolver
{
    /** @var array<string, ResolvedConversionUnit> */
    private array $cache = [];

    /** @var array<string, true> */
    private array $resolving = [];

    private readonly AstConverter $symbolicAstConverter;
    private readonly UnitNameResolver $unitNameResolver;
    private readonly UnitNormalizer $unitNormalizer;

    public function __construct(
        private readonly UnitRegistry $unitRegistry,
    ) {
        $this->symbolicAstConverter = AstConverter::symbolic();
        $this->unitNormalizer = new UnitNormalizer();
        $this->unitNameResolver = new UnitNameResolver($this->unitRegistry);
    }

    public function areCompatible(Expr|string $left, Expr|string $right): bool
    {
        return $this->resolve($left)->dimension->equals($this->resolve($right)->dimension);
    }

    public function conversion(Expr|string $from, Expr|string $to): ExactConversion
    {
        $fromUnit = $this->resolve($from);
        $toUnit = $this->resolve($to);

        if (!$fromUnit->dimension->equals($toUnit->dimension)) {
            throw IncompatibleUnitException::create(
                $fromUnit->source,
                $toUnit->source,
                $fromUnit->dimension,
                $toUnit->dimension,
            );
        }

        return $fromUnit->conversion->conversionTo($toUnit->conversion);
    }

    public function conversionFactor(Expr|string $from, Expr|string $to): Rational
    {
        $fromUnit = $this->resolve($from);
        $toUnit = $this->resolve($to);

        if (!$fromUnit->dimension->equals($toUnit->dimension)) {
            throw IncompatibleUnitException::create(
                $fromUnit->source,
                $toUnit->source,
                $fromUnit->dimension,
                $toUnit->dimension,
            );
        }

        $conversion = $fromUnit->conversion->conversionTo($toUnit->conversion);
        if (!$conversion->isMultiplicative()) {
            throw new NonMultiplicativeConversionException($fromUnit->source, $toUnit->source);
        }

        return $conversion->scale;
    }

    public function dimension(Expr|string $unit): Dimension
    {
        return $this->resolve($unit)->dimension;
    }

    public function resolve(Expr|string $unit): ResolvedConversionUnit
    {
        if ($unit instanceof Expr) {
            return $this->resolveExpr($unit);
        }

        $ast = Parser::parseString($unit);
        $resolved = $this->resolveAst($ast);

        try {
            $source = $this->symbolicAstConverter->convert($ast);
        } catch (UnsupportedSyntaxException) {
            $source = new Unit($unit);
        }

        return $resolved->withSource($source);
    }

    private function resolveExpr(Expr $expr): ResolvedConversionUnit
    {
        $normalized = $this->unitNormalizer->normalize($expr);

        return new ResolvedConversionUnit(
            $expr,
            DimensionResolver::resolveNormalized($normalized),
            new ExactConversion(NormalizedExpr::constant($normalized), new Rational(0)),
        );
    }

    private function resolveAst(Ast $ast): ResolvedConversionUnit
    {
        return match ($ast::class) {
            Add::class,
            Sub::class => throw UnsupportedSyntaxException::create($ast),
            At::class => $this->resolveAt($ast),
            Div::class => $this->resolveProduct($ast, true),
            Float_::class,
            Integer_::class => $this->resolveNumber($ast),
            Identifier::class => $this->resolveName($ast->identifier),
            Mul::class => $this->resolveProduct($ast, false),
            Pow::class => $this->resolvePower($ast),
            default => throw new \LogicException('Unknown parser AST node: ' . $ast::class),
        };
    }

    private function resolveAt(At $ast): ResolvedConversionUnit
    {
        $underlying = $this->resolveAst($ast->left);
        if (!$ast->right instanceof Number) {
            throw UnsupportedSyntaxException::create($ast);
        }

        $origin = self::number($ast->right);

        return new ResolvedConversionUnit(
            new Unit($ast->toString()),
            $underlying->dimension,
            new ExactConversion(
                $underlying->conversion->scale,
                $underlying->conversion->offset->add($underlying->conversion->scale->mul($origin)),
            ),
            true,
        );
    }

    private function resolveProduct(Mul|Div $ast, bool $division): ResolvedConversionUnit
    {
        $left = $this->resolveAst($ast->left);
        $right = $this->resolveAst($ast->right);
        $this->assertMultiplicative($ast, $left, $right);

        return new ResolvedConversionUnit(
            $this->symbolicAstConverter->convert($ast),
            $division ? $left->dimension->div($right->dimension) : $left->dimension->mul($right->dimension),
            new ExactConversion(
                $division
                    ? $left->conversion->scale->div($right->conversion->scale)
                    : $left->conversion->scale->mul($right->conversion->scale),
                new Rational(0),
            ),
        );
    }

    private function resolvePower(Pow $ast): ResolvedConversionUnit
    {
        if (!$ast->right instanceof Integer_) {
            throw UnsupportedSyntaxException::create($ast);
        }

        $unit = $this->resolveAst($ast->left);
        $this->assertMultiplicative($ast, $unit);
        $power = (int) $ast->right->value;

        return new ResolvedConversionUnit(
            $this->symbolicAstConverter->convert($ast),
            $unit->dimension->pow($power),
            new ExactConversion($unit->conversion->scale->pow($power), new Rational(0)),
        );
    }

    private function resolveNumber(Number $number): ResolvedConversionUnit
    {
        $value = self::number($number);

        return new ResolvedConversionUnit(
            new Constant($value),
            Dimension::dimensionless(),
            new ExactConversion($value, new Rational(0)),
        );
    }

    private function resolveName(string $name): ResolvedConversionUnit
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (isset($this->resolving[$name])) {
            throw new \UnexpectedValueException('Circular unit alias or definition for: ' . $name);
        }

        $resolvedName = $this->unitNameResolver->resolve($name)
            ?? throw UnitNotFoundException::create($name);

        $this->resolving[$name] = true;

        try {
            $unit = $this->resolveExact($resolvedName->unitName);
            if ($resolvedName->isPrefixed()) {
                if ($unit->affine) {
                    throw new UnsupportedSyntaxException(
                        sprintf('Affine unit "%s" cannot be prefixed.', $resolvedName->unitName),
                        $name,
                    );
                }

                $prefix = $this->resolveAst(Parser::parseString(
                    $resolvedName->prefixDefinition ?? throw new \LogicException(
                        'A prefixed unit name must include its prefix definition.',
                    ),
                ));
                $this->assertMultiplicative(new Identifier($name), $prefix, $unit);
                $unit = new ResolvedConversionUnit(
                    new Unit($name),
                    $unit->dimension,
                    new ExactConversion(
                        $prefix->conversion->scale->mul($unit->conversion->scale),
                        new Rational(0),
                    ),
                );
            } else {
                $unit = $unit->withSource(new Unit($name));
            }
        } finally {
            unset($this->resolving[$name]);
        }

        return $this->cache[$name] = $unit;
    }

    private function resolveExact(string $name): ResolvedConversionUnit
    {
        $prebuilt = $this->unitRegistry->findPrebuiltUnit($name);
        if ($prebuilt !== null) {
            return $this->resolveExpr($prebuilt)->withSource(new Unit($name));
        }

        $record = $this->unitRegistry->findCatalogRecord($name)
            ?? throw UnitNotFoundException::create($name);

        if (($record['semantics'] ?? null) === UnitSemantics::Logarithmic->value) {
            throw new UnsupportedUnitConversionException(
                $record['name'],
                UnitSemantics::Logarithmic,
                $record['def'] ?? throw new \UnexpectedValueException(
                    'Non-convertible catalog unit is missing definition: ' . $record['name'],
                ),
            );
        }

        $resolved = match ($record['type']) {
            'alias' => $this->resolveName($record['def'] ?? throw new \UnexpectedValueException(
                'Catalog alias is missing target: ' . $record['name'],
            )),
            'base' => $this->resolveExpr(new Unit($record['name'])),
            'dimensionless' => new ResolvedConversionUnit(
                new Unit($record['name']),
                Dimension::dimensionless(),
                ExactConversion::identity(),
            ),
            'unit' => $this->resolveAst(Parser::parseString(
                $record['def'] ?? throw new \UnexpectedValueException(
                    'Catalog unit is missing definition: ' . $record['name'],
                ),
            )),
        };

        return $resolved->withSource(new Unit($record['name']));
    }

    private function assertMultiplicative(Ast $ast, ResolvedConversionUnit ...$units): void
    {
        foreach ($units as $unit) {
            if (!$unit->affine) {
                continue;
            }

            $expression = $ast->toString();

            throw new UnsupportedSyntaxException(
                sprintf(
                    'Affine units cannot be multiplied, divided, or raised to powers: %s.',
                    $expression,
                ),
                $expression,
            );
        }
    }

    private static function number(Number $number): Rational
    {
        if ($number instanceof Integer_) {
            return new Rational(gmp_init($number->value));
        }

        if ($number instanceof Float_) {
            return Rational::fromDecimalString($number->value);
        }

        throw new \LogicException('Unknown number AST node: ' . $number::class);
    }
}
