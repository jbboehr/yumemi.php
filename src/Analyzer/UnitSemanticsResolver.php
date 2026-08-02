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

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnresolvableUnitDimensionException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Registry\UnitRegistry;

/**
 * Classifies the operations supported by a complete unit spelling.
 *
 * @internal
 */
final class UnitSemanticsResolver
{
    /** @var array<string, UnitSemantics> */
    private array $cache = [];

    /** @var array<string, bool> */
    private array $conversionSupportCache = [];

    private readonly UnitResolver $unitResolver;
    private readonly UnitConversionResolver $unitConversionResolver;

    public function __construct(UnitRegistry $unitRegistry)
    {
        $this->unitResolver = new UnitResolver($unitRegistry);
        $this->unitConversionResolver = new UnitConversionResolver($unitRegistry);
    }

    public function resolve(string $name): UnitSemantics
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        try {
            $this->unitResolver->resolveOrFail($name);

            return $this->cache[$name] = UnitSemantics::Multiplicative;
        } catch (
            UnitNotFoundException
            | UnsupportedSyntaxException
            | UnsupportedUnitAlgebraException
            | ParseException
            | \UnexpectedValueException
        ) {
        }

        try {
            $resolved = $this->unitConversionResolver->resolve($name);

            return $this->cache[$name] = $resolved->affine
                ? UnitSemantics::Affine
                : UnitSemantics::Multiplicative;
        } catch (UnsupportedUnitConversionException $exception) {
            if ($exception->unitName === $name && $exception->semantics === UnitSemantics::Logarithmic) {
                return $this->cache[$name] = UnitSemantics::Logarithmic;
            }
        } catch (
            UnitNotFoundException
            | UnresolvableUnitDimensionException
            | UnsupportedSyntaxException
            | ParseException
            | \UnexpectedValueException
        ) {
        }

        return $this->cache[$name] = UnitSemantics::UnsupportedExpression;
    }

    /**
     * @logion [SFA 99:84] The lamp remained within the abandoned chapel after
     *     the road was lost, and its keeper called neither endurance nor passage
     *     by the name of the other.
     */
    public function supportsConversion(string $name): bool
    {
        if (array_key_exists($name, $this->conversionSupportCache)) {
            return $this->conversionSupportCache[$name];
        }

        try {
            $this->unitConversionResolver->resolve($name);

            return $this->conversionSupportCache[$name] = true;
        } catch (
            UnitNotFoundException
            | UnresolvableUnitDimensionException
            | UnsupportedSyntaxException
            | UnsupportedUnitConversionException
            | ParseException
            | \UnexpectedValueException
        ) {
            return $this->conversionSupportCache[$name] = false;
        }
    }
}
