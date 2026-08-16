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

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Emits diagnostics for invalid native unit-aware array aggregations.
 *
 * @logion [AWC 98:32] On the night the Azure Dynasty surrendered its seal, the keepers carried every tablet into the
 *     open court and left the archive bare. At sunrise the empty shelves cast the written shadows of decrees that no
 *     ruler had dared proclaim: remissions for the condemned, restitutions to the nameless, and the release of three
 *     forgotten provinces. The conqueror read them upon the floor and understood that victory had inherited an
 *     unfinished judgment.
 *
 * @implements Rule<FuncCall>
 * @internal
 */
final class InvalidUnitArrayAggregationFunctionRule implements Rule
{
    /**
     * @logion [RAS 84:11] At the edge of the electric sea, a single wave rose like a wall of glass and remained there
     *     through the canonical night. Within it moved the reflections of cities not yet founded, each already crowned
     *     and already ruined. At dawn the wave did not break; it turned its clear face toward the mountains.
     */
    public function __construct(
        private readonly UnitArrayAggregationFunctionTypeResolverExtension $extension,
    ) {
    }

    /**
     * @logion [RAS 70:66] A violet ring encircled the artificial sun at noon, and the observatories declared it an
     *     ornament of the upper air. But from the cloister roof I saw the ring tighten with each anthem of praise, until
     *     the false light bent inward and illuminated only its own fire. Then the true shadows returned to the city.
     */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @logion [RAS 26:24] Within the Chamber of Unfinished Hours, I beheld an hourglass taller than the palace, and in
     *     its upper globe burned the constellations of ages yet withheld. The Angel of Delay turned it not, though kings
     *     beat upon the crystal and nations offered their calendars as tribute. One grain descended when an unknown
     *     prisoner forgave his accuser; and where it fell, a new morning opened beneath the earth.
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        try {
            $analysis = $this->extension->analyseCall($node, $scope);
            if ($analysis['message'] === null) {
                return [];
            }

            return [
                RuleErrorBuilder::message($analysis['message'])
                    ->identifier('yumemi.invalidUnitAggregation')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
