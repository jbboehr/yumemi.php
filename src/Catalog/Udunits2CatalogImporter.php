<?php

namespace jbboehr\IudexMensurarumMysteriorum\Catalog;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * @phpstan-import-type Udunits2Catalog from \jbboehr\IudexMensurarumMysteriorum\Registry\Udunits2UnitRegistry
 * @phpstan-type MutableUdunits2Catalog array{
 *     units: array<string, array<string, mixed>>,
 *     base: list<string>,
 *     prefixes: array<string, string>,
 *     prefixRegex?: string
 * }
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
        ];

        foreach ($files as $file) {
            $this->importFile($catalog, $file);
        }

        $catalog['prefixRegex'] = $this->createPrefixRegex(array_keys($catalog['prefixes']));

        /** @phpstan-var Udunits2Catalog $catalog */
        return $catalog;
    }

    /**
     * @phpstan-param MutableUdunits2Catalog $catalog
     */
    private function importFile(array &$catalog, string $file): void
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
                    $this->importUnit($catalog, $node);
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
     */
    private function importUnit(array &$catalog, DOMElement $node): void
    {
        $base = false;
        $name = null;
        $symbol = null;
        $aliases = [];
        $definition = null;
        $dimensionless = false;
        $def = null;
        $plural = null;
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
                'name' => [$name, $plural] = $this->readName($childNode, $name, $plural),
                'symbol' => $symbol = trim($childNode->textContent),
                default => throw new \RuntimeException('Unhandled UDUNITS2 unit tag: ' . $childNode->tagName),
            };
        }

        if ($name === null && $aliases !== []) {
            $name = array_shift($aliases);
        }

        if ($name === null && $symbol === null && $aliases === []) {
            return;
        }

        if ($def !== null && str_contains($def, 'lg(')) {
            return;
        }

        $def = $def === null ? null : $this->normalizeDefinition($def);
        $type = 'unit';
        $unit = [
            'name' => $name ?? $symbol ?? throw new \LogicException('UDUNITS2 unit name was not resolved.'),
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

        if ($plural !== null) {
            $unit['plural'] = $plural;
        }

        if ($def !== null) {
            $unit['def'] = $def;
        }

        if ($comment !== null) {
            $unit['comment'] = $comment;
        }

        if ($symbol !== null) {
            $this->addAlias($catalog, $symbol, $unit['name']);

            if ($symbol === '′') {
                $this->addAlias($catalog, '\'', $unit['name']);
            }
        }

        foreach ($aliases as $alias) {
            $this->addAlias($catalog, $alias, $unit['name']);
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
    }

    /**
     * @return array{string|null, string|null}
     */
    private function readName(DOMElement $node, ?string $name, ?string $plural): array
    {
        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childNode->tagName === 'singular') {
                $name = trim($childNode->textContent);
            } elseif ($childNode->tagName === 'plural') {
                $plural = trim($childNode->textContent);
            }
        }

        return [$name, $plural];
    }

    /**
     * @return list<string>
     */
    private function readAliases(DOMElement $node): array
    {
        $aliases = [];

        foreach ($node->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if (in_array($childNode->tagName, ['singular', 'plural', 'symbol'], true)) {
                $aliases[] = trim($childNode->textContent);
                continue;
            }

            if ($childNode->tagName === 'noplural') {
                continue;
            }

            if ($childNode->tagName === 'name') {
                $aliases = array_merge($aliases, $this->readAliases($childNode));
                continue;
            }

            throw new \RuntimeException('Unhandled UDUNITS2 alias tag: ' . $childNode->tagName);
        }

        return $aliases;
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

        if ($name !== null) {
            $catalog['prefixes'][$name] = $value;
        }

        if ($symbol !== null) {
            $catalog['prefixes'][$symbol] = $value;
        }
    }

    /**
     * @phpstan-param MutableUdunits2Catalog $catalog
     */
    private function addAlias(array &$catalog, string $alias, string $name): void
    {
        $catalog['units'][$alias] = [
            'type' => 'alias',
            'name' => $alias,
            'def' => $name,
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
