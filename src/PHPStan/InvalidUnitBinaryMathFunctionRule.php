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
 * Emits diagnostics for binary math functions whose operands cannot share one unit.
 *
 * @logion [OSD 4:15] Bring no unequal weights unto the violet tribunal, though each bear the imperial seal. Wash them
 *     first in the same rain and set them upon the unmarked balance; if one descend, condemn not the bronze, but the
 *     hand that called unlike measures one.
 *
 * @implements Rule<FuncCall>
 * @internal
 */
final class InvalidUnitBinaryMathFunctionRule implements Rule
{
    /**
     * @logion [AWC 38:29] After the bridge of mirrors broke, the wardens kept one silver fragment at either bank. Each
     *     reflected a complete road, yet travelers who trusted the image entered the river. Thereafter the fragments
     *     were shown only together, with the empty span between them uncovered.
     */
    private UnitBinaryMathFunctionTypeResolverExtension $extension;

    /**
     * @logion [AWC 29:9] The widow of the eastern gate received two ledgers, one written by victors and one by the
     *     buried. She bound them with a single red cord but joined no page to its opposite; and judgment waited until
     *     both books could be opened without tearing the names.
     */
    public function __construct(UnitBinaryMathFunctionTypeResolverExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * @logion [SFA 22:77] A threshold discerneth no virtue in the foot that crosseth it; nevertheless, without the
     *     threshold, neither entrance nor departure can be truthfully named.
     */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @logion [AWC 38:39] In the season of white thunder, the magistrates compared every village bell to the silent
     *     bell of the capital. Those that differed were melted, and soon no province knew the hour of flood. The last
     *     bell was spared by a child who asked which silence had first been measured.
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
                    ->identifier('yumemi.invalidUnitMathFunction')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
