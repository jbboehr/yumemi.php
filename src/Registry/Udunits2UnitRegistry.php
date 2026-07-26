<?php

namespace jbboehr\Yumemi\Registry;

use jbboehr\Yumemi\Expr\Unit;

/**
 * UDUNITS2 catalog data source.
 *
 * This class does not parse definition strings or own a UnitResolver/AstConverter.
 * {@see \jbboehr\Yumemi\Analyzer\UnitResolver} reads {@see record()}
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
 * @phpstan-type Udunits2DerivedUnit array{type: 'unit', name: string, def: string, definition?: string, plural?: string, comment?: string}
 * @phpstan-type Udunits2AliasUnit array{type: 'alias', name: string, def: string}
 * @phpstan-type Udunits2Unit Udunits2BaseUnit|Udunits2DimensionlessUnit|Udunits2DerivedUnit|Udunits2AliasUnit
 * @phpstan-type Udunits2Catalog array{
 *     units: array<string, Udunits2Unit>,
 *     base: list<string>,
 *     prefixes: array<string, string>,
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
    public function lookup(string $name): ?Unit
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
    public function record(string $name): ?array
    {
        $unit = $this->catalog['units'][$name] ?? null;
        if ($unit === null) {
            return null;
        }

        return match ($unit['type']) {
            'alias' => [
                'type' => 'alias',
                'name' => $unit['name'],
                'def' => $unit['def'],
            ],
            'base' => [
                'type' => 'base',
                'name' => $unit['name'],
            ],
            'dimensionless' => [
                'type' => 'dimensionless',
                'name' => $unit['name'],
            ],
            'unit' => [
                'type' => 'unit',
                'name' => $unit['name'],
                'def' => $unit['def'],
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return $this->catalog['prefixes'];
    }

    /**
     * @phpstan-return Udunits2Catalog
     */
    private function loadCatalog(string $dataFile): array
    {
        $catalog = require $dataFile;

        if (!is_array($catalog)) {
            throw new \UnexpectedValueException('UDUNITS2 catalog file must return an array.');
        }

        /** @phpstan-var Udunits2Catalog $catalog */
        return $this->materializePluralAliases($catalog);
    }

    /**
     * Expand catalog-backed plural forms into exact aliases.
     *
     * The generated data file only stores a handful of explicit plural fields and
     * omits common English plurals such as "meters". Materialising them here keeps
     * UnitResolver free of suffix-stripping heuristics while preserving ergonomics.
     *
     * @phpstan-param Udunits2Catalog $catalog
     * @phpstan-return Udunits2Catalog
     */
    private function materializePluralAliases(array $catalog): array
    {
        /** @var array<string, array{type: 'alias', name: string, def: string}> $additions */
        $additions = [];

        foreach ($catalog['units'] as $name => $unit) {
            if (isset($unit['plural'])) {
                $plural = $unit['plural'];
                if (!isset($catalog['units'][$plural]) && !isset($additions[$plural])) {
                    $additions[$plural] = [
                        'type' => 'alias',
                        'name' => $plural,
                        'def' => $name,
                    ];
                }
            }

            if (!self::isPluralizableName($name)) {
                continue;
            }

            foreach (self::pluralForms($name) as $plural) {
                if (isset($catalog['units'][$plural]) || isset($additions[$plural])) {
                    continue;
                }

                $additions[$plural] = [
                    'type' => 'alias',
                    'name' => $plural,
                    'def' => $name,
                ];
            }
        }

        foreach ($additions as $plural => $alias) {
            $catalog['units'][$plural] = $alias;
        }

        /** @phpstan-var Udunits2Catalog $catalog */
        return $catalog;
    }

    private static function isPluralizableName(string $name): bool
    {
        // Word-like catalog keys only. Symbols, mixed case SI abbreviations, and
        // very short names are left exact (m, Pa, °C, ...).
        return strlen($name) >= 3
            && preg_match('/^[a-z][a-z0-9_]*$/', $name) === 1;
    }

    /**
     * @return list<string>
     */
    private static function pluralForms(string $name): array
    {
        if (str_ends_with($name, 'y') && strlen($name) >= 2) {
            $previous = $name[strlen($name) - 2];
            if (!str_contains('aeiou', $previous)) {
                return [substr($name, 0, -1) . 'ies'];
            }
        }

        if (
            str_ends_with($name, 's')
            || str_ends_with($name, 'x')
            || str_ends_with($name, 'z')
            || str_ends_with($name, 'ch')
            || str_ends_with($name, 'sh')
        ) {
            return [$name . 'es'];
        }

        return [$name . 's'];
    }
}
