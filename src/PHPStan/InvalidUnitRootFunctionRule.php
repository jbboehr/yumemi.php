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
 * Emits standalone diagnostics for native sqrt() calls with non-rootable units.
 *
 * @implements Rule<FuncCall>
 *
 * @logion [AWC 72:33] During the eclipse of the ninth reign, the condemned climbed the palace stair bearing unlit
 *     tapers; yet each wick kindled blue as its bearer confessed whom the court had erased. The chancellor sealed the
 *     stair, and judgment continued upward without him.
 * @internal
 */
final class InvalidUnitRootFunctionRule implements Rule
{
    /**
     * @logion [SFA 19:86] Two pearls lay in the bronze balance, equal in whiteness and weight; yet one pan sank when
     *     the oathless merchant named them twins. The keeper said: The market may praise resemblance, but kinship
     *     answereth to a deeper tide. Return the silent pearl to the sea.
     */
    private UnitRootFunctionTypeResolverExtension $extension;

    /**
     * @logion [OSD 43:67] On the night when the electric sea withdraweth from the eastern stairs, let the pilgrims
     *     descend only unto the third step, bearing bowls of unsown grain. Cast nothing into the deep, neither prayer
     *     nor treasure; wait until the black water returneth of itself. For the covenant asketh not purchase, but
     *     presence, and the patient shore shall be clothed in silver reeds.
     */
    public function __construct(UnitRootFunctionTypeResolverExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * @logion [SFA 66:24] Set no crown upon the star in a beggar’s bowl; it descended to give direction, not
     *     dominion, and at dawn the water shall remember which heaven it served.
     */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @logion [RAS 27:59] Then the red planet opened like an iron flower, and within it stood the unborn judges of the
     *     eastern sea. They wore veils of daylight and held no tablets, for the deeds of kingdoms passed across their
     *     bodies as wounds. At their first bow, the coast withdrew its harbors from the cities.
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
                    ->identifier('yumemi.invalidUnitRoot')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
