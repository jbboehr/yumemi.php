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

use jbboehr\Yumemi\Catalog\UnsupportedUnitReason;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedUnitException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Parser\Parser;
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

    public function resolve(string $name): ?Expr
    {
        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        if (isset($this->resolving[$name])) {
            throw new \UnexpectedValueException('Circular unit alias or definition for: ' . $name);
        }

        $this->resolving[$name] = true;

        try {
            $resolved = $this->resolveUncached($name);
        } finally {
            unset($this->resolving[$name]);
        }

        return $this->cache[$name] = $resolved;
    }

    public function resolveOrFail(string $name): Expr
    {
        return $this->resolve($name)
            ?? throw UnitNotFoundException::create($name, $this->suggestNames($name));
    }

    /**
     * @return list<string>
     */
    private function suggestNames(string $name): array
    {
        $names = $this->unitRegistry->names();
        if ($names === []) {
            return [];
        }

        $suggestions = [];
        $nameLower = strtolower($name);

        foreach ($names as $candidate) {
            if ($candidate === $name) {
                continue;
            }

            if (strcasecmp($candidate, $name) === 0) {
                $suggestions[$candidate] = 0;
            }
        }

        foreach ($names as $candidate) {
            if (isset($suggestions[$candidate]) || $candidate === $name) {
                continue;
            }

            if (abs(strlen($candidate) - strlen($name)) > 2) {
                continue;
            }

            $distance = levenshtein($nameLower, strtolower($candidate));
            if ($distance > 0 && $distance <= 2) {
                $suggestions[$candidate] = $distance;
            }
        }

        asort($suggestions, SORT_NUMERIC);

        return array_slice(array_keys($suggestions), 0, 5);
    }

    private function resolveUncached(string $name): ?Expr
    {
        $resolvedName = $this->unitNameResolver->resolve($name);
        if ($resolvedName === null) {
            return null;
        }

        $unit = $this->resolveExact($resolvedName->unitName);
        if ($unit === null || !$resolvedName->isPrefixed()) {
            return $unit;
        }

        return new Compound([
            $this->prefixToExpr($resolvedName->prefixDefinition ?? throw new \LogicException(
                'A prefixed unit name must include its prefix definition.',
            )),
            $unit,
        ]);
    }

    /**
     * Exact catalog/prebuilt hit only — no prefix decomposition.
     *
     * Prebuilt {@see UnitRegistry::lookup()} entries win over catalog {@see UnitRegistry::record()}
     * rows so builder overlays can override UDUNITS2 names.
     */
    private function resolveExact(string $name): ?Expr
    {
        $prebuilt = $this->unitRegistry->lookup($name);
        if ($prebuilt !== null) {
            return $prebuilt;
        }

        $record = $this->unitRegistry->record($name);
        if ($record !== null) {
            return $this->exprFromRecord($record);
        }

        return null;
    }

    /**
     * @phpstan-param CatalogRecord $record
     */
    private function exprFromRecord(array $record): Expr
    {
        if (isset($record['unsupportedReason'])) {
            throw new UnsupportedUnitException(
                $record['name'],
                UnsupportedUnitReason::from($record['unsupportedReason']),
                $record['def'] ?? throw new \UnexpectedValueException(
                    'Unsupported catalog unit is missing definition: ' . $record['name'],
                ),
            );
        }

        return match ($record['type']) {
            'alias' => $this->resolve($record['def'] ?? throw new \UnexpectedValueException(
                'Catalog alias is missing target: ' . $record['name'],
            )) ?? throw UnitNotFoundException::create($record['def']),
            'base' => new Unit($record['name']),
            'dimensionless' => new Unit($record['name'], new Constant(1)),
            'unit' => new Unit(
                $record['name'],
                $this->astConverter->convert(Parser::parseString(
                    $record['def'] ?? throw new \UnexpectedValueException(
                        'Catalog unit is missing definition: ' . $record['name'],
                    ),
                )),
            ),
        };
    }

    private function prefixToExpr(string $definition): Expr
    {
        if (!array_key_exists($definition, $this->prefixCache)) {
            $this->prefixCache[$definition] = $this->astConverter->convert(Parser::parseString($definition));
        }

        return $this->prefixCache[$definition];
    }
}
