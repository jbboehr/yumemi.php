<?php

namespace jbboehr\IudexMensurarumMysteriorum\Registry;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\AstConverter;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitResolver;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Parser\Parser;

/**
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
 */
final class Udunits2UnitRegistry extends UnitRegistry
{
    private const DATA_FILE = __DIR__ . '/../../data/udunits2.php';

    /** @phpstan-var Udunits2Catalog */
    private readonly array $catalog;

    /** @var array<string, Unit|null> */
    private array $cache = [];

    private readonly AstConverter $astConverter;

    public function __construct(?string $dataFile = null)
    {
        parent::__construct();

        $this->catalog = $this->loadCatalog($dataFile ?? self::DATA_FILE);
        $this->astConverter = new AstConverter(new UnitResolver($this));
    }

    public function lookup(string $name): ?Unit
    {
        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        $unit = $this->catalog['units'][$name] ?? null;
        if ($unit === null) {
            return $this->cache[$name] = null;
        }

        return $this->cache[$name] = match ($unit['type']) {
            'alias' => $this->lookup($unit['def']),
            'base' => new Unit($unit['name']),
            'dimensionless' => new Unit($unit['name'], new Constant(1)),
            'unit' => new Unit($unit['name'], $this->definitionToExpr($unit['def'])),
        };
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return $this->catalog['prefixes'];
    }

    private function definitionToExpr(string $definition): Expr
    {
        return $this->astConverter->convert(Parser::parseString($definition));
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
        return $catalog;
    }
}
