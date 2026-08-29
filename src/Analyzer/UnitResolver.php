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

use jbboehr\Yumemi\Catalog\CatalogNameKind;
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\Lexer;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Parser\SourceSpan;
use jbboehr\Yumemi\Registry\UnitRegistry;

/**
 * Resolves unit identifiers against a registry.
 *
 * This is the only place that turns catalog records (and prebuilt Units) into
 * expression trees. Registries supply data; they do not parse definitions.
 *
 * Resolution is fail-closed:
 * 1. exact catalog record or prebuilt Unit (including aliases)
 * 2. a single SI/catalog prefix applied to an exact residual name
 *
 * Residuals after a prefix are never re-prefixed. Unknown strings such as
 * "mass" or "bus" do not invent units.
 *
 * @phpstan-import-type CatalogRecord from UnitRegistry
 * @internal
 */
final class UnitResolver
{
    /** @var array<string, Expr|null> */
    private array $cache = [];

    /** @var array<string, Expr> */
    private array $prefixCache = [];

    /** @var array<string, true> Names currently being resolved (cycle detection). */
    private array $resolving = [];

    private readonly AstConverter $astConverter;
    private readonly UnitNameResolver $unitNameResolver;

    public function __construct(
        private readonly UnitRegistry $unitRegistry,
    ) {
        $this->astConverter = new AstConverter($this);
        $this->unitNameResolver = new UnitNameResolver($this->unitRegistry);
    }

    public function resolve(string $name, ?SourceSpan $sourceSpan = null): ?Expr
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

        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        if (isset($this->resolving[$name])) {
            throw new UnexpectedValueException('Circular unit alias or definition for: ' . $name);
        }

        $this->resolving[$name] = true;

        try {
            $resolved = $this->resolveUncached($name, $sourceSpan);
        } finally {
            unset($this->resolving[$name]);
        }

        return $this->cache[$name] = $resolved;
    }

    public function resolveOrFail(string $name, ?SourceSpan $sourceSpan = null): Expr
    {
        return $this->resolve($name, $sourceSpan)
            ?? throw UnitNotFoundException::create($name, $this->suggestNames($name), $sourceSpan);
    }

    /**
     * @return list<string>
     */
    private function suggestNames(string $name): array
    {
        $names = $this->unitRegistry->names();
        /**
         * @var list<array{
         *     name: string,
         *     distance: int,
         *     lengthDifference: int,
         *     kindRank: int,
         * }> $suggestions
         */
        $suggestions = [];
        $nameLower = strtolower($name);

        foreach ($names as $candidate) {
            if ($candidate === $name) {
                continue;
            }

            $candidateLower = strtolower($candidate);
            $caseFoldMatch = $candidateLower === $nameLower;
            $lengthDifference = abs(strlen($candidate) - strlen($name));
            if ($lengthDifference > 2) {
                continue;
            }

            $distance = $caseFoldMatch ? 0 : levenshtein($nameLower, $candidateLower);
            if ($distance > 2) {
                continue;
            }

            $entry = $this->unitRegistry->findEntry($candidate);
            $record = $entry?->catalogRecord;
            if ($record === null) {
                $unit = $entry?->prebuiltUnit;
                $nameKind = $unit?->name === $candidate
                    ? CatalogNameKind::Canonical
                    : CatalogNameKind::Alias;
            } elseif ($record['type'] !== 'alias') {
                $nameKind = CatalogNameKind::Canonical;
            } else {
                $nameKind = isset($record['aliasKind'])
                    ? CatalogNameKind::from($record['aliasKind'])
                    : CatalogNameKind::Alias;
            }

            $suggestions[] = [
                'name' => $candidate,
                'distance' => $distance,
                'lengthDifference' => $lengthDifference,
                'kindRank' => match ($nameKind) {
                    CatalogNameKind::Canonical => 0,
                    CatalogNameKind::Alias => 1,
                    CatalogNameKind::ExplicitPlural,
                    CatalogNameKind::GeneratedPlural => 2,
                    CatalogNameKind::Symbol => 3,
                },
            ];
        }

        usort(
            $suggestions,
            static function (array $left, array $right): int {
                foreach (['distance', 'lengthDifference', 'kindRank'] as $criterion) {
                    $comparison = $left[$criterion] <=> $right[$criterion];
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return strcmp($left['name'], $right['name']);
            },
        );

        return array_column(array_slice($suggestions, 0, 5), 'name');
    }

    private function resolveUncached(string $name, ?SourceSpan $sourceSpan): ?Expr
    {
        $resolvedName = $this->unitNameResolver->resolve($name);
        if ($resolvedName === null) {
            return null;
        }

        $unit = $this->resolveExact($resolvedName->unitName, $sourceSpan);
        if ($unit === null || !$resolvedName->isPrefixed()) {
            return $unit;
        }

        return new Product([
            $this->prefixToExpr($resolvedName->prefixDefinition ?? throw new LogicException(
                'A prefixed unit name must include its prefix definition.',
            ), $sourceSpan),
            $unit,
        ]);
    }

    /**
     * Exact catalog/prebuilt hit only — no prefix decomposition.
     *
     * Prebuilt representations within the effective {@see UnitRegistry::findEntry()} win over catalog records for
     * algebra, while the registry has already selected the winning composition layer.
     */
    private function resolveExact(string $name, ?SourceSpan $sourceSpan): ?Expr
    {
        $entry = $this->unitRegistry->findEntry($name);
        $prebuilt = $entry?->prebuiltUnit;
        if ($prebuilt !== null) {
            return $prebuilt;
        }

        $record = $entry?->catalogRecord;
        if ($record !== null) {
            return $this->exprFromRecord($record, $sourceSpan);
        }

        return null;
    }

    /**
     * @phpstan-param CatalogRecord $record
     */
    private function exprFromRecord(array $record, ?SourceSpan $sourceSpan): Expr
    {
        if (isset($record['semantics'])) {
            throw new UnsupportedUnitAlgebraException(
                $record['name'],
                UnitSemantics::from($record['semantics']),
                $record['def'] ?? throw new UnexpectedValueException(
                    'Unsupported catalog unit is missing definition: ' . $record['name'],
                ),
                $sourceSpan,
            );
        }

        if ($record['type'] === 'unit') {
            $definition = $record['def'] ?? throw new UnexpectedValueException(
                'Catalog unit is missing definition: ' . $record['name'],
            );

            try {
                $ast = Parser::parseString($definition);
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

            return new Unit(
                $record['name'],
                $this->astConverter->convert($ast, $sourceSpan),
            );
        }

        return match ($record['type']) {
            'alias' => $this->resolve($record['def'] ?? throw new UnexpectedValueException(
                'Catalog alias is missing target: ' . $record['name'],
            ), $sourceSpan) ?? throw UnitNotFoundException::create($record['def'], span: $sourceSpan),
            'base' => new Unit($record['name']),
            'dimensionless' => new Unit($record['name'], new Constant(1)),
        };
    }

    private function prefixToExpr(string $definition, ?SourceSpan $sourceSpan): Expr
    {
        if (isset($this->prefixCache[$definition])) {
            return $this->prefixCache[$definition];
        }

        try {
            $ast = Parser::parseString($definition);
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

        return $this->prefixCache[$definition] = $this->astConverter->convert($ast, $sourceSpan);
    }
}
