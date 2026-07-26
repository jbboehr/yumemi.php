<?php

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Registry\UnitRegistry;

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
        return $this->resolve($name)
            ?? throw UnitNotFoundException::create($name, $this->suggestNames($name));
    }

    /**
     * @return list<string>
     */
    private function suggestNames(string $name): array
    {
        $names = $this->unitRegistry->names();
        if ($names === []) {
            return [];
        }

        $suggestions = [];
        $nameLower = strtolower($name);

        foreach ($names as $candidate) {
            if ($candidate === $name) {
                continue;
            }

            if (strcasecmp($candidate, $name) === 0) {
                $suggestions[$candidate] = 0;
            }
        }

        foreach ($names as $candidate) {
            if (isset($suggestions[$candidate]) || $candidate === $name) {
                continue;
            }

            if (abs(strlen($candidate) - strlen($name)) > 2) {
                continue;
            }

            $distance = levenshtein($nameLower, strtolower($candidate));
            if ($distance > 0 && $distance <= 2) {
                $suggestions[$candidate] = $distance;
            }
        }

        asort($suggestions, SORT_NUMERIC);

        return array_slice(array_keys($suggestions), 0, 5);
    }

    private function resolveUncached(string $name): ?Expr
    {
        return $this->resolveExact($name)
            ?? $this->tryLookupWithPrefixes($name);
    }

    /**
     * Exact catalog/prebuilt hit only — no prefix decomposition.
     *
     * Prebuilt {@see UnitRegistry::lookup()} entries win over catalog {@see UnitRegistry::record()}
     * rows so builder overlays can override UDUNITS2 names.
     */
    private function resolveExact(string $name): ?Expr
    {
        $prebuilt = $this->unitRegistry->lookup($name);
        if ($prebuilt !== null) {
            return $prebuilt;
        }

        $record = $this->unitRegistry->record($name);
        if ($record !== null) {
            return $this->exprFromRecord($record);
        }

        return null;
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
