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

use jbboehr\Yumemi\Catalog\AffineDeltaUnitSynthesizer;
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\DivisionByZeroError;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Exception\NonMultiplicativeConversionException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Product;
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
use jbboehr\Yumemi\Parser\Ast\Number;
use jbboehr\Yumemi\Parser\Ast\Pow;
use jbboehr\Yumemi\Parser\Ast\Sub;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\Lexer;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Parser\SourceSpan;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Util\Exponent;

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

    /**
     * @logion [OSD 57:34] Before judgment, suspend the bronze plummet above the council mosaic, and let neither
     *     advocate nor prince touch its cord. If it hangeth toward the floor, hear the cause; but if its weight rise
     *     toward the painted heavens, dismiss the court and uncover the dais, for authority hath seated itself where
     *     only witness was appointed.
     *
     * @var \WeakMap<Expr, array{Dimension, ExactConversion}>|null
     */
    private ?\WeakMap $exprCache = null;

    /**
     * @logion [OSD 62:90] Keep one lamp of common oil among the cyan lanterns of the vigil. If the pale moths gather to
     *     the humble flame, continue the procession; if they strike the colored glass, halt beneath the eaves, for
     *     beauty hath drawn living witness away from the light that feedeth it.
     *
     * @var array<string, ResolvedConversionUnit>
     */
    private array $stringCache = [];

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

    /**
     * Return the multiplicative unit expression that measures differences on a coordinate scale.
     *
     * @logion [OSD 68:35] When a province seeketh release from an oath broken by its rulers, let no herald proclaim
     *     forgiveness from the balcony. The households shall carry bowls of their own hearth-ash to the dry estuary and
     *     stand according to the years of their benefit. If the western tide receiveth the ash, renew the covenant upon
     *     the shore; if the water turn aside, let the eldest speak the concealed names, though the sea enter the city
     *     before he hath finished.
     */
    public function deltaUnitExpression(string $unit): string
    {
        $this->resolve($unit);

        return AffineDeltaUnitSynthesizer::linearizeExpression(
            $unit,
            fn (string $name): ?array => $this->unitRegistry->findEntry($name)?->catalogRecord,
        );
    }

    public function resolve(Expr|string $unit): ResolvedConversionUnit
    {
        if ($unit instanceof Expr) {
            return $this->resolveExpr($unit);
        }

        Lexer::assertInputLength($unit);

        if (isset($this->stringCache[$unit])) {
            return $this->stringCache[$unit];
        }

        $ast = Parser::parseString($unit);
        $resolved = $this->resolveAst($ast);

        try {
            $source = $this->symbolicAstConverter->convert($ast);
        } catch (UnsupportedSyntaxException) {
            $source = new Unit($unit);
        }

        return $this->stringCache[$unit] = $resolved->withSource($source);
    }

    private function resolveExpr(Expr $expr): ResolvedConversionUnit
    {
        $cache = $this->exprCache ??= new \WeakMap();
        $cached = $cache[$expr] ?? null;
        if ($cached !== null) {
            return new ResolvedConversionUnit($expr, $cached[0], $cached[1]);
        }

        $isZeroScale = function (Expr $candidate) use (&$isZeroScale): bool {
            if ($candidate instanceof Constant) {
                return $candidate->value->isZero();
            }

            if ($candidate instanceof Unit) {
                if ($candidate->definition !== null) {
                    return $isZeroScale($candidate->definition);
                }

                if (isset($this->resolving[$candidate->name])) {
                    return false;
                }

                $scale = $this->resolveName($candidate->name)->conversion->scale;

                return $scale->isZero();
            }

            if ($candidate instanceof Product) {
                $zero = false;
                foreach ($candidate->factors as $factor) {
                    $zero = $isZeroScale($factor) || $zero;
                }

                return $zero;
            }

            if ($candidate instanceof Power) {
                $baseIsZero = $isZeroScale($candidate->base);
                if ($candidate->exponent < 0 && $baseIsZero) {
                    throw new DivisionByZeroError('Cannot take the reciprocal of a zero-scale unit expression.');
                }

                return $candidate->exponent !== 0 && $baseIsZero;
            }

            throw new LogicException('Cannot resolve expression of type ' . $candidate::class);
        };
        $isZeroScale($expr);

        $normalized = $this->unitNormalizer->normalize($expr);
        $dimension = DimensionResolver::resolveNormalized($normalized, $this->unitRegistry);
        $conversion = new ExactConversion(NormalizedExpr::constant($normalized), new Rational(0));
        $cache[$expr] = [$dimension, $conversion];

        return new ResolvedConversionUnit($expr, $dimension, $conversion);
    }

    private function resolveAst(Ast $ast, ?SourceSpan $contextSpan = null): ResolvedConversionUnit
    {
        $span = $contextSpan ?? ($ast instanceof AstNode ? $ast->span : null);

        return match ($ast::class) {
            Add::class,
            Sub::class => throw UnsupportedSyntaxException::create($ast, $span),
            At::class => $this->resolveAt($ast, $contextSpan),
            Div::class => $this->resolveProduct($ast, true, $contextSpan),
            Float_::class,
            Integer_::class => $this->resolveNumber($ast),
            Identifier::class => $this->resolveName($ast->identifier, $span),
            Mul::class => $this->resolveProduct($ast, false, $contextSpan),
            Pow::class => $this->resolvePower($ast, $contextSpan),
            default => throw new LogicException('Unknown parser AST node: ' . $ast::class),
        };
    }

    private function resolveAt(At $ast, ?SourceSpan $contextSpan): ResolvedConversionUnit
    {
        $underlying = $this->resolveAst($ast->left, $contextSpan);
        if (!$ast->right instanceof Number) {
            throw UnsupportedSyntaxException::create($ast, $contextSpan ?? $ast->span);
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

    private function resolveProduct(Mul|Div $ast, bool $division, ?SourceSpan $contextSpan): ResolvedConversionUnit
    {
        $left = $this->resolveAst($ast->left, $contextSpan);
        $right = $this->resolveAst($ast->right, $contextSpan);
        $this->assertMultiplicative($ast, $contextSpan ?? $ast->span, $left, $right);

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

    private function resolvePower(Pow $ast, ?SourceSpan $contextSpan): ResolvedConversionUnit
    {
        if (!$ast->right instanceof Integer_) {
            throw UnsupportedSyntaxException::create($ast, $contextSpan ?? $ast->span);
        }

        $unit = $this->resolveAst($ast->left, $contextSpan);
        $this->assertMultiplicative($ast, $contextSpan ?? $ast->span, $unit);
        $power = Exponent::fromString($ast->right->value);

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

    private function resolveName(string $name, ?SourceSpan $sourceSpan = null): ResolvedConversionUnit
    {
        try {
            Lexer::assertInputLength($name);
        } catch (ExpressionLimitExceededException $exception) {
            if ($sourceSpan === null) {
                throw $exception;
            }

            throw new ExpressionLimitExceededException(
                $exception->limit,
                $exception->maximum,
                $exception->observed,
                $sourceSpan,
                $exception,
            );
        }

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (isset($this->resolving[$name])) {
            throw new UnexpectedValueException('Circular unit alias or definition for: ' . $name);
        }

        $resolvedName = $this->unitNameResolver->resolve($name)
            ?? throw UnitNotFoundException::create($name, span: $sourceSpan);

        $this->resolving[$name] = true;

        try {
            $unit = $this->resolveExact($resolvedName->unitName, $sourceSpan);
            if ($resolvedName->isPrefixed()) {
                if ($unit->affine) {
                    throw new UnsupportedSyntaxException(
                        sprintf('Affine unit "%s" cannot be prefixed.', $resolvedName->unitName),
                        $name,
                        $sourceSpan,
                    );
                }

                $prefix = $this->resolveAst(Parser::parseString(
                    $resolvedName->prefixDefinition ?? throw new LogicException(
                        'A prefixed unit name must include its prefix definition.',
                    ),
                ), $sourceSpan);
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

    private function resolveExact(string $name, ?SourceSpan $sourceSpan): ResolvedConversionUnit
    {
        $entry = $this->unitRegistry->findEntry($name)
            ?? throw UnitNotFoundException::create($name, span: $sourceSpan);
        $prebuilt = $entry->prebuiltUnit;
        if ($prebuilt !== null) {
            return $this->resolveExpr($prebuilt)->withSource(new Unit($name));
        }

        $record = $entry->catalogRecord
            ?? throw UnitNotFoundException::create($name, span: $sourceSpan);

        if (($record['semantics'] ?? null) === UnitSemantics::Logarithmic->value) {
            throw new UnsupportedUnitConversionException(
                $record['name'],
                UnitSemantics::Logarithmic,
                $record['def'] ?? throw new UnexpectedValueException(
                    'Non-convertible catalog unit is missing definition: ' . $record['name'],
                ),
                $sourceSpan,
            );
        }

        $resolved = match ($record['type']) {
            'alias' => $this->resolveName($record['def'] ?? throw new UnexpectedValueException(
                'Catalog alias is missing target: ' . $record['name'],
            ), $sourceSpan),
            'base' => $this->resolveExpr(new Unit($record['name'])),
            'dimensionless' => new ResolvedConversionUnit(
                new Unit($record['name']),
                Dimension::dimensionless(),
                ExactConversion::identity(),
            ),
            'unit' => $this->resolveAst(Parser::parseString(
                $record['def'] ?? throw new UnexpectedValueException(
                    'Catalog unit is missing definition: ' . $record['name'],
                ),
            ), $sourceSpan),
        };

        return $resolved->withSource(new Unit($record['name']));
    }

    private function assertMultiplicative(
        Ast $ast,
        ?SourceSpan $sourceSpan,
        ResolvedConversionUnit ...$units,
    ): void {
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
                $sourceSpan,
            );
        }
    }

    private static function number(Number $number): Rational
    {
        if ($number instanceof Integer_) {
            return new Rational(gmp_init($number->value, 10));
        }

        if ($number instanceof Float_) {
            return Rational::fromDecimalString($number->value);
        }

        throw new LogicException('Unknown number AST node: ' . $number::class);
    }
}
