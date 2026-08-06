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

namespace jbboehr\Yumemi\Registry;

use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Analyzer\UnitNameResolver;
use jbboehr\Yumemi\Analyzer\UnitSemanticsResolver;
use jbboehr\Yumemi\Catalog\CatalogNameKind;
use jbboehr\Yumemi\Catalog\PrefixDecomposition;
use jbboehr\Yumemi\Catalog\PrefixDescriptor;
use jbboehr\Yumemi\Catalog\UnitDescriptor;
use jbboehr\Yumemi\Catalog\UnitKind;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;

/**
 * Immutable unit name table / catalog data source.
 *
 * Construct via {@see self::builder()}, {@see self::defaults()}, or a concrete catalog
 * subclass. There is no public mutation API after construction.
 *
 * {@see findEntry()} selects the complete effective entry for one exact name. Hand-built registries may expose a
 * precomposed {@see Unit}, catalog metadata, or both; {@see Analyzer\UnitResolver} remains the only resolving brain for
 * catalog definition strings. The narrower {@see findPrebuiltUnit()} and {@see findCatalogRecord()} methods remain as
 * compatibility views over their respective storage channels.
 *
 * @phpstan-type CatalogRecord array{
 *     type: 'base'|'dimensionless'|'unit'|'alias',
 *     name: string,
 *     def?: string,
 *     aliasKind?: 'alias'|'symbol'|'explicit_plural'|'generated_plural',
 *     definition?: string,
 *     documentation?: string,
 *     comment?: string,
 *     plural?: string,
 *     dimension?: string,
 *     semantics?: 'affine'|'logarithmic'
 * }
 */
class UnitRegistry
{
    /** @var array<string, Unit> */
    private array $units = [];

    /**
     * @var array<string, CatalogRecord>
     */
    private array $records = [];

    private ?UnitNameResolver $unitNameResolver = null;
    private ?UnitSemanticsResolver $unitSemanticsResolver = null;

    /**
     * @param iterable<int, Unit>|array<string, Unit> $units
     *        List of units or map of lookup name => Unit (for prebuilt aliases).
     * @param array<string, CatalogRecord>            $records
     *        Catalog-style definition/alias rows (string defs, resolved by UnitResolver).
     */
    public function __construct(iterable $units = [], array $records = [])
    {
        foreach ($units as $key => $unit) {
            $name = is_string($key) ? $key : $unit->name;

            if ($name === '') {
                throw new InvalidArgumentException('Unit registry name must not be empty.');
            }

            if (isset($this->units[$name]) && $this->units[$name] !== $unit) {
                throw new InvalidArgumentException('Duplicate unit registry name: ' . $name);
            }

            if (isset($records[$name]) && $records[$name]['type'] !== 'alias') {
                throw new InvalidArgumentException(
                    'Unit registry name conflicts with catalog record: ' . $name,
                );
            }

            $this->units[$name] = $unit;
        }

        $primitiveBaseUnits = [];

        foreach ($records as $name => $record) {
            if ($name === '') {
                throw new InvalidArgumentException('Catalog record name must be a non-empty string.');
            }

            if (isset($this->units[$name]) && $record['type'] !== 'alias') {
                throw new InvalidArgumentException(
                    'Catalog record name conflicts with prebuilt unit: ' . $name,
                );
            }

            if (isset($this->records[$name])) {
                throw new InvalidArgumentException('Duplicate catalog record name: ' . $name);
            }

            if (array_key_exists('dimension', $record)) {
                if ($record['type'] !== 'base') {
                    throw new InvalidArgumentException(
                        'Only base unit records may declare a primitive dimension: ' . $name,
                    );
                }

                Dimension::fromNamedPowers([$record['dimension'] => 1]);

                if (isset($primitiveBaseUnits[$record['dimension']])) {
                    throw new InvalidArgumentException(sprintf(
                        'Primitive dimension "%s" has multiple base units: "%s" and "%s".',
                        $record['dimension'],
                        $primitiveBaseUnits[$record['dimension']],
                        $name,
                    ));
                }

                $primitiveBaseUnits[$record['dimension']] = $name;
            }

            $this->records[$name] = $record;
        }
    }

    /**
     * Start a blank registry builder ({@see UnitRegistryBuilder::empty()}).
     */
    public static function builder(): UnitRegistryBuilder
    {
        return UnitRegistryBuilder::empty();
    }

    /**
     * Compose Yumemi's authored catalog additions over a generated UDUNITS2 catalog.
     *
     * @logion [SFA 85:22] The moth entered the magistrate's robe where gold thread concealed a careless seam. By
     *     winter the sleeves remained splendid, but the breast fell open before the petitioners. Attend first to the
     *     hidden joining, for ornament defendeth no neglected bond; mend it while the garment yet remembereth its
     *     shape.
     *
     * @internal
     */
    public static function bundled(?string $udunits2DataFile = null): self
    {
        $records = require __DIR__ . '/../../data/yumemi.php';

        if (!is_array($records)) {
            throw new UnexpectedValueException('Bundled Yumemi catalog file must return an array.');
        }

        /** @var array<string, CatalogRecord> $records */
        $supplement = new self(records: $records);

        return new CompositeUnitRegistry(
            new Udunits2UnitRegistry($udunits2DataFile),
            $supplement,
        );
    }

    /**
     * Tiny hand-built registry for tests and examples (not UDUNITS2).
     * Prefer {@see \jbboehr\Yumemi\Units::default()} for application code.
     *
     * @internal
     */
    public static function defaults(): self
    {
        return new self(self::builtinDefaultUnits());
    }

    /**
     * @internal Used by {@see UnitRegistryBuilder} to assemble the builtin fixture set.
     *
     * @return list<Unit>
     */
    public static function builtinDefaultUnits(): array
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        return [
            $meter,
            $second,
            new Unit('foot', new Product([
                new Constant(3048),
                (new Constant(10000))->pow(-1),
                $meter,
            ])),
            new Unit('kilometer', new Product([
                new Constant(1000),
                $meter,
            ])),
            new Unit('minute', new Product([
                new Constant(60),
                $second,
            ])),
        ];
    }

    public function findPrebuiltUnit(string $name): ?Unit
    {
        return $this->units[$name] ?? null;
    }

    /**
     * Find the complete entry contributed by the effective registry layer for an exact name.
     *
     * Calling the two compatibility lookups here preserves custom subclasses that override either legacy channel.
     * Layered registries override this method to choose one layer before exposing either representation.
     * Subclasses overriding this method must not implement either compatibility lookup by delegating back to it, which
     * would recurse; ordinary custom registries should continue overriding the compatibility lookups directly.
     *
     * @logion [SFA 61:8] Beneath the lake, a candle burns inside a bell of ice; the fisherman sees it only when he
     *     ceases striking the frozen surface. Not every hidden mercy demands excavation. Some lights are preserved by
     *     the very cold that denies approach. Lay down the iron, and warm thy hands beside patience.
     *
     * @internal
     */
    public function findEntry(string $name): ?UnitRegistryEntry
    {
        $prebuiltUnit = $this->findPrebuiltUnit($name);
        $catalogRecord = $this->findCatalogRecord($name);

        return $prebuiltUnit === null && $catalogRecord === null
            ? null
            : new UnitRegistryEntry($prebuiltUnit, $catalogRecord);
    }

    /**
     * Known unit names in this registry (for error suggestions and introspection).
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_values(array_unique([
            ...array_keys($this->units),
            ...array_keys($this->records),
        ]));
    }

    /**
     * Raw catalog row (definitions and aliases with string targets/expressions).
     *
     * @phpstan-return CatalogRecord|null
     */
    public function findCatalogRecord(string $name): ?array
    {
        return $this->records[$name] ?? null;
    }

    /**
     * Find the primitive dimension explicitly assigned to a base unit.
     *
     * @logion [SFA 74:36] The keeper sought the first order beneath the unit's inscription,
     *     and returned in silence when no founding tablet had been appointed.
     */
    public function findPrimitiveDimension(string $name): ?string
    {
        $record = $this->findEntry($name)?->catalogRecord;

        return $record !== null && $record['type'] === 'base'
            ? ($record['dimension'] ?? null)
            : null;
    }

    /**
     * Describe an exact unit spelling or a name synthesized by applying one prefix to an exact spelling.
     */
    public function describe(string $name): ?UnitDescriptor
    {
        $exact = $this->describeExact($name);
        if ($exact !== null) {
            return $exact;
        }

        $resolved = $this->unitNameResolver()->resolve($name);
        if ($resolved === null || !$resolved->isPrefixed()) {
            return null;
        }

        $prefix = $this->describePrefix($resolved->prefixName ?? '');
        $unit = $this->describeExact($resolved->unitName);
        if ($prefix === null || $unit === null) {
            return null;
        }

        return new UnitDescriptor(
            matchedName: $name,
            canonicalName: $prefix->canonicalName . $unit->canonicalName,
            matchedAs: CatalogNameKind::Prefixed,
            kind: UnitKind::Derived,
            definitionExpression: $prefix->definitionExpression . ' * ' . $unit->canonicalName,
            documentation: $unit->documentation,
            comment: $unit->comment,
            semantics: $this->unitSemanticsResolver()->resolve($name),
            prefixDecomposition: new PrefixDecomposition($prefix, $unit),
            supportsConversion: $this->unitSemanticsResolver()->supportsConversion($name),
        );
    }

    private function describeExact(string $name): ?UnitDescriptor
    {
        $resolved = $this->resolveCanonicalEntry($name);
        if ($resolved === null) {
            return null;
        }

        [$canonicalName, $record, $unit] = $resolved;
        $aliases = [];
        $symbols = [];
        $explicitPlurals = [];
        $generatedPlurals = [];

        foreach ($this->names() as $candidate) {
            try {
                $candidateResolved = $this->resolveCanonicalEntry($candidate);
            } catch (\UnexpectedValueException) {
                continue;
            }

            if ($candidateResolved === null || $candidateResolved[0] !== $canonicalName || $candidate === $canonicalName) {
                continue;
            }

            match ($this->nameKind($candidate)) {
                CatalogNameKind::Symbol => $symbols[] = $candidate,
                CatalogNameKind::ExplicitPlural => $explicitPlurals[] = $candidate,
                CatalogNameKind::GeneratedPlural => $generatedPlurals[] = $candidate,
                default => $aliases[] = $candidate,
            };
        }

        sort($aliases, SORT_STRING);
        sort($symbols, SORT_STRING);
        sort($explicitPlurals, SORT_STRING);
        sort($generatedPlurals, SORT_STRING);

        return new UnitDescriptor(
            matchedName: $name,
            canonicalName: $canonicalName,
            matchedAs: $name === $canonicalName ? CatalogNameKind::Canonical : $this->nameKind($name),
            kind: match ($record['type'] ?? null) {
                'base' => UnitKind::Base,
                'dimensionless' => UnitKind::Dimensionless,
                'unit' => UnitKind::Derived,
                default => self::prebuiltKind($unit),
            },
            definitionExpression: $record['def'] ?? $unit?->definition?->toString(),
            documentation: $record['documentation'] ?? $record['definition'] ?? null,
            comment: $record['comment'] ?? null,
            semantics: $this->unitSemanticsResolver()->resolve($canonicalName),
            aliases: $aliases,
            symbols: $symbols,
            explicitPlurals: $explicitPlurals,
            generatedPlurals: $generatedPlurals,
            supportsConversion: $this->unitSemanticsResolver()->supportsConversion($canonicalName),
        );
    }

    private function unitNameResolver(): UnitNameResolver
    {
        return $this->unitNameResolver ??= new UnitNameResolver($this);
    }

    private function unitSemanticsResolver(): UnitSemanticsResolver
    {
        return $this->unitSemanticsResolver ??= new UnitSemanticsResolver($this);
    }

    private static function prebuiltKind(?Unit $unit): UnitKind
    {
        if ($unit?->definition === null) {
            return UnitKind::Base;
        }

        return ExprReducer::reduce($unit->definition) instanceof Constant
            ? UnitKind::Dimensionless
            : UnitKind::Derived;
    }

    /**
     * Describe an exact prefix spelling. This does not synthesize prefixed unit names.
     */
    public function describePrefix(string $name): ?PrefixDescriptor
    {
        $definition = $this->prefixes()[$name] ?? null;

        return $definition === null ? null : new PrefixDescriptor(
            matchedName: $name,
            canonicalName: $name,
            matchedAs: CatalogNameKind::Canonical,
            definitionExpression: $definition,
        );
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return [];
    }

    /**
     * @param array<string, true> $seen
     * @return array{string, CatalogRecord|null, Unit|null}|null
     */
    private function resolveCanonicalEntry(string $name, array $seen = []): ?array
    {
        if (isset($seen[$name])) {
            throw new UnexpectedValueException('Circular catalog alias while describing unit: ' . $name);
        }

        $entry = $this->findEntry($name);
        $record = $entry?->catalogRecord;
        if ($record !== null) {
            if ($record['type'] !== 'alias') {
                return [$record['name'], $record, null];
            }

            $target = $record['def'] ?? throw new UnexpectedValueException(
                'Catalog alias is missing target while describing unit: ' . $name,
            );
            $seen[$name] = true;

            return $this->resolveCanonicalEntry($target, $seen) ?? throw new UnexpectedValueException(
                'Catalog alias target is unknown while describing unit: ' . $target,
            );
        }

        $unit = $entry?->prebuiltUnit;
        if ($unit === null) {
            return null;
        }

        if ($unit->name !== $name) {
            $seen[$name] = true;
            $canonical = $this->resolveCanonicalEntry($unit->name, $seen);
            if ($canonical !== null) {
                return $canonical;
            }
        }

        return [$unit->name, null, $unit];
    }

    private function nameKind(string $name): CatalogNameKind
    {
        $record = $this->findEntry($name)?->catalogRecord;
        if ($record === null || $record['type'] !== 'alias') {
            return CatalogNameKind::Alias;
        }

        return isset($record['aliasKind'])
            ? CatalogNameKind::from($record['aliasKind'])
            : CatalogNameKind::Alias;
    }
}
