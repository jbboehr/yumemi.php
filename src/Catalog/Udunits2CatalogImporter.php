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

namespace jbboehr\Yumemi\Catalog;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * @phpstan-import-type Udunits2Catalog from \jbboehr\Yumemi\Registry\Udunits2UnitRegistry
 * @phpstan-type MutableUdunits2Catalog array{
 *     units: array<string, array<string, mixed>>,
 *     base: list<string>,
 *     prefixes: array<string, string>,
 *     prefixMetadata: array<string, array{name: string, kind: 'canonical'|'symbol', value: string}>,
 *     prefixRegex?: string
 * }
 * @phpstan-type Udunits2Name array{singular: string, plural: string|null, pluralizable: bool}
 * @phpstan-type Udunits2Aliases array{names: list<Udunits2Name>, symbols: list<string>}
 * @phpstan-type ImplicitPluralTargets array<string, string|null>
 */
final class Udunits2CatalogImporter
{
    /**
     * @param list<string> $files
     * @phpstan-return Udunits2Catalog
     */
    public function importFiles(array $files): array
    {
        if ($files === []) {
            throw new \InvalidArgumentException('At least one UDUNITS2 XML file is required.');
        }

        /** @phpstan-var MutableUdunits2Catalog $catalog */
        $catalog = [
            'units' => [],
            'base' => [],
            'prefixes' => [],
            'prefixMetadata' => [],
        ];

        /** @var ImplicitPluralTargets $implicitPluralTargets */
        $implicitPluralTargets = [];

        foreach ($files as $file) {
            $this->importFile($catalog, $file, $implicitPluralTargets);
        }

        $this->materializeImplicitPluralAliases($catalog, $implicitPluralTargets);
        $this->materializeSemantics($catalog);
        $catalog['prefixRegex'] = $this->createPrefixRegex(array_keys($catalog['prefixes']));

        /** @phpstan-var Udunits2Catalog $catalog */
        return $catalog;
    }

    /**
     * @phpstan-param MutableUdunits2Catalog $catalog
     * @phpstan-param ImplicitPluralTargets $implicitPluralTargets
     */
    private function importFile(array &$catalog, string $file, array &$implicitPluralTargets): void
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new \RuntimeException('Could not read UDUNITS2 XML file: ' . $file);
        }

        $document = new DOMDocument();
        if (!$document->loadXML($contents)) {
            throw new \RuntimeException('Could not parse UDUNITS2 XML file: ' . $file);
        }

        $xpath = new DOMXPath($document);

        $unitNodes = $xpath->query('/unit-system/unit');
        if ($unitNodes !== false) {
            foreach ($unitNodes as $node) {
                if ($node instanceof DOMElement) {
                    $this->importUnit($catalog, $node, $implicitPluralTargets);
                }
            }
        }

        $prefixNodes = $xpath->query('/unit-system/prefix');
        if ($prefixNodes !== false) {
            foreach ($prefixNodes as $node) {
                if ($node instanceof DOMElement) {
                    $this->importPrefix($catalog, $node);
                }
            }
        }
    }

    /**
     * @phpstan-param MutableUdunits2Catalog $catalog
     * @phpstan-param ImplicitPluralTargets $implicitPluralTargets
     */
    private function importUnit(array &$catalog, DOMElement $node, array &$implicitPluralTargets): void
    {
        $base = false;
        /** @var Udunits2Name|null $name */
        $name = null;
        /** @var list<string> $symbols */
        $symbols = [];
        /** @var Udunits2Aliases $aliases */
        $aliases = ['names' => [], 'symbols' => []];
        $definition = null;
        $dimensionless = false;
        $def = null;
        $comment = null;

        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            match ($childNode->tagName) {
                'aliases' => $aliases = array_merge($aliases, $this->readAliases($childNode)),
                'base' => $base = true,
                'comment' => $comment = trim($childNode->textContent),
                'def' => $def = trim($childNode->textContent),
                'definition' => $definition = trim($childNode->textContent),
                'dimensionless' => $dimensionless = true,
                'name' => $name = $this->readName($childNode),
                'symbol' => $symbols[] = trim($childNode->textContent),
                default => throw new \RuntimeException('Unhandled UDUNITS2 unit tag: ' . $childNode->tagName),
            };
        }

        if ($name === null && $aliases['names'] !== []) {
            $name = array_shift($aliases['names']);
        }

        // If name is still null, aliases were empty (see above), so only symbol can identify the unit.
        if ($name === null && $symbols === [] && $aliases['symbols'] === []) {
            return;
        }

        $def = $def === null ? null : $this->normalizeDefinition($def);
        $type = 'unit';
        // Guaranteed by the early return above: at least one of name/symbol is non-null.
        $unit = [
            'name' => $name['singular'] ?? $symbols[0] ?? $aliases['symbols'][0],
        ];

        if ($definition !== null) {
            $unit['definition'] = $definition;
        }

        if ($base) {
            $catalog['base'][] = $unit['name'];
            $type = 'base';
        }

        if ($dimensionless) {
            $type = 'dimensionless';
        }

        if ($name !== null && $name['plural'] !== null) {
            $unit['plural'] = $name['plural'];
        }

        if ($def !== null) {
            $unit['def'] = $def;

            $semantics = UnitDefinitionClassifier::classify($def);
            if ($semantics !== UnitSemantics::Multiplicative) {
                $unit['semantics'] = $semantics->value;
            }
        }

        if ($comment !== null) {
            $unit['comment'] = $comment;
        }

        foreach ([...$symbols, ...$aliases['symbols']] as $symbol) {
            if ($symbol === $unit['name']) {
                continue;
            }

            $this->addAlias($catalog, $symbol, $unit['name'], 'symbol');

            if ($symbol === '′') {
                $this->addAlias($catalog, '\'', $unit['name'], 'symbol');
            }
        }

        foreach ($aliases['names'] as $alias) {
            $this->addAlias($catalog, $alias['singular'], $unit['name'], 'alias');
            $this->registerPluralAlias($catalog, $alias, $unit['name'], $implicitPluralTargets);
        }

        if (isset($catalog['units'][$unit['name']])) {
            throw new \RuntimeException('Already registered UDUNITS2 unit name: ' . $unit['name']);
        }

        if ($type === 'unit') {
            $catalog['units'][$unit['name']] = ['type' => 'unit'] + $unit;
        } elseif ($type === 'base') {
            $catalog['units'][$unit['name']] = ['type' => 'base'] + $unit;
        } else {
            $catalog['units'][$unit['name']] = ['type' => 'dimensionless'] + $unit;
        }

        if ($name !== null) {
            $this->registerPluralAlias($catalog, $name, $unit['name'], $implicitPluralTargets);
        }
    }

    /**
     * @phpstan-return Udunits2Name
     */
    private function readName(DOMElement $node): array
    {
        $singular = null;
        $plural = null;
        $pluralizable = true;

        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childNode->tagName === 'singular') {
                $singular = trim($childNode->textContent);
            } elseif ($childNode->tagName === 'plural') {
                $plural = trim($childNode->textContent);
            } elseif ($childNode->tagName === 'noplural') {
                $pluralizable = false;
            } else {
                throw new \RuntimeException('Unhandled UDUNITS2 name tag: ' . $childNode->tagName);
            }
        }

        if ($singular === null || $singular === '') {
            throw new \RuntimeException('UDUNITS2 name must contain a non-empty singular form.');
        }

        return [
            'singular' => $singular,
            'plural' => $plural,
            'pluralizable' => $pluralizable,
        ];
    }

    /**
     * @phpstan-return Udunits2Aliases
     */
    private function readAliases(DOMElement $node): array
    {
        /** @var list<Udunits2Name> $names */
        $names = [];
        $symbols = [];
        $directSingular = null;
        $directPlural = null;
        $pluralizable = true;

        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            match ($childNode->tagName) {
                'name' => $names[] = $this->readName($childNode),
                'noplural' => $pluralizable = false,
                'plural' => $directPlural = trim($childNode->textContent),
                'singular' => $directSingular = trim($childNode->textContent),
                'symbol' => $symbols[] = trim($childNode->textContent),
                default => throw new \RuntimeException('Unhandled UDUNITS2 alias tag: ' . $childNode->tagName),
            };
        }

        if ($directSingular !== null) {
            $names[] = [
                'singular' => $directSingular,
                'plural' => $directPlural,
                'pluralizable' => $pluralizable,
            ];
        }

        if (!$pluralizable) {
            foreach ($names as $key => $name) {
                $names[$key]['pluralizable'] = false;
            }
        }

        return ['names' => $names, 'symbols' => $symbols];
    }

    /**
     * @phpstan-param MutableUdunits2Catalog $catalog
     * @phpstan-param Udunits2Name $name
     * @phpstan-param ImplicitPluralTargets $implicitPluralTargets
     */
    private function registerPluralAlias(
        array &$catalog,
        array $name,
        string $target,
        array &$implicitPluralTargets,
    ): void {
        if ($name['plural'] !== null) {
            if (!isset($catalog['units'][$name['plural']])) {
                $this->addAlias($catalog, $name['plural'], $target, 'explicit_plural');
            }

            return;
        }

        if (!$name['pluralizable'] || !self::isPluralizableName($name['singular'])) {
            return;
        }

        $plural = self::pluralForm($name['singular']);
        if (!array_key_exists($plural, $implicitPluralTargets)) {
            $implicitPluralTargets[$plural] = $target;
        } elseif ($implicitPluralTargets[$plural] !== $target) {
            $implicitPluralTargets[$plural] = null;
        }
    }

    /**
     * @phpstan-param MutableUdunits2Catalog $catalog
     * @phpstan-param ImplicitPluralTargets $implicitPluralTargets
     */
    private function materializeImplicitPluralAliases(array &$catalog, array $implicitPluralTargets): void
    {
        foreach ($implicitPluralTargets as $plural => $target) {
            if ($target === null || isset($catalog['units'][$plural])) {
                continue;
            }

            $this->addAlias($catalog, $plural, $target, 'generated_plural');
        }
    }

    /**
     * Propagate semantics through exact-name synonym definitions such as
     * celsius = degree_Celsius. Alias rows inherit semantics during introspection.
     *
     * @phpstan-param MutableUdunits2Catalog $catalog
     */
    private function materializeSemantics(array &$catalog): void
    {
        foreach ($catalog['units'] as $name => $unit) {
            if ($unit['type'] === 'alias' || isset($unit['semantics'])) {
                continue;
            }

            $semantics = UnitDefinitionClassifier::inheritedSemantics(
                $unit,
                static fn (string $target): ?array => $catalog['units'][$target] ?? null,
            );
            if ($semantics !== UnitSemantics::Multiplicative) {
                $catalog['units'][$name]['semantics'] = $semantics->value;
            }
        }
    }

    private static function isPluralizableName(string $name): bool
    {
        return strlen($name) >= 3
            && preg_match('/^[a-z][a-z0-9_]*$/', $name) === 1;
    }

    private static function pluralForm(string $name): string
    {
        if (str_ends_with($name, 'y') && strlen($name) >= 2) {
            $previous = $name[strlen($name) - 2];
            if (!str_contains('aeiou', $previous)) {
                return substr($name, 0, -1) . 'ies';
            }
        }

        if (
            str_ends_with($name, 's')
            || str_ends_with($name, 'x')
            || str_ends_with($name, 'z')
            || str_ends_with($name, 'ch')
            || str_ends_with($name, 'sh')
        ) {
            return $name . 'es';
        }

        return $name . 's';
    }

    /**
     * @phpstan-param MutableUdunits2Catalog $catalog
     */
    private function importPrefix(array &$catalog, DOMElement $node): void
    {
        $name = null;
        $symbol = null;
        $value = null;

        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            match ($childNode->tagName) {
                'name' => $name = trim($childNode->textContent),
                'symbol' => $symbol = trim($childNode->textContent),
                'value' => $value = trim($childNode->textContent),
                default => null,
            };
        }

        if ($value === null) {
            return;
        }

        if (str_starts_with($value, '.')) {
            $value = '0' . $value;
        }

        $canonicalName = $name ?? $symbol;
        if ($canonicalName === null) {
            return;
        }

        if ($name !== null) {
            $catalog['prefixes'][$name] = $value;
            $catalog['prefixMetadata'][$name] = [
                'name' => $canonicalName,
                'kind' => 'canonical',
                'value' => $value,
            ];
        }

        if ($symbol !== null) {
            $catalog['prefixes'][$symbol] = $value;
            $catalog['prefixMetadata'][$symbol] = [
                'name' => $canonicalName,
                'kind' => $symbol === $canonicalName ? 'canonical' : 'symbol',
                'value' => $value,
            ];
        }
    }

    /**
     * @phpstan-param MutableUdunits2Catalog $catalog
     * @param 'alias'|'symbol'|'explicit_plural'|'generated_plural' $kind
     */
    private function addAlias(array &$catalog, string $alias, string $name, string $kind): void
    {
        $catalog['units'][$alias] = [
            'type' => 'alias',
            'name' => $alias,
            'def' => $name,
            'aliasKind' => $kind,
        ];
    }

    private function normalizeDefinition(string $definition): string
    {
        return str_replace('cm2', 'cm ^ 2', $definition);
    }

    /**
     * @param list<string> $prefixes
     */
    private function createPrefixRegex(array $prefixes): string
    {
        return '~^((?:' . implode(')|(?:', array_map(static fn (string $prefix): string => preg_quote($prefix, '~'), $prefixes)) . '))~';
    }
}
