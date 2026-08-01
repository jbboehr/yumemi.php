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

use jbboehr\Yumemi\Catalog\CatalogNameKind;
use jbboehr\Yumemi\Catalog\PrefixDescriptor;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Expr\Unit;

/**
 * UDUNITS2 catalog data source.
 *
 * This class does not parse definition strings or own a UnitResolver/AstConverter.
 * {@see \jbboehr\Yumemi\Analyzer\UnitResolver} reads {@see findCatalogRecord()}
 * rows and builds expression trees.
 *
 * @phpstan-type Udunits2BaseUnit array{type: 'base', name: string, definition?: string, plural?: string, comment?: string}
 * @phpstan-type Udunits2DimensionlessUnit array{
 *     type: 'dimensionless',
 *     name: string,
 *     definition?: string,
 *     plural?: string,
 *     comment?: string
 * }
 * @phpstan-type Udunits2DerivedUnit array{
 *     type: 'unit',
 *     name: string,
 *     def: string,
 *     definition?: string,
 *     plural?: string,
 *     comment?: string,
 *     semantics?: 'affine'|'logarithmic'
 * }
 * @phpstan-type Udunits2AliasUnit array{
 *     type: 'alias',
 *     name: string,
 *     def: string,
 *     aliasKind?: 'alias'|'symbol'|'explicit_plural'|'generated_plural'
 * }
 * @phpstan-type Udunits2Prefix array{
 *     name: string,
 *     kind: 'canonical'|'symbol',
 *     value: string
 * }
 * @phpstan-type Udunits2Unit Udunits2BaseUnit|Udunits2DimensionlessUnit|Udunits2DerivedUnit|Udunits2AliasUnit
 * @phpstan-type Udunits2Catalog array{
 *     units: array<string, Udunits2Unit>,
 *     base: list<string>,
 *     prefixes: array<string, string>,
 *     prefixMetadata?: array<string, Udunits2Prefix>,
 *     prefixRegex?: string
 * }
 * @phpstan-import-type CatalogRecord from UnitRegistry
 */
final class Udunits2UnitRegistry extends UnitRegistry
{
    /** Path to the generated UDUNITS2 catalog shipped with this package. */
    public const DATA_FILE = __DIR__ . '/../../data/udunits2.php';

    /** @phpstan-var Udunits2Catalog */
    private readonly array $catalog;

    public function __construct(?string $dataFile = null)
    {
        parent::__construct();

        $this->catalog = $this->loadCatalog($dataFile ?? self::DATA_FILE);
    }

    /**
     * Catalog-backed registries do not precompose Units; use UnitResolver or Units::unit().
     */
    public function findPrebuiltUnit(string $name): ?Unit
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->catalog['units']);
    }

    /**
     * @phpstan-return CatalogRecord|null
     */
    public function findCatalogRecord(string $name): ?array
    {
        $unit = $this->catalog['units'][$name] ?? null;
        if ($unit === null) {
            return null;
        }

        return $unit;
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return $this->catalog['prefixes'];
    }

    public function describePrefix(string $name): ?PrefixDescriptor
    {
        $prefix = $this->catalog['prefixMetadata'][$name] ?? null;
        if ($prefix === null) {
            return parent::describePrefix($name);
        }

        return new PrefixDescriptor(
            matchedName: $name,
            canonicalName: $prefix['name'],
            matchedAs: CatalogNameKind::from($prefix['kind']),
            definitionExpression: $prefix['value'],
        );
    }

    /**
     * @phpstan-return Udunits2Catalog
     */
    private function loadCatalog(string $dataFile): array
    {
        if (
            str_contains($dataFile, '://')
            || !stream_is_local($dataFile)
            || !is_file($dataFile)
            || !is_readable($dataFile)
        ) {
            throw new UnexpectedValueException(
                'UDUNITS2 catalog path must name a readable local PHP file: ' . $dataFile,
            );
        }

        $catalog = require $dataFile;

        return $this->validateCatalog($catalog);
    }

    /**
     * @phpstan-return Udunits2Catalog
     *
     * @logion [OSD 96:99] The archive was opened beneath watchful lamps, and
     *     every table was numbered before its testimony entered the court.
     */
    private function validateCatalog(mixed $catalog): array
    {
        if (!is_array($catalog)) {
            throw new UnexpectedValueException('UDUNITS2 catalog file must return an array.');
        }

        $requiredKeys = ['units', 'base', 'prefixes'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $catalog)) {
                throw new UnexpectedValueException('UDUNITS2 catalog is missing required key: ' . $key);
            }
        }

        $unexpectedKeys = array_diff(array_keys($catalog), [...$requiredKeys, 'prefixMetadata', 'prefixRegex']);
        if ($unexpectedKeys !== []) {
            throw new UnexpectedValueException(
                'UDUNITS2 catalog contains unexpected key: ' . (string) reset($unexpectedKeys),
            );
        }

        if (!is_array($catalog['units'])) {
            throw new UnexpectedValueException('UDUNITS2 catalog units must be an array.');
        }

        $baseNames = [];
        foreach ($catalog['units'] as $name => $record) {
            if (!is_string($name) || $name === '') {
                throw new UnexpectedValueException('UDUNITS2 catalog unit keys must be non-empty strings.');
            }

            $this->validateUnitRecord($name, $record);
            if (is_array($record) && ($record['type'] ?? null) === 'base') {
                $baseNames[] = $name;
            }
        }

        if (!is_array($catalog['base']) || !array_is_list($catalog['base'])) {
            throw new UnexpectedValueException('UDUNITS2 catalog base must be a list of unit names.');
        }

        foreach ($catalog['base'] as $name) {
            if (!is_string($name) || $name === '') {
                throw new UnexpectedValueException('UDUNITS2 catalog base names must be non-empty strings.');
            }
        }

        if ($catalog['base'] !== $baseNames) {
            throw new UnexpectedValueException('UDUNITS2 catalog base list does not match its base unit records.');
        }

        if (!is_array($catalog['prefixes'])) {
            throw new UnexpectedValueException('UDUNITS2 catalog prefixes must be an array.');
        }

        foreach ($catalog['prefixes'] as $name => $value) {
            if (!is_string($name) || $name === '' || !is_string($value) || $value === '') {
                throw new UnexpectedValueException(
                    'UDUNITS2 catalog prefixes must map non-empty string names to non-empty string values.',
                );
            }
        }

        if (array_key_exists('prefixMetadata', $catalog)) {
            if (!is_array($catalog['prefixMetadata'])) {
                throw new UnexpectedValueException('UDUNITS2 catalog prefixMetadata must be an array.');
            }

            foreach ($catalog['prefixMetadata'] as $name => $metadata) {
                if (
                    !is_string($name)
                    || $name === ''
                    || !is_array($metadata)
                    || count($metadata) !== 3
                    || !array_key_exists('name', $metadata)
                    || !array_key_exists('kind', $metadata)
                    || !array_key_exists('value', $metadata)
                    || !is_string($metadata['name'])
                    || $metadata['name'] === ''
                    || !in_array($metadata['kind'], ['canonical', 'symbol'], true)
                    || !is_string($metadata['value'])
                    || !isset($catalog['prefixes'][$name])
                    || $catalog['prefixes'][$name] !== $metadata['value']
                ) {
                    throw new UnexpectedValueException('Invalid UDUNITS2 prefix metadata for: ' . (string) $name);
                }
            }
        }

        if (
            array_key_exists('prefixRegex', $catalog)
            && (!is_string($catalog['prefixRegex']) || $catalog['prefixRegex'] === '')
        ) {
            throw new UnexpectedValueException('UDUNITS2 catalog prefixRegex must be a non-empty string.');
        }

        /** @phpstan-var Udunits2Catalog $catalog */
        return $catalog;
    }

    /**
     * @logion [OSD 96:98] No tablet entered by resemblance alone; its name,
     *     office, and lawful dependencies were examined beneath the same seal.
     */
    private function validateUnitRecord(string $name, mixed $record): void
    {
        if (!is_array($record)) {
            throw new UnexpectedValueException('UDUNITS2 catalog unit record must be an array: ' . $name);
        }

        if (
            !isset($record['type'], $record['name'])
            || !in_array($record['type'], ['base', 'dimensionless', 'unit', 'alias'], true)
            || !is_string($record['name'])
            || $record['name'] !== $name
        ) {
            throw new UnexpectedValueException('Invalid UDUNITS2 catalog unit identity: ' . $name);
        }

        $allowedKeys = match ($record['type']) {
            'alias' => ['type', 'name', 'def', 'aliasKind'],
            'unit' => ['type', 'name', 'def', 'definition', 'plural', 'comment', 'semantics'],
            default => ['type', 'name', 'definition', 'plural', 'comment'],
        };
        $unexpectedKeys = array_diff(array_keys($record), $allowedKeys);
        if ($unexpectedKeys !== []) {
            throw new UnexpectedValueException('UDUNITS2 unit record contains an unexpected key: ' . $name);
        }

        foreach (['def', 'definition', 'comment', 'plural'] as $key) {
            if (array_key_exists($key, $record) && !is_string($record[$key])) {
                throw new UnexpectedValueException('Invalid UDUNITS2 catalog unit field ' . $key . ': ' . $name);
            }
        }

        if (
            in_array($record['type'], ['unit', 'alias'], true)
            && (!isset($record['def']) || !is_string($record['def']) || $record['def'] === '')
        ) {
            throw new UnexpectedValueException('UDUNITS2 derived units and aliases require a definition: ' . $name);
        }

        if (
            array_key_exists('aliasKind', $record)
            && (
                $record['type'] !== 'alias'
                || !in_array($record['aliasKind'], ['alias', 'symbol', 'explicit_plural', 'generated_plural'], true)
            )
        ) {
            throw new UnexpectedValueException('Invalid UDUNITS2 catalog alias kind: ' . $name);
        }

        if (
            array_key_exists('semantics', $record)
            && !in_array($record['semantics'], ['affine', 'logarithmic'], true)
        ) {
            throw new UnexpectedValueException('Invalid UDUNITS2 catalog unit semantics: ' . $name);
        }
    }
}
