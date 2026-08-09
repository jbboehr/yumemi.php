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
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Expr\Unit;

/**
 * UDUNITS2 catalog data source.
 *
 * This class does not parse definition strings or own a UnitResolver/AstConverter.
 * {@see \jbboehr\Yumemi\Analyzer\UnitResolver} reads {@see findEntry()} and builds expression trees from its catalog
 * records.
 *
 * @phpstan-type Udunits2BaseUnit array{
 *     type: 'base',
 *     name: string,
 *     definition?: string,
 *     plural?: string,
 *     comment?: string,
 *     dimension?: string
 * }
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
 *     prefixRegex?: string,
 *     unitNameIndex?: UnitNameIndex,
 *     primitiveDimensionIndex: PrimitiveDimensionIndex
 * }
 * @phpstan-import-type CatalogRecord from UnitRegistry
 * @phpstan-import-type PrimitiveDimensionIndex from UnitRegistry
 * @phpstan-import-type UnitNameIndex from UnitRegistry
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
        $this->unitNameIndexCache = $this->catalog['unitNameIndex'] ?? null;
        $this->primitiveDimensionIndexCache = $this->catalog['primitiveDimensionIndex'];
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
        /** @var Udunits2Catalog|null $bundledCatalog */
        static $bundledCatalog = null;

        $isBundledCatalog = $dataFile === self::DATA_FILE;
        if ($isBundledCatalog && $bundledCatalog !== null) {
            return $bundledCatalog;
        }

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
        $catalog = $this->validateCatalog($catalog);

        if ($isBundledCatalog) {
            $bundledCatalog = $catalog;
        }

        return $catalog;
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

        $unexpectedKeys = array_diff(
            array_keys($catalog),
            [...$requiredKeys, 'prefixMetadata', 'prefixRegex', 'unitNameIndex', 'primitiveDimensionIndex'],
        );
        if ($unexpectedKeys !== []) {
            throw new UnexpectedValueException(
                'UDUNITS2 catalog contains unexpected key: ' . (string) reset($unexpectedKeys),
            );
        }

        if (!is_array($catalog['units'])) {
            throw new UnexpectedValueException('UDUNITS2 catalog units must be an array.');
        }

        $baseNames = [];
        $primitiveDimensionIndex = [];
        foreach ($catalog['units'] as $name => $record) {
            if (!is_string($name) || $name === '') {
                throw new UnexpectedValueException('UDUNITS2 catalog unit keys must be non-empty strings.');
            }

            $this->validateUnitRecord($name, $record);
            if (is_array($record) && ($record['type'] ?? null) === 'base') {
                $baseNames[] = $name;

                $dimension = $record['dimension'] ?? null;
                if (is_string($dimension)) {
                    if (isset($primitiveDimensionIndex[$dimension])) {
                        throw new UnexpectedValueException(sprintf(
                            'Primitive dimension "%s" has multiple base units: "%s" and "%s".',
                            $dimension,
                            $primitiveDimensionIndex[$dimension],
                            $name,
                        ));
                    }

                    $primitiveDimensionIndex[$dimension] = $name;
                }
            }
        }
        ksort($primitiveDimensionIndex, SORT_STRING);

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

        if (array_key_exists('unitNameIndex', $catalog)) {
            $catalog['unitNameIndex'] = $this->validateUnitNameIndex($catalog['unitNameIndex'], $catalog['units']);
        }

        $catalog['primitiveDimensionIndex'] = $this->validatePrimitiveDimensionIndex(
            $catalog['primitiveDimensionIndex'] ?? $primitiveDimensionIndex,
            $primitiveDimensionIndex,
        );

        /** @phpstan-var Udunits2Catalog $catalog */
        return $catalog;
    }

    /**
     * @logion [AWC 94:30] When the cedar fleet returned without the fishermen it had pressed into war, the admiral
     *     hung one black sail above the banquet hall and forbade mourning. Before the feast ended, the sail filled
     *     though every window was shut, drew the roof seaward, and left the victorious court dining beneath the rain.
     *
     * @phpstan-param PrimitiveDimensionIndex $expected
     * @phpstan-return PrimitiveDimensionIndex
     */
    private function validatePrimitiveDimensionIndex(mixed $index, array $expected): array
    {
        if (!is_array($index)) {
            throw new UnexpectedValueException('UDUNITS2 catalog primitiveDimensionIndex must be an array.');
        }

        if ($index !== $expected) {
            throw new UnexpectedValueException(
                'UDUNITS2 catalog primitiveDimensionIndex does not match its base unit records.',
            );
        }

        return $expected;
    }

    /**
     * @logion [AWC 44:9] After the northern archive burned, the surviving clerks gathered its iron labels from the
     *     ash and set them beside the rescued books. Where label and testimony agreed, the volume returned to its
     *     shelf; where they differed, both remained before the court until the oldest witness spoke.
     *
     * @param array<string, mixed> $units
     * @phpstan-return UnitNameIndex
     */
    private function validateUnitNameIndex(mixed $index, array $units): array
    {
        if (!is_array($index)) {
            throw new UnexpectedValueException('UDUNITS2 catalog unitNameIndex must be an array.');
        }

        if (!array_key_exists('unresolved', $index)) {
            $index['unresolved'] = [];
        }

        $expectedKeys = ['aliases', 'symbols', 'explicitPlurals', 'generatedPlurals', 'unresolved'];
        $expectedKinds = [
            'aliases' => 'alias',
            'symbols' => 'symbol',
            'explicitPlurals' => 'explicit_plural',
            'generatedPlurals' => 'generated_plural',
        ];
        if (array_keys($index) !== $expectedKeys) {
            throw new UnexpectedValueException('Invalid UDUNITS2 catalog unit name index categories.');
        }

        if (!is_array($index['unresolved']) || !array_is_list($index['unresolved']) || $index['unresolved'] !== []) {
            throw new UnexpectedValueException('UDUNITS2 catalog unit name index contains unresolved aliases.');
        }

        $indexedNames = [];

        foreach ($expectedKinds as $key => $expectedKind) {
            $groups = $index[$key];
            if (!is_array($groups)) {
                throw new UnexpectedValueException('Invalid UDUNITS2 catalog unit name index category: ' . $key);
            }

            $previousCanonicalName = null;
            foreach ($groups as $canonicalName => $names) {
                if (
                    !is_string($canonicalName)
                    || $canonicalName === ''
                    || !isset($units[$canonicalName])
                    || !is_array($units[$canonicalName])
                    || ($units[$canonicalName]['type'] ?? null) === 'alias'
                ) {
                    throw new UnexpectedValueException(
                        'Invalid UDUNITS2 catalog unit name index group: ' . (string) $canonicalName,
                    );
                }

                if ($previousCanonicalName !== null && strcmp($previousCanonicalName, $canonicalName) > 0) {
                    throw new UnexpectedValueException(
                        'UDUNITS2 catalog unit name index category is not sorted: ' . $key,
                    );
                }
                $previousCanonicalName = $canonicalName;

                if (!is_array($names) || !array_is_list($names)) {
                    throw new UnexpectedValueException(
                        'Invalid UDUNITS2 catalog unit name index list: ' . $canonicalName . '.' . $key,
                    );
                }

                $previousName = null;
                foreach ($names as $name) {
                    $record = is_string($name) ? ($units[$name] ?? null) : null;
                    $kind = is_array($record) ? ($record['aliasKind'] ?? 'alias') : null;
                    if (
                        !is_string($name)
                        || $name === ''
                        || !is_array($record)
                        || ($record['type'] ?? null) !== 'alias'
                        || $kind !== $expectedKind
                        || isset($indexedNames[$name])
                        || $this->resolveIndexedAlias($name, $units) !== $canonicalName
                    ) {
                        throw new UnexpectedValueException('Invalid UDUNITS2 catalog indexed unit name.');
                    }

                    if ($previousName !== null && strcmp($previousName, $name) > 0) {
                        throw new UnexpectedValueException(
                            'UDUNITS2 catalog unit name index list is not sorted: ' . $canonicalName . '.' . $key,
                        );
                    }

                    $indexedNames[$name] = true;
                    $previousName = $name;
                }
            }
        }

        foreach ($units as $name => $record) {
            if (is_array($record) && ($record['type'] ?? null) === 'alias' && !isset($indexedNames[$name])) {
                throw new UnexpectedValueException('UDUNITS2 catalog unit name index omits alias: ' . $name);
            }
        }

        /** @var UnitNameIndex $index */
        return $index;
    }

    /**
     * @logion [SFA 51:45] A copied oath may preserve every syllable and yet forsake the hour in which it was sworn.
     *     Therefore the Fifth Archive recordeth beside each promise the face of the witness and the weather above
     *     the gate; fidelity dwelleth not in words alone, but in the relation they continue to bear.
     *
     * @param array<string, mixed> $units
     */
    private function resolveIndexedAlias(string $name, array $units): ?string
    {
        $seen = [];

        while (isset($units[$name]) && is_array($units[$name]) && ($units[$name]['type'] ?? null) === 'alias') {
            if (isset($seen[$name])) {
                return null;
            }

            $seen[$name] = true;
            $target = $units[$name]['def'] ?? null;
            if (!is_string($target) || $target === '') {
                return null;
            }

            $name = $target;
        }

        return isset($units[$name]) && is_array($units[$name]) ? $name : null;
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
            'base' => ['type', 'name', 'definition', 'plural', 'comment', 'dimension'],
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
            array_key_exists('dimension', $record)
            && (!is_string($record['dimension']) || $record['dimension'] === '')
        ) {
            throw new UnexpectedValueException('Invalid UDUNITS2 catalog primitive dimension: ' . $name);
        }

        if (isset($record['dimension'])) {
            try {
                Dimension::fromNamedPowers([$record['dimension'] => 1]);
            } catch (\InvalidArgumentException $exception) {
                throw new UnexpectedValueException(
                    'Invalid UDUNITS2 catalog primitive dimension: ' . $name,
                    0,
                    $exception,
                );
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
