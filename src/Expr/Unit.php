<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Expr;

use jbboehr\Yumemi\Analyzer\DimensionResolver;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Analyzer\UnitNormalizer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\UnsupportedUnitDimensionException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Units;
use jbboehr\Yumemi\Util\MathTrait;

/**
 * A named unit leaf in an expression tree.
 *
 * Application code should obtain units via {@see Units::unit()} (or parse/quantity
 * APIs on {@see Units}), not by constructing this class directly.
 */
final class Unit implements Expr
{
    use MathTrait;

    /** @var \WeakReference<Units>|null */
    private readonly ?\WeakReference $units;

    /**
     * @internal Prefer {@see Units::unit()} for application code.
     *
     * @param \WeakReference<Units>|null $units Optional catalog context for dimension fallback.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?Expr $definition = null,
        ?\WeakReference $units = null,
    ) {
        if ($this->name === '') {
            throw new \InvalidArgumentException('Unit name must not be empty.');
        }

        $this->units = $units;
    }

    /**
     * Bind a Units context used when dimension cannot be derived from the definition tree alone.
     */
    public function withUnits(Units $units): self
    {
        return new self($this->name, $this->definition, \WeakReference::create($units));
    }

    public function isBase(): bool
    {
        return $this->definition === null;
    }

    /**
     * Dimensional identity of this unit.
     *
     * Prefers expanding the attached definition tree (catalog-loaded units). If that fails and a
     * {@see Units} context is still bound, resolves the unit name through that catalog instead.
     */
    public function dimension(): Dimension
    {
        try {
            return (new DimensionResolver(new UnitNormalizer()))->resolve($this);
        } catch (UnsupportedUnitDimensionException $exception) {
            $units = $this->units?->get();
            if ($units !== null) {
                return $units->dimension($this->name);
            }

            throw UnsupportedUnitDimensionException::missingContext($this->name);
        }
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function reduce(): Expr
    {
        return ExprReducer::reduce($this);
    }
}
