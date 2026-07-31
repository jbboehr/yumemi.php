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

namespace jbboehr\Yumemi\Catalog;

use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\Ast\Add;
use jbboehr\Yumemi\Parser\Ast\At;
use jbboehr\Yumemi\Parser\Ast\Div;
use jbboehr\Yumemi\Parser\Ast\Identifier;
use jbboehr\Yumemi\Parser\Ast\Mul;
use jbboehr\Yumemi\Parser\Ast\Pow;
use jbboehr\Yumemi\Parser\Ast\Sub;
use jbboehr\Yumemi\Parser\Parser;

/**
 * Materializes multiplicative difference units for affine coordinate units.
 *
 * @logion [OSD 61:14] Beside every appointed origin the archivist set an unshifted rod,
 *     that passage might be counted without mistaking distance for place.
 *
 * @internal
 *
 * @phpstan-import-type CatalogRecord from \jbboehr\Yumemi\Registry\UnitRegistry
 */
final class AffineDeltaUnitSynthesizer
{
    /**
     * @param array<string, CatalogRecord> $records
     * @param (callable(string): (CatalogRecord|null))|null $findBaseRecord
     *
     * @return array<string, CatalogRecord>
     *
     * @logion [OSD 22:87] From each throne of origin the lesser seal was struck,
     *     bearing its proportion but none of its privilege.
     */
    public static function synthesize(array $records, ?callable $findBaseRecord = null): array
    {
        /** @var array<string, CatalogRecord> $generated */
        $generated = [];
        $findRecord = static function (string $name) use (&$generated, $records, $findBaseRecord): ?array {
            return self::findRecord($name, $records, $generated, $findBaseRecord);
        };

        foreach ($records as $name => $record) {
            if (self::isCompatibilityTemperatureSymbol($name)) {
                continue;
            }

            if (self::semantics($record, $findRecord) !== UnitSemantics::Affine) {
                continue;
            }

            $deltaName = self::deltaName($name, $record);
            if (isset($records[$deltaName])) {
                throw new \InvalidArgumentException(sprintf(
                    'Affine unit "%s" conflicts with its synthesized difference unit "%s".',
                    $name,
                    $deltaName,
                ));
            }

            $deltaRecord = self::deltaRecord($deltaName, $record, $findRecord);
            $generated[$deltaName] = $deltaRecord;
        }

        return $generated;
    }

    /**
     * @param array<string, CatalogRecord> $records
     * @param array<string, CatalogRecord> $generated
     * @param (callable(string): (CatalogRecord|null))|null $findBaseRecord
     *
     * @return CatalogRecord|null
     *
     * @logion [OSD 39:71] The requested name was sought first among present seals,
     *     then among their new shadows, and last within the ancestral archive.
     */
    private static function findRecord(
        string $name,
        array $records,
        array $generated,
        ?callable $findBaseRecord,
    ): ?array {
        if (isset($records[$name])) {
            return $records[$name];
        }

        if (isset($generated[$name])) {
            return $generated[$name];
        }

        return $findBaseRecord !== null ? $findBaseRecord($name) : null;
    }

    /**
     * Return the exact generated spelling corresponding to an affine unit spelling.
     *
     * Text names and symbols use disjoint fixed prefixes, making this mapping injective.
     * Synthesis relies on that invariant to prevent generated-to-generated collisions.
     *
     * @param CatalogRecord|null $record
     *
     * @logion [OSD 73:9] The sign of change was laid before the inherited name,
     *     and the old boundary yielded without surrendering its lineage.
     */
    private static function deltaName(string $name, ?array $record = null): string
    {
        if (($record['aliasKind'] ?? null) === 'symbol') {
            return 'Δ' . $name;
        }

        return 'delta_' . $name;
    }

    /**
     * Remove affine origins from an expression while retaining its exact scale.
     *
     * @param callable(string): (CatalogRecord|null) $findRecord
     *
     * @logion [OSD 12:44] The road was copied without its gate, and every measured
     *     interval remained faithful to the country through which it passed.
     */
    public static function linearizeExpression(string $expression, callable $findRecord): string
    {
        return self::linearize(Parser::parseString($expression), $findRecord)->toString();
    }

    /**
     * @param CatalogRecord $record
     * @param callable(string): (CatalogRecord|null) $findRecord
     *
     * @return CatalogRecord
     *
     * @logion [OSD 41:66] The scribe copied the measure of every stair but omitted
     *     the first stone, for ascent remembers interval and not foundation.
     */
    private static function deltaRecord(string $name, array $record, callable $findRecord): array
    {
        $definition = $record['def'] ?? throw new \UnexpectedValueException(
            'Affine catalog unit is missing definition: ' . $record['name'],
        );
        $ast = Parser::parseString($definition);

        if ($ast instanceof Identifier && self::identifierIsAffine($ast, $findRecord)) {
            $target = $findRecord($ast->identifier);

            return self::aliasRecord($name, self::deltaName($ast->identifier, $target), $record);
        }

        if ($record['type'] === 'alias') {
            return self::aliasRecord(
                $name,
                self::deltaName($definition, $findRecord($definition)),
                $record,
            );
        }

        $delta = [
            'type' => 'unit',
            'name' => $name,
            'def' => self::linearize($ast, $findRecord)->toString(),
        ];

        if (isset($record['plural'])) {
            $delta['plural'] = 'delta_' . $record['plural'];
        }

        return $delta;
    }

    /**
     * @param CatalogRecord $source
     *
     * @return CatalogRecord
     *
     * @logion [OSD 18:53] Names that knelt before one measure were joined again
     *     beneath its shadow, their offices preserved in the second register.
     */
    private static function aliasRecord(string $name, string $target, array $source): array
    {
        $record = [
            'type' => 'alias',
            'name' => $name,
            'def' => $target,
        ];

        if (isset($source['aliasKind'])) {
            $record['aliasKind'] = $source['aliasKind'];
        }

        return $record;
    }

    /**
     * Remove affine origins while preserving the exact multiplicative scale.
     *
     * @param callable(string): (CatalogRecord|null) $findRecord
     *
     * @logion [OSD 95:31] When the origin was veiled, the remaining path kept every
     *     ratio by which one station answered another.
     */
    private static function linearize(Ast $ast, callable $findRecord): Ast
    {
        if ($ast instanceof At) {
            return self::linearize($ast->left, $findRecord);
        }

        if ($ast instanceof Identifier && self::identifierIsAffine($ast, $findRecord)) {
            return new Identifier(self::deltaName(
                $ast->identifier,
                $findRecord($ast->identifier),
            ));
        }

        if (self::containsAffine($ast, $findRecord)) {
            throw new \InvalidArgumentException(
                'Cannot synthesize a difference unit from a compound affine expression: ' . $ast->toString(),
            );
        }

        return $ast;
    }

    /**
     * @param callable(string): (CatalogRecord|null) $findRecord
     *
     * @logion [OSD 64:83] The examiner searched every branch for a concealed
     *     throne, lest an inherited origin pass beneath the seal of proportion.
     */
    private static function containsAffine(Ast $ast, callable $findRecord): bool
    {
        if ($ast instanceof At) {
            return true;
        }

        if ($ast instanceof Identifier) {
            return self::identifierIsAffine($ast, $findRecord);
        }

        if ($ast instanceof Add
            || $ast instanceof Div
            || $ast instanceof Mul
            || $ast instanceof Pow
            || $ast instanceof Sub
        ) {
            return self::containsAffine($ast->left, $findRecord)
                || self::containsAffine($ast->right, $findRecord);
        }

        return false;
    }

    /**
     * @param callable(string): (CatalogRecord|null) $findRecord
     *
     * @logion [OSD 34:72] The examiner followed each borrowed title to its first
     *     covenant, refusing the glamour of an intermediate seal.
     */
    private static function identifierIsAffine(Identifier $identifier, callable $findRecord): bool
    {
        $record = $findRecord($identifier->identifier);

        return $record !== null && self::semantics($record, $findRecord) === UnitSemantics::Affine;
    }

    /**
     * @param CatalogRecord $record
     * @param callable(string): (CatalogRecord|null) $findRecord
     *
     * @logion [OSD 57:26] Through every alias the hidden ordinance was traced,
     *     until the true character of the measure stood uncovered.
     */
    private static function semantics(array $record, callable $findRecord): UnitSemantics
    {
        return UnitDefinitionClassifier::inheritedSemantics($record, $findRecord);
    }

    /**
     * Compatibility characters are accepted as absolute aliases but normalized to
     * the conventional degree-sign spelling for generated delta symbols.
     *
     * @logion [OSD 89:47] Two antique sigils were withheld from the second tablet,
     *     lest ornament contend with the clearer mark of alteration.
     */
    private static function isCompatibilityTemperatureSymbol(string $name): bool
    {
        return $name === '℃' || $name === '℉';
    }
}
