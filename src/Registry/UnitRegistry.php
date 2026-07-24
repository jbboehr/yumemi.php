<?php

namespace jbboehr\IudexMensurarumMysteriorum\Registry;

use jbboehr\IudexMensurarumMysteriorum\Exception\UnitNotFoundException;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;

/**
 * Unit name table / catalog data source.
 *
 * Hand-built registries expose precomposed {@see Unit} values via {@see lookup()}.
 * Catalog-backed registries expose raw rows via {@see record()}; {@see Analyzer\UnitResolver}
 * parses definitions and is the only resolving brain.
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
     * @param iterable<Unit> $units
     */
    public function __construct(iterable $units = [])
    {
        foreach ($units as $unit) {
            $this->register($unit);
        }
    }

    public static function defaults(): self
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        return new self([
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
        ]);
    }

    public function get(string $name): Unit
    {
        return $this->lookup($name) ?? throw UnitNotFoundException::create($name);
    }

    public function lookup(string $name): ?Unit
    {
        return $this->units[$name] ?? null;
    }

    /**
     * Raw catalog row for catalog-backed registries.
     *
     * Default registries that only store precomposed Units return null; the resolver
     * then uses {@see lookup()} instead.
     *
     * @phpstan-return CatalogRecord|null
     */
    public function record(string $name): ?array
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return [];
    }

    public function register(Unit $unit): void
    {
        $this->units[$unit->name] = $unit;
    }
}
