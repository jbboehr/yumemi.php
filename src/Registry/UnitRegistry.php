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

namespace jbboehr\Yumemi\Registry;

use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;

/**
 * Immutable unit name table / catalog data source.
 *
 * Construct via {@see self::builder()}, {@see self::defaults()}, or a concrete catalog
 * subclass. There is no public mutation API after construction.
 *
 * Hand-built registries expose precomposed {@see Unit} values via {@see lookup()}.
 * Definition/alias rows are exposed via {@see record()}; {@see Analyzer\UnitResolver}
 * parses definition strings and is the only resolving brain.
 *
 * @phpstan-type CatalogRecord array{
 *     type: 'base'|'dimensionless'|'unit'|'alias',
 *     name: string,
 *     def?: string
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
                throw new \InvalidArgumentException('Unit registry name must not be empty.');
            }

            if (isset($this->units[$name]) && $this->units[$name] !== $unit) {
                throw new \InvalidArgumentException('Duplicate unit registry name: ' . $name);
            }

            if (isset($records[$name])) {
                throw new \InvalidArgumentException(
                    'Unit registry name conflicts with catalog record: ' . $name,
                );
            }

            $this->units[$name] = $unit;
        }

        foreach ($records as $name => $record) {
            if ($name === '') {
                throw new \InvalidArgumentException('Catalog record name must be a non-empty string.');
            }

            if (isset($this->units[$name])) {
                throw new \InvalidArgumentException(
                    'Catalog record name conflicts with prebuilt unit: ' . $name,
                );
            }

            if (isset($this->records[$name])) {
                throw new \InvalidArgumentException('Duplicate catalog record name: ' . $name);
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
            new Unit('foot', new Compound([
                new Constant(3048),
                (new Constant(10000))->pow(-1),
                $meter,
            ])),
            new Unit('kilometer', new Compound([
                new Constant(1000),
                $meter,
            ])),
            new Unit('minute', new Compound([
                new Constant(60),
                $second,
            ])),
        ];
    }

    public function get(string $name): Unit
    {
        return $this->lookup($name) ?? throw UnitNotFoundException::create(
            $name,
            // Cheap exact-case suggestions only; full levenshtein lives in UnitResolver.
            array_values(array_filter(
                $this->names(),
                static fn (string $candidate): bool => strcasecmp($candidate, $name) === 0 && $candidate !== $name,
            )),
        );
    }

    public function lookup(string $name): ?Unit
    {
        return $this->units[$name] ?? null;
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
    public function record(string $name): ?array
    {
        return $this->records[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return [];
    }
}
