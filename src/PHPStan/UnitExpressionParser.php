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

namespace jbboehr\Yumemi\PHPStan;

use jbboehr\Yumemi\Analyzer\NormalizedExpr;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Exception\UnresolvableUnitDimensionException;
use jbboehr\Yumemi\Formatter\ExprRenderer;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Units;

/**
 * Parses unit expression strings through the Yumemi runtime for PHPStan.
 *
 * This is the bridge from static analysis to the shared unit engine. Later pieces
 * (custom types, PHPDoc resolvers) should call this instead of reimplementing parsing.
 * @internal
 */
final class UnitExpressionParser
{
    private readonly Units $units;

    public function __construct(?Units $units = null)
    {
        $this->units = $units ?? Units::default();
    }

    public function parse(string $unitString): UnitExpressionParseResult
    {
        if (trim($unitString) === '') {
            return UnitExpressionParseResult::invalid('Unit expression must not be empty.');
        }

        return $this->guardParse(function () use ($unitString): UnitExpressionParseResult {
            $expr = $this->units->parse($unitString);
            $dimension = $this->units->dimension($expr);
            $normalized = $this->units->normalize($expr);

            return UnitExpressionParseResult::ok(new UnitExpression(
                $expr,
                ExprRenderer::format($expr),
                $dimension,
                $normalized,
            ));
        }, 'Invalid unit expression syntax.');
    }

    public function parseQuantityUnit(string $quantityString): UnitExpressionParseResult
    {
        if (trim($quantityString) === '') {
            return UnitExpressionParseResult::invalid('Quantity expression must not be empty.');
        }

        return $this->guardParse(function () use ($quantityString): UnitExpressionParseResult {
            $quantity = $this->units->parseQuantity($quantityString);

            return $this->parse(ExprRenderer::format($quantity->unit()));
        }, 'Invalid quantity expression syntax.');
    }

    /**
     * Parse a named coordinate unit, preserving its exact origin and difference scale.
     *
     * @logion [OSD 54:83] The coordinate name was examined with its origin and rod,
     *     that static judgment might distinguish a station from an interval.
     */
    public function parsePoint(string $unitString): PointUnitExpressionParseResult
    {
        if (trim($unitString) === '') {
            return PointUnitExpressionParseResult::invalid('Point unit must not be empty.');
        }

        try {
            $point = $this->units->point(0, $unitString);
            $deltaQuantity = $this->units->deltaQuantity(1, $unitString);
            $deltaResult = $this->parse(ExprRenderer::format($deltaQuantity->unit()));
            if (!$deltaResult->isOk()) {
                return PointUnitExpressionParseResult::invalid(
                    $deltaResult->errorMessage() ?? 'Invalid point difference unit.',
                    $deltaResult->errorSpan(),
                );
            }

            $deltaUnit = $deltaResult->expression();
            $canonicalUnit = NormalizedExpr::withoutConstant($deltaUnit->normalizedExpr);

            return PointUnitExpressionParseResult::ok(new PointUnitExpression(
                $point->unit(),
                $point->dimension(),
                $deltaUnit,
                $this->units->convert(0, $point->unit(), $canonicalUnit),
            ));
        } catch (
            UnitNotFoundException
            | UnsupportedSyntaxException
            | UnsupportedUnitAlgebraException
            | UnsupportedUnitConversionException
            | UnresolvableUnitDimensionException $exception
        ) {
            return PointUnitExpressionParseResult::invalid($exception->getMessage(), $exception->span);
        } catch (\InvalidArgumentException $exception) {
            return PointUnitExpressionParseResult::invalid($exception->getMessage());
        } catch (ParseException $exception) {
            $message = $exception->getMessage();
            if ($message === '') {
                $message = 'Invalid point unit syntax.';
            }

            return PointUnitExpressionParseResult::invalid($message, $exception->getSpan());
        }
    }

    /**
     * @param callable(): UnitExpressionParseResult $parse
     */
    private function guardParse(
        callable $parse,
        string $syntaxFallback,
    ): UnitExpressionParseResult {
        try {
            return $parse();
        } catch (
            UnitNotFoundException
            | UnsupportedSyntaxException
            | UnsupportedUnitAlgebraException
            | UnsupportedUnitConversionException
            | UnresolvableUnitDimensionException $exception
        ) {
            return UnitExpressionParseResult::invalid($exception->getMessage(), $exception->span);
        } catch (ParseException $exception) {
            $message = $exception->getMessage();
            if ($message === '') {
                $message = $syntaxFallback;
            }
            return UnitExpressionParseResult::invalid($message, $exception->getSpan());
        }
    }
}
