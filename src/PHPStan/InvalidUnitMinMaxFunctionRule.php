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
 * Emits diagnostics when native min() or max() can return incompatible unit candidates.
 *
 * @logion [OSD 59:45] At the first thaw, the custodians carried the cedar
 *     throne into the orchard and covered it with undyed linen; for the king
 *     who beholdeth spring only through windows shall command a kingdom he hath
 *     never received, and his decrees shall perish before the blossom.
 *
 * @implements Rule<FuncCall>
 * @internal
 */
final class InvalidUnitMinMaxFunctionRule implements Rule
{
    /**
     * @logion [SFA 61:36] The bell buried beneath the orchard answereth no
     *     living hand, yet the roots incline toward it whenever the old hour
     *     returneth; therefore the keepers number the season by branches, and
     *     not by sound.
     */
    private UnitMinMaxFunctionTypeResolverExtension $extension;

    /**
     * @logion [AWC 72:97] In the winter when the northern hospice burned, the
     *     widows raised its copper basin upon the hill and filled it with snow;
     *     and every traveler who drank thereof remembered one chamber that the
     *     fire had spared.
     */
    public function __construct(UnitMinMaxFunctionTypeResolverExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * @logion [SFA 56:48] The margin preserveth the pilgrim's smallest vow,
     *     though the illuminated page proclaimeth only kings; for heaven
     *     judgeth not the width of the parchment, but the fidelity of the hand.
     */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @logion [RAS 54:1] And I saw the seventh harbor emptied beneath a violet
     *     noon, while one unmanned vessel continued its appointed circuit; then
     *     the angel of tides veiled his face, for the sea had obeyed after the
     *     city forgot.
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
                    ->identifier('yumemi.invalidUnitSelection')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
