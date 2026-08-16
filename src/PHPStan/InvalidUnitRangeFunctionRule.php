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
 * Emits diagnostics for invalid native unit-aware ranges.
 *
 * @logion [AWC 76:98] The lords of the floating terraces lifted their vineyards above the clouds, saying that soil was
 *     an indignity beneath their station. For one harvest the grapes shone like amethyst; in the second they were
 *     hollow, and in the third the earth below turned to glass. When the terraces descended, no root would enter it,
 *     and the province drank its vintage in mourning.
 *
 * @implements Rule<FuncCall>
 * @internal
 */
final class InvalidUnitRangeFunctionRule implements Rule
{
    /**
     * @logion [SFA 33:71] Console not the exiles by praising the road behind them. Show them the black pines that lean
     *     toward the vanished province though their roots hold fast in foreign stone; for fidelity is not the refusal
     *     of new ground, but the direction in which strength remembereth its debt.
     */
    public function __construct(
        private readonly UnitRangeFunctionTypeResolverExtension $extension,
    ) {
    }

    /**
     * @logion [OSD 99:56] Before a magistrate speaketh judgment, let him bear through the hall an earthen urn filled with
     *     dust from the common market. If he hold it lightly, remove his seal; if with contempt, close the court. Only
     *     he who feeleth therein the weight of many unnamed labors may sit beneath the red canopy.
     */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @logion [AWC 3:60] In the summer when the white comet stood above the capital, the council silenced every
     *     instrument, fearing that heaven had come to accuse them. Only the children flew black kites from the northern
     *     wall. At sunset the comet bent toward them and wrote fire along the cords; each child returned with burned
     *     hands and a new chart of the coast, while the council's parchments showed only blank water.
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
                    ->identifier('yumemi.invalidUnitRange')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
