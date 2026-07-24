<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Exception\UnitNotFoundException;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Parser\Parser;
use jbboehr\IudexMensurarumMysteriorum\Registry\UnitRegistry;

/**
 * Resolves unit identifiers against a registry.
 *
 * This is the only place that turns catalog records (and prebuilt Units) into
 * expression trees. Registries supply data; they do not parse definitions.
 *
 * Resolution is fail-closed:
 * 1. exact catalog record or prebuilt Unit (including aliases)
 * 2. a single SI/catalog prefix applied to an exact residual name
 *
 * Residuals after a prefix are never re-prefixed. Unknown strings such as
 * "mass" or "bus" do not invent units.
 */
final class UnitResolver
{
    /** @var array<string, Expr|null> */
    private array $cache = [];

    /** @var array<string, Expr> */
    private array $prefixCache = [];

    /** @var array<string, true> Names currently being resolved (cycle detection). */
    private array $resolving = [];

    private readonly AstConverter $astConverter;

    public function __construct(
        private readonly UnitRegistry $unitRegistry,
    ) {
        $this->astConverter = new AstConverter($this);
    }

    public function resolve(string $name): ?Expr
    {
        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        if (isset($this->resolving[$name])) {
            throw new \UnexpectedValueException('Circular unit alias or definition for: ' . $name);
        }

        $this->resolving[$name] = true;

        try {
            $resolved = $this->resolveUncached($name);
        } finally {
            unset($this->resolving[$name]);
        }

        return $this->cache[$name] = $resolved;
    }

    public function resolveOrFail(string $name): Expr
    {
        return $this->resolve($name) ?? throw UnitNotFoundException::create($name);
    }

    private function resolveUncached(string $name): ?Expr
    {
        return $this->resolveExact($name)
            ?? $this->tryLookupWithPrefixes($name);
    }

    /**
     * Exact catalog/prebuilt hit only — no prefix decomposition.
     */
    private function resolveExact(string $name): ?Expr
    {
        $record = $this->unitRegistry->record($name);
        if ($record !== null) {
            return $this->exprFromRecord($record);
        }

        return $this->unitRegistry->lookup($name);
    }

    /**
     * @param array{type: 'base'|'dimensionless'|'unit'|'alias', name: string, def?: string} $record
     */
    private function exprFromRecord(array $record): Expr
    {
        return match ($record['type']) {
            'alias' => $this->resolve($record['def'] ?? throw new \UnexpectedValueException(
                'Catalog alias is missing target: ' . $record['name'],
            )) ?? throw UnitNotFoundException::create($record['def']),
            'base' => new Unit($record['name']),
            'dimensionless' => new Unit($record['name'], new Constant(1)),
            'unit' => new Unit(
                $record['name'],
                $this->astConverter->convert(Parser::parseString(
                    $record['def'] ?? throw new \UnexpectedValueException(
                        'Catalog unit is missing definition: ' . $record['name'],
                    ),
                )),
            ),
        };
    }

    private function tryLookupWithPrefixes(string $name): ?Expr
    {
        foreach ($this->sortedPrefixes() as $prefix => $definition) {
            if (!str_starts_with($name, $prefix)) {
                continue;
            }

            $remainingName = substr($name, strlen($prefix));
            if ($remainingName === '') {
                continue;
            }

            // Exact catalog/prebuilt residual only — no nested prefixes.
            $remainingUnit = $this->resolveExact($remainingName);
            if ($remainingUnit === null) {
                continue;
            }

            return new Compound([
                $this->prefixToExpr($definition),
                $remainingUnit,
            ]);
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function sortedPrefixes(): array
    {
        $prefixes = $this->unitRegistry->prefixes();
        uksort($prefixes, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        return $prefixes;
    }

    private function prefixToExpr(string $definition): Expr
    {
        if (!array_key_exists($definition, $this->prefixCache)) {
            $this->prefixCache[$definition] = $this->astConverter->convert(Parser::parseString($definition));
        }

        return $this->prefixCache[$definition];
    }
}
