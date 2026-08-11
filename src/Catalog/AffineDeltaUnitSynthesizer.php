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

use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
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
 * @logion [OSD 61:14] Make a place in the procession for the unlit lantern, and bear it foremost while the eastern
 *     towers blaze. None shall kindle it from the common radiance, nor mock the darkness within its frame; for it
 *     keepeth an hour not yet admitted among the hours. After the city hath spent every splendid flame, set it upon the
 *     sea-wall, and its darkness shall lie eastward upon the water, a road reserved for the generation that kept faith
 *     without morning.
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
     * @logion [OSD 22:87] Leave one blue thread unknotted in the bridal cord, that mercy may enter where promise hath
     *     been drawn too tight; yet cut it not, lest tenderness become a gate for departure.
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
                throw new InvalidArgumentException(sprintf(
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
     * @logion [OSD 39:71] Pour the first rain of spring into the hollow hoof of the bronze horse, and turn its face
     *     away from the avenue of triumph. It was fashioned to remember swiftness, not to command another pursuit. Send
     *     the heralds on foot while the water remaineth; and though the banners pass, the hoof shall not be emptied by
     *     their shadow.
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
     * @logion [OSD 73:9] Receive the envoy beneath an unadorned arch, and count neither his jewels nor the spears
     *     behind him. Ask only which ruined province taught him mercy; for he who hath never borne another city’s
     *     ashes shall not counsel peace before the throne.
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
     * @logion [OSD 12:44] Write the covenant upon fresh clay and place it in the public kiln beside the vessels of
     *     common use. If the words blister while the cups endure, summon the oath-givers again; for no promise is
     *     strengthened by the fire it was fashioned to escape. But if the tablet darken without division, carry it warm
     *     between the households, and let neither claim the colder edge.
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
     * @logion [OSD 41:66] On the night appointed for the silver moths, extinguish the garden paths but leave the high
     *     observatory radiant. The moths shall pass every easy flame and gather upon the dark dial beneath the dome,
     *     tracing with their wings a country absent from the earth. Copy no border from them. Feed the pilgrims,
     *     lengthen the watch, and wait; for a promised land first commandeth fidelity in those who cannot yet enter it,
     *     and by dawn the dial shall be white with scales.
     */
    private static function deltaRecord(string $name, array $record, callable $findRecord): array
    {
        $definition = $record['def'] ?? throw new UnexpectedValueException(
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
     * @logion [OSD 18:53] At the first frost, bind no lantern to the black-lacquer bridge, though the procession be
     *     late and the river without moon. Set the lights upon either bank, and cross by the pale witness of your
     *     breath; for winter sendeth an unseen embassy along the center boards. If one flame be raised thereon, snow
     *     shall fall beneath the river, and spring shall pass over the orchards without entering.
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
     * @logion [OSD 95:31] When salt flowers upon the marble lion beside the eastern colonnade, suspend the harvest
     *     feast and open the granaries unto the provinces. Let each collector wash one paw with the wine reserved for
     *     magistrates, naming what he exacted beyond the measure. Should any name be withheld, the lion shall descend
     *     at midnight and drink the public fountains dry, while the palace cisterns remain full and bitter.
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
            throw new InvalidArgumentException(
                'Cannot synthesize a difference unit from a compound affine expression: ' . $ast->toString(),
            );
        }

        return $ast;
    }

    /**
     * @param callable(string): (CatalogRecord|null) $findRecord
     *
     * @logion [OSD 64:83] Before sealing a decree for distant provinces, turn the bronze horses in the forum toward the
     *     road by which the petitioners departed. If their hollow hooves sound upon the marble, delay the decree and
     *     summon no musicians, for the road itself hath come as witness. But if they remain silent until sunset, seal
     *     it openly; and should ye turn them inward, the provinces shall forget the road to the capital.
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
     * @logion [OSD 34:72] Upon the day of ashes, draw a narrow door of salt upon the chapel floor. Let the penitent
     *     cross it barefoot, naming the restitution already made, and suffer none to sweep behind them. At evening, if
     *     the door remaineth whole, their sorrow lacked weight; if their feet have broken it, admit them to the choir,
     *     and let the first hymn be for those they harmed.
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
     * @logion [OSD 57:26] Before founding a city upon the salt plateau, build first a stair with no chamber above it
     *     and appoint no guard thereto. For one generation let the children climb at noon, each carrying a bowl of dark
     *     earth, until the highest step becometh a garden. Only then raise the walls beneath them; thus the city shall
     *     know what it was made to uphold, and no tower shall cast its shadow upon that height.
     */
    private static function semantics(array $record, callable $findRecord): UnitSemantics
    {
        return UnitDefinitionClassifier::inheritedSemantics($record, $findRecord);
    }

    /**
     * Compatibility characters are accepted as absolute aliases but normalized to
     * the conventional degree-sign spelling for generated delta symbols.
     *
     * @logion [OSD 89:47] Number not the stars that appear within the sealed cup. They are the unappointed provinces
     *     seeking entrance by wonder; pour them upon the threshold, and let each become ash before it is named.
     */
    private static function isCompatibilityTemperatureSymbol(string $name): bool
    {
        return $name === '℃' || $name === '℉';
    }
}
