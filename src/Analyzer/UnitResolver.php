<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Exception\UnitNotFoundException;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Parser\Parser;
use jbboehr\IudexMensurarumMysteriorum\Registry\UnitRegistry;

final class UnitResolver
{
    /** @var array<string, Expr|null> */
    private array $cache = [];

    /** @var array<string, Expr> */
    private array $prefixCache = [];

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

        return $this->cache[$name] = $this->unitRegistry->lookup($name)
            ?? $this->tryLookupWithPrefixes($name)
            ?? $this->tryLookupStripPlural($name);
    }

    public function resolveOrFail(string $name): Expr
    {
        return $this->resolve($name) ?? throw UnitNotFoundException::create($name);
    }

    private function tryLookupStripPlural(string $name): ?Expr
    {
        if (str_ends_with($name, 'es')) {
            return $this->resolve(substr($name, 0, -2));
        }

        if (str_ends_with($name, 's')) {
            return $this->resolve(substr($name, 0, -1));
        }

        return null;
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

            $remainingUnit = $this->resolve($remainingName);
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
