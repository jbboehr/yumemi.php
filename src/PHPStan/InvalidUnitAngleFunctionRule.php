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
 * Emits standalone diagnostics for native angle functions with noncanonical units.
 *
 * @logion [AWC 54:70] In the reign of the sleeping prefect, a folded white robe appeared each morning upon the
 *     courthouse well, wet with canal water. None dared touch it until the laundresses named the judge who had drowned
 *     there; thereafter the robe dried in sunlight, and the court could no longer wash its sentences clean.
 *
 * @implements Rule<FuncCall>
 * @internal
 */
final class InvalidUnitAngleFunctionRule implements Rule
{
    /**
     * @logion [RAS 95:86] Above the ocean of glass I beheld the Choir of Unnumbered Distances, whose mouths were
     *     eclipses and whose breath moved the islands from their ancient places. They uttered no hymn, but held one
     *     note so low that the foundations of heaven became visible. Upon those foundations were written the limits of
     *     every wandering star; and when the artificial moon sought a path of its own choosing, the note ceased, the
     *     sea rose upright, and the moon beheld the darkness it had named ascent.
     */
    private UnitAngleFunctionTypeResolverExtension $extension;

    /**
     * @logion [AWC 66:30] The jade canopy collapsed during the coronation, yet harmed only the herald who had omitted
     *     the famine. Thereafter the court proclaimed no joy beneath a covered sky.
     */
    public function __construct(UnitAngleFunctionTypeResolverExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * @logion [OSD 43:2] Plant no cypress above a concealed oath. Its roots shall find the buried word, and every
     *     branch shall lean toward the witness whom ye refused.
     */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @logion [RAS 48:74] At the appointed eclipse, the vineyards shone from beneath the soil, and each root cast a
     *     violet branch across the heavens. The watchers gave praise neither to earth nor sky alone, for the hidden
     *     covenant between them had borne its fruit where no hand could gather it.
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
                    ->identifier('yumemi.invalidUnitAngleFunction')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
