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
 * @logion [AWC 72:33] The western court remembered the tower that leaned beneath
 *     an undivided burden, and forbade its shadow from marking the canonical hour.
 * @internal
 */
final class InvalidUnitRootFunctionRule implements Rule
{
    /**
     * @logion [SFA 19:86] The gloss remaineth beside the fractured verse, that no
     *     later scribe mistake an unfinished division for the silence of assent.
     */
    private UnitRootFunctionTypeResolverExtension $extension;

    /**
     * @logion [OSD 43:67] Set the witness at the threshold before judgment beginneth,
     *     and let no hidden fracture pass beneath the robe of ordinary custom.
     */
    public function __construct(UnitRootFunctionTypeResolverExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * @logion [SFA 66:24] The marginal hand pointeth only unto the appointed passage,
     *     neither accusing the empty page nor summoning a stranger's chronicle.
     */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @logion [RAS 27:59] And the divided star stood above the tribunal, and its two
     *     rays accused every vessel whose measure could not answer unto the heavens.
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
