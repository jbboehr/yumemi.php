<?php

namespace jbboehr\Yumemi\Registry;

use jbboehr\Yumemi\Expr\Unit;

/**
 * Immutable layered registry: overlay wins, then base (e.g. custom units over UDUNITS2).
 *
 * @phpstan-import-type CatalogRecord from UnitRegistry
 */
final class CompositeUnitRegistry extends UnitRegistry
{
    public function __construct(
        private readonly UnitRegistry $base,
        private readonly UnitRegistry $overlay,
    ) {
        parent::__construct();
    }

    public function lookup(string $name): ?Unit
    {
        return $this->overlay->lookup($name) ?? $this->base->lookup($name);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_values(array_unique([
            ...$this->overlay->names(),
            ...$this->base->names(),
        ]));
    }

    /**
     * @phpstan-return CatalogRecord|null
     */
    public function record(string $name): ?array
    {
        return $this->overlay->record($name) ?? $this->base->record($name);
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        // Overlay keys win on conflict.
        return array_merge($this->base->prefixes(), $this->overlay->prefixes());
    }
}
