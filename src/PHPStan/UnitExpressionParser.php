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

namespace jbboehr\Yumemi\PHPStan;

use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitDimensionException;
use jbboehr\Yumemi\Formatter\ExprFormatter;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Units;

/**
 * Parses unit expression strings through the IMM runtime for PHPStan.
 *
 * This is the bridge from static analysis to the shared unit engine. Later pieces
 * (custom types, PHPDoc resolvers) should call this instead of reimplementing parsing.
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
        $unitString = trim($unitString);

        if ($unitString === '') {
            return UnitExpressionParseResult::invalid('Unit expression must not be empty.');
        }

        try {
            $expr = $this->units->parse($unitString);
            $dimension = $this->units->dimension($expr);
            $normalized = $this->units->normalize($expr);

            return UnitExpressionParseResult::ok(new UnitExpression(
                $expr,
                ExprFormatter::format($expr),
                $dimension,
                $normalized,
            ));
        } catch (UnitNotFoundException $exception) {
            return UnitExpressionParseResult::invalid($exception->getMessage());
        } catch (UnsupportedSyntaxException $exception) {
            return UnitExpressionParseResult::invalid($exception->getMessage());
        } catch (UnsupportedUnitDimensionException $exception) {
            return UnitExpressionParseResult::invalid($exception->getMessage());
        } catch (ParseException $exception) {
            $message = $exception->getMessage();
            if ($message === '') {
                $message = 'Invalid unit expression syntax.';
            }

            return UnitExpressionParseResult::invalid($message);
        } catch (\Throwable $exception) {
            return UnitExpressionParseResult::invalid(
                'Failed to parse unit expression: ' . $exception->getMessage(),
            );
        }
    }
}
