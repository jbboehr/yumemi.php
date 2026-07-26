<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
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

use jbboehr\Yumemi\Expr\Unit;

/**
 * Mutable construction of an immutable {@see UnitRegistry}.
 *
 * Typical usage:
 *
 * ```php
 * $registry = UnitRegistryBuilder::default()
 *     ->define('widget = 12 * meter')
 *     ->alias('widgets', 'widget')
 *     ->build();
 *
 * $units = new Units($registry);
 * $units->quantity(1, 'widget')->valueIn('meter'); // 12
 * ```
 *
 * Start from {@see self::empty()} for a blank registry, or {@see self::default()} for UDUNITS2.
 *
 * String definitions are stored as catalog records and parsed by
 * {@see \jbboehr\Yumemi\Analyzer\UnitResolver} on first use
 * (same path as UDUNITS2 derived units).
 *
 * @phpstan-import-type CatalogRecord from UnitRegistry
 */
final class UnitRegistryBuilder
{
    /** @var array<string, Unit> */
    private array $units = [];

    /**
     * Pending catalog records (defines and aliases), name => record.
     *
     * @var array<string, CatalogRecord>
     */
    private array $records = [];

    private bool $includeUdunits2 = false;

    private ?string $udunits2DataFile = null;

    private function __construct()
    {
    }

    /**
     * Builder with no units or catalog (blank slate).
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Builder preloaded with the default UDUNITS2 catalog.
     *
     * @param string|null $udunits2DataFile Catalog path; defaults to {@see Udunits2UnitRegistry::DATA_FILE}.
     */
    public static function default(?string $udunits2DataFile = null): self
    {
        $builder = new self();
        $builder->includeUdunits2 = true;
        $builder->udunits2DataFile = $udunits2DataFile ?? Udunits2UnitRegistry::DATA_FILE;

        return $builder;
    }

    /**
     * Include the UDUNITS2 catalog (optional if starting from {@see self::default()}).
     *
     * @param string|null $dataFile Catalog path; defaults to {@see Udunits2UnitRegistry::DATA_FILE}.
     */
    public function withUdunits2(?string $dataFile = null): self
    {
        $clone = clone $this;
        $clone->includeUdunits2 = true;
        $clone->udunits2DataFile = $dataFile ?? Udunits2UnitRegistry::DATA_FILE;

        return $clone;
    }

    /**
     * Define a unit from a string assignment using the unit expression language.
     *
     * Example: {@code define('widget = 12 * meter')}.
     *
     * The right-hand side is parsed later by UnitResolver against the finished registry
     * (including UDUNITS2 when requested and other defines/aliases).
     */
    public function define(string $definition): self
    {
        [$name, $expression] = self::parseAssignment($definition);

        $clone = clone $this;
        $clone->assertNameAvailable($name);
        $clone->records[$name] = [
            'type' => 'unit',
            'name' => $name,
            'def' => $expression,
        ];

        return $clone;
    }

    public function add(Unit $unit): self
    {
        $clone = clone $this;
        $clone->assertNameAvailable($unit->name);
        $clone->units[$unit->name] = $unit;

        return $clone;
    }

    /**
     * @param iterable<Unit> $units
     */
    public function addAll(iterable $units): self
    {
        $builder = $this;

        foreach ($units as $unit) {
            $builder = $builder->add($unit);
        }

        return $builder;
    }

    /**
     * Map an additional lookup name to an existing unit name (catalog alias record).
     *
     * The target may be a custom define/add name or a name from UDUNITS2 once the
     * registry is composed.
     */
    public function alias(string $name, string $target): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Alias name must not be empty.');
        }

        if ($target === '') {
            throw new \InvalidArgumentException('Alias target must not be empty.');
        }

        $clone = clone $this;
        $clone->assertNameAvailable($name);
        $clone->records[$name] = [
            'type' => 'alias',
            'name' => $name,
            'def' => $target,
        ];

        return $clone;
    }

    public function build(): UnitRegistry
    {
        $overlay = $this->buildOverlayRegistry();

        if ($this->includeUdunits2) {
            $base = new Udunits2UnitRegistry(
                $this->udunits2DataFile ?? Udunits2UnitRegistry::DATA_FILE,
            );

            if ($overlay === null) {
                return $base;
            }

            return new CompositeUnitRegistry($base, $overlay);
        }

        return $overlay ?? new UnitRegistry();
    }

    private function buildOverlayRegistry(): ?UnitRegistry
    {
        /** @var array<string, Unit> $map */
        $map = $this->units;

        /** @var array<string, CatalogRecord> $records */
        $records = $this->records;

        if ($map === [] && $records === []) {
            return null;
        }

        // Prebuilt aliases: if a record is an alias to a prebuilt unit name, also expose
        // lookup() for that alias so get() works without going through UnitResolver.
        foreach ($records as $name => $record) {
            if ($record['type'] !== 'alias') {
                continue;
            }

            $target = $record['def'] ?? null;
            if ($target === null || !isset($map[$target])) {
                continue;
            }

            $map[$name] = $map[$target];
            unset($records[$name]);
        }

        return new UnitRegistry($map, $records);
    }

    private function assertNameAvailable(string $name): void
    {
        if (isset($this->units[$name])) {
            throw new \InvalidArgumentException('Duplicate unit name in registry builder: ' . $name);
        }

        if (isset($this->records[$name])) {
            throw new \InvalidArgumentException('Duplicate unit or alias name in registry builder: ' . $name);
        }
    }

    /**
     * @return array{string, string} name, expression
     */
    private static function parseAssignment(string $definition): array
    {
        $definition = trim($definition);

        if ($definition === '') {
            throw new \InvalidArgumentException('Unit definition must not be empty.');
        }

        if (preg_match('/^(\S+)\s*=\s*(.+)$/s', $definition, $matches) !== 1) {
            throw new \InvalidArgumentException(
                'Unit definition must look like "name = expression", got: ' . $definition,
            );
        }

        $name = $matches[1];
        $expression = trim($matches[2]);

        if ($expression === '') {
            throw new \InvalidArgumentException('Unit definition expression must not be empty: ' . $definition);
        }

        return [$name, $expression];
    }
}
