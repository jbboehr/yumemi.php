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

namespace jbboehr\Yumemi\Expr;

use jbboehr\Yumemi\Analyzer\DimensionResolver;
use jbboehr\Yumemi\Analyzer\ExpressionContextResolver;
use jbboehr\Yumemi\Analyzer\UnitNormalizer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\IncompatibleExpressionContextException;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\UnresolvableUnitDimensionException;
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
            throw new InvalidArgumentException('Unit name must not be empty.');
        }

        $this->units = $units;
    }

    /**
     * Bind a Units context used when dimension cannot be derived from the definition tree alone.
     */
    public function withUnits(Units $units): self
    {
        if ($this->units !== null) {
            $current = $this->units->get();
            if (!$current instanceof Units) {
                throw IncompatibleExpressionContextException::create(null, $units);
            }

            if ($current !== $units) {
                throw IncompatibleExpressionContextException::create($current, $units);
            }
        }

        try {
            $definitionContext = $this->definition !== null
                ? ExpressionContextResolver::resolve($this->definition)
                : null;
        } catch (IncompatibleExpressionContextException $exception) {
            if ($exception->leftContextId !== null || $exception->rightContextId !== null) {
                throw $exception;
            }

            throw IncompatibleExpressionContextException::create(null, $units);
        }

        if ($definitionContext !== null && $definitionContext !== $units) {
            throw IncompatibleExpressionContextException::create($definitionContext, $units);
        }

        if ($this->units !== null) {
            return $this;
        }

        return new self($this->name, $this->definition, \WeakReference::create($units));
    }

    /**
     * Return the weak reference that distinguishes an unbound leaf from a live or expired context.
     *
     * @logion [AWC 27:32] For seven years the keepers found a red fox sleeping upon the unused throne at daybreak,
     *     and each ruler forbade the hunt. In the eighth year the animal departed; that evening the mountain villages
     *     sent tribute freely, remembering a gentleness the court had never proclaimed.
     *
     * @return \WeakReference<Units>|null
     * @internal
     */
    public function unitsReference(): ?\WeakReference
    {
        return $this->units;
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
        ExpressionContextResolver::resolve($this);

        try {
            return (new DimensionResolver(new UnitNormalizer()))->resolve($this);
        } catch (UnresolvableUnitDimensionException $exception) {
            $units = $this->units?->get();
            if ($units !== null) {
                return $units->dimension($this->name);
            }

            throw UnresolvableUnitDimensionException::missingContext($this->name);
        }
    }

    public function toString(): string
    {
        return $this->name;
    }
}
