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

namespace jbboehr\Yumemi\Formatter;

use jbboehr\Yumemi\Analyzer\ResolvedUnitName;
use jbboehr\Yumemi\Analyzer\UnitNameResolver;
use jbboehr\Yumemi\Catalog\CatalogNameKind;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Parser\Ast\Identifier;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Registry\UnitRegistry;

/** Registry-aware user-facing rendering of unit expressions. */
final class ExprFormatter
{
    private readonly UnitNameResolver $unitNameResolver;

    /** @var array<string, string> */
    private array $unitNameCache = [];

    /** @var array<string, list<string>>|null */
    private ?array $prefixSymbols = null;

    public function __construct(
        private readonly UnitRegistry $unitRegistry,
        private readonly FormatOptions $options = new FormatOptions(),
    ) {
        $this->unitNameResolver = new UnitNameResolver($this->unitRegistry);
    }

    public function format(Expr $expr): string
    {
        return ExprRenderer::format($expr, $this->options, $this->formatUnitName(...));
    }

    private function formatUnitName(string $name): string
    {
        if ($this->options->unitNameStyle === UnitNameStyle::Preserve) {
            return $name;
        }

        return $this->unitNameCache[$name] ??= $this->formatResolvedUnitName(
            $name,
            $this->unitNameResolver->resolve($name),
        );
    }

    private function formatResolvedUnitName(string $name, ?ResolvedUnitName $resolved): string
    {
        if ($resolved === null) {
            return $name;
        }

        $unit = $this->unitRegistry->describe($resolved->unitName);
        if ($unit === null) {
            return $name;
        }

        $unitName = $this->options->unitNameStyle === UnitNameStyle::Symbol
            ? $this->preferredSymbol($unit->symbols) ?? $unit->canonicalName
            : $unit->canonicalName;

        if (!$resolved->isPrefixed()) {
            return $unitName;
        }

        $prefix = $this->unitRegistry->describePrefix($resolved->prefixName ?? '');
        if ($prefix === null) {
            return $name;
        }

        $prefixName = $this->options->unitNameStyle === UnitNameStyle::Symbol
            ? $this->preferredPrefixSymbol($prefix->canonicalName) ?? $prefix->canonicalName
            : $prefix->canonicalName;

        foreach ([$prefixName . $unitName, $prefix->canonicalName . $unit->canonicalName] as $candidate) {
            if ($candidate === $name) {
                return $name;
            }

            // Concatenation can hit an exact name or a different prefix split. Prove identity
            // from the same prefix definition and canonical unit, without normalizing expressions.
            $candidateResolved = $this->unitNameResolver->resolve($candidate);
            if (
                $candidateResolved !== null
                && $candidateResolved->prefixDefinition === $resolved->prefixDefinition
                && $this->unitRegistry->describe($candidateResolved->unitName)?->canonicalName === $unit->canonicalName
            ) {
                // Punctuation symbols may resolve as names but parse as multiple unit leaves.
                try {
                    $candidateAst = Parser::parseString($candidate);
                } catch (ParseException|ExpressionLimitExceededException) {
                    continue;
                }

                if ($candidateAst instanceof Identifier && $candidateAst->identifier === $candidate) {
                    return $candidate;
                }
            }
        }

        return $name;
    }

    /**
     * @param list<string> $symbols
     */
    private function preferredSymbol(array $symbols): ?string
    {
        if ($this->options->typography === Typography::Ascii) {
            $symbols = array_values(array_filter($symbols, self::isAscii(...)));
        }

        usort($symbols, self::compareSymbols(...));

        return $symbols[0] ?? null;
    }

    private function preferredPrefixSymbol(string $canonicalName): ?string
    {
        if ($this->prefixSymbols === null) {
            $this->prefixSymbols = [];

            foreach (array_keys($this->unitRegistry->prefixes()) as $name) {
                $prefix = $this->unitRegistry->describePrefix($name);
                if ($prefix === null || $prefix->matchedAs !== CatalogNameKind::Symbol) {
                    continue;
                }

                $this->prefixSymbols[$prefix->canonicalName][] = $name;
            }
        }

        return $this->preferredSymbol($this->prefixSymbols[$canonicalName] ?? []);
    }

    private static function isAscii(string $value): bool
    {
        return preg_match('/^[\x00-\x7f]*$/D', $value) === 1;
    }

    private static function compareSymbols(string $left, string $right): int
    {
        $lengthComparison = self::codepointLength($left) <=> self::codepointLength($right);

        return $lengthComparison !== 0 ? $lengthComparison : strcmp($left, $right);
    }

    private static function codepointLength(string $value): int
    {
        $result = preg_match_all('/./us', $value);

        return $result === false ? strlen($value) : $result;
    }
}
