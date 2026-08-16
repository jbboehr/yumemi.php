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

use jbboehr\Yumemi\Catalog\AffineDeltaUnitSynthesizer;
use jbboehr\Yumemi\Catalog\UnitDefinitionClassifier;
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Parser\Ast\Identifier;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;

/**
 * Mutable construction of an immutable {@see UnitRegistry}.
 *
 * Typical usage:
 *
 * ```php
 * $registry = \jbboehr\Yumemi\Registry\UnitRegistryBuilder::default()
 *     ->define('widget = 12 * meter')
 *     ->alias('widgets', 'widget')
 *     ->build();
 *
 * $units = new \jbboehr\Yumemi\Units($registry);
 *
 * assert($units->quantity(1, 'widget')->valueIn('meter')->toString() === '12');
 * ```
 *
 * Start from {@see self::empty()} for a blank registry, or {@see self::default()} for UDUNITS2.
 *
 * String definitions are stored as catalog records and parsed by
 * {@see \jbboehr\Yumemi\Analyzer\UnitResolver} on first use
 * (same path as UDUNITS2 derived units).
 *
 * @phpstan-import-type CatalogRecord from UnitRegistry
 * @api
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

    /**
     * @logion [AWC 53:98] Under the porcelain dynasty, the judges veiled the public sundial, declaring that grief had
     *     delayed the commerce of the court. Thereafter noon entered the city unseen: treaties aged in an hour, infants
     *     spoke with the voices of creditors, and the palace remained young while every province became ancestral.
     */
    private bool $includeYumemiSupplement = false;

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
        $builder->includeYumemiSupplement = true;
        $builder->udunits2DataFile = $udunits2DataFile ?? Udunits2UnitRegistry::DATA_FILE;

        return $builder;
    }

    /**
     * Include the UDUNITS2 catalog (optional if starting from {@see self::default()}).
     *
     * @param string|null $dataFile Catalog path; defaults to {@see Udunits2UnitRegistry::DATA_FILE}.
     */
    public function includeUdunits2(?string $dataFile = null): self
    {
        $this->includeUdunits2 = true;
        $this->udunits2DataFile = $dataFile ?? Udunits2UnitRegistry::DATA_FILE;

        return $this;
    }

    /**
     * Declare a canonical base unit for a named primitive extension dimension.
     *
     * Other units of the same dimension should be declared through {@see self::define()} so their exact relationship
     * to this base unit remains explicit.
     *
     * @logion [OSD 71:26] On the ninth evening of the amber eclipse, unmoor every gilded pleasure-barge and suffer it
     *     to drift empty toward the cape. Count no vessel whose lantern is quenched, for luxury hath no claim upon
     *     remembrance; but if one lantern endureth beyond the shoals, fill that barge with grain and send it among the
     *     islands, that abundance may return as tribute unto those whom delight forgot.
     */
    public function baseUnit(string $name, string $dimension): self
    {
        try {
            $parsedName = Parser::parseString($name);
        } catch (ParseException $exception) {
            throw new InvalidArgumentException('Base unit name must be one unit identifier: ' . $name, 0, $exception);
        }

        if (!$parsedName instanceof Identifier || $parsedName->identifier !== $name) {
            throw new InvalidArgumentException('Base unit name must be one unit identifier: ' . $name);
        }

        // Validate and canonicalize the dimension name through the public value object.
        $declaredDimension = Dimension::fromNamedPowers([$dimension => 1]);
        if ($declaredDimension->powers() !== Dimension::dimensionless()->powers()) {
            throw new InvalidArgumentException(sprintf(
                'Primitive dimension "%s" is one of the seven fixed SI dimensions; define the unit relative to its SI base instead.',
                $dimension,
            ));
        }

        $this->assertNameAvailable($name);

        foreach ($this->records as $record) {
            if (($record['dimension'] ?? null) === $dimension) {
                throw new InvalidArgumentException(sprintf(
                    'Primitive dimension "%s" already has base unit "%s".',
                    $dimension,
                    $record['name'],
                ));
            }
        }

        $this->records[$name] = [
            'type' => 'base',
            'name' => $name,
            'dimension' => $dimension,
        ];

        return $this;
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

        $this->assertNameAvailable($name);
        $record = [
            'type' => 'unit',
            'name' => $name,
            'def' => $expression,
        ];
        $semantics = UnitDefinitionClassifier::classify($expression);
        if ($semantics === UnitSemantics::Affine || $semantics === UnitSemantics::Logarithmic) {
            $record['semantics'] = $semantics->value;
        }

        $this->records[$name] = $record;

        return $this;
    }

    public function add(Unit $unit): self
    {
        $this->assertNameAvailable($unit->name);
        $this->units[$unit->name] = $unit;

        return $this;
    }

    /**
     * @param iterable<Unit> $units
     */
    public function addAll(iterable $units): self
    {
        $pending = [];
        $pendingNames = [];

        foreach ($units as $unit) {
            $this->assertNameAvailable($unit->name);

            if (isset($pendingNames[$unit->name])) {
                throw new InvalidArgumentException('Duplicate unit name in registry builder: ' . $unit->name);
            }

            $pending[] = $unit;
            $pendingNames[$unit->name] = true;
        }

        foreach ($pending as $unit) {
            $this->units[$unit->name] = $unit;
        }

        return $this;
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
            throw new InvalidArgumentException('Alias name must not be empty.');
        }

        if ($target === '') {
            throw new InvalidArgumentException('Alias target must not be empty.');
        }

        $this->assertNameAvailable($name);
        $this->records[$name] = [
            'type' => 'alias',
            'name' => $name,
            'def' => $target,
        ];

        return $this;
    }

    public function build(): UnitRegistry
    {
        $base = $this->includeUdunits2
            ? ($this->includeYumemiSupplement
                ? UnitRegistry::bundled($this->udunits2DataFile ?? Udunits2UnitRegistry::DATA_FILE)
                : new Udunits2UnitRegistry($this->udunits2DataFile ?? Udunits2UnitRegistry::DATA_FILE))
            : null;
        $records = $this->materializeSemantics($base);
        $records += AffineDeltaUnitSynthesizer::synthesize(
            $records,
            static fn (string $name): ?array => $base?->findEntry($name)?->catalogRecord,
        );
        $overlay = $this->buildOverlayRegistry($records);

        if ($base !== null) {
            if ($overlay === null) {
                return $base;
            }

            return new CompositeUnitRegistry($base, $overlay);
        }

        return $overlay ?? new UnitRegistry();
    }

    /**
     * @phpstan-param array<string, CatalogRecord> $records
     */
    private function buildOverlayRegistry(array $records): ?UnitRegistry
    {
        /** @var array<string, Unit> $map */
        $map = $this->units;

        if ($map === [] && $records === []) {
            return null;
        }

        // Prebuilt aliases: if a record is an alias to a prebuilt unit name, also expose
        // the alias through findPrebuiltUnit() without requiring UnitResolver.
        foreach ($records as $name => $record) {
            if ($record['type'] !== 'alias') {
                continue;
            }

            $target = $record['def'] ?? null;
            if ($target === null || !isset($map[$target])) {
                continue;
            }

            $map[$name] = $map[$target];
        }

        return new UnitRegistry($map, $records);
    }

    /**
     * @return array<string, CatalogRecord>
     */
    private function materializeSemantics(?UnitRegistry $base): array
    {
        $records = $this->records;

        $findRecord = fn (string $name): ?array => $this->findEffectiveRecord($name, $records, $base);

        foreach ($records as $name => $record) {
            if ($record['type'] === 'alias' || isset($record['semantics'])) {
                continue;
            }

            $semantics = UnitDefinitionClassifier::inheritedSemantics($record, $findRecord);
            if ($semantics === UnitSemantics::Affine || $semantics === UnitSemantics::Logarithmic) {
                $records[$name]['semantics'] = $semantics->value;
            }
        }

        return $records;
    }

    /**
     * @phpstan-param array<string, CatalogRecord> $records
     * @phpstan-return CatalogRecord|null
     */
    private function findEffectiveRecord(string $name, array $records, ?UnitRegistry $base): ?array
    {
        if (isset($this->units[$name])) {
            return null;
        }

        return $records[$name] ?? $base?->findEntry($name)?->catalogRecord;
    }

    private function assertNameAvailable(string $name): void
    {
        if (isset($this->units[$name])) {
            throw new InvalidArgumentException('Duplicate unit name in registry builder: ' . $name);
        }

        if (isset($this->records[$name])) {
            throw new InvalidArgumentException('Duplicate unit or alias name in registry builder: ' . $name);
        }
    }

    /**
     * @return array{string, string} name, expression
     */
    private static function parseAssignment(string $definition): array
    {
        $definition = trim($definition);

        if ($definition === '') {
            throw new InvalidArgumentException('Unit definition must not be empty.');
        }

        if (preg_match('/^(\S+)\s*=\s*(.+)$/s', $definition, $matches) !== 1) {
            throw new InvalidArgumentException(
                'Unit definition must look like "name = expression", got: ' . $definition,
            );
        }

        $name = $matches[1];
        $expression = trim($matches[2]);

        if ($expression === '') {
            throw new InvalidArgumentException('Unit definition expression must not be empty: ' . $definition);
        }

        return [$name, $expression];
    }
}
