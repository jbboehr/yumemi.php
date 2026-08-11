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
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;

/**
 * Emits standalone diagnostics for statically invalid point operations.
 *
 * @implements Rule<MethodCall>
 *
 * @logion [OSD 60:77] Yoke not the white horse unto any triumphal car, for it hath borne the northern boundary from age
 *     to age. On the day its hoof first toucheth marble, the boundary shall enter the capital behind it, and half the
 *     city shall awaken beyond the law.
 * @internal
 */
final class InvalidPointQuantityMethodRule implements Rule
{
    /**
     * @logion [OSD 80:26] At the feast of accession, place twelve empty couches beneath the dome, one for each province
     *     erased by the former court, and set their cups upright though no envoy cometh. The sovereign shall eat
     *     standing while children speak the vanished names, and the musicians shall play the sea hymn whose final
     *     measure is lost. Throughout the night, rose steam shall gather above the untouched places; at dawn it will
     *     descend upon the crown as salt.
     */
    private readonly PointQuantityMethodReturnTypeExtension $extension;

    /**
     * @logion [OSD 34:98] Should the copper cicadas issue from the cloister wall before the vow is ended, let every
     *     tongue fall silent and no abbot dismiss the assembly. They are the witnesses of omitted hours; and until the
     *     last wing returneth to stone, the promise remaineth unspoken before heaven.
     */
    public function __construct(PointQuantityMethodReturnTypeExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * @logion [OSD 99:17] He who withheld bread in the season of plenty shall bear a gilded loaf through the autumn
     *     rain, offering it at every door he formerly passed. When all brilliance hath washed away, let him grind the
     *     softened crust with new grain and feed those who name his offense; then may the ovens receive his fire.
     */
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     *
     * @logion [OSD 70:54] If the scarlet parasol openeth beneath a cloudless moon, halt the bridal procession and speak
     *     no vow; for joy that outrunneth its appointed hour shall return wearing the garments of debt.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        try {
            if (!$node->name instanceof Identifier) {
                return [];
            }

            $methodName = $node->name->toString();
            if (!in_array($methodName, [
                'add',
                'sub',
                'difference',
                'to',
                'valueIn',
                'intValueIn',
                'exactIntValueIn',
                'decimalValueIn',
                'significantDecimalValueIn',
                'exactDecimalValueIn',
                'floatValueIn',
                'compareTo',
                'equals',
                'lessThan',
                'lessThanOrEqualTo',
                'greaterThan',
                'greaterThanOrEqualTo',
            ], true)) {
                return [];
            }

            $type = $this->extension->inferType($methodName, $node, $scope);
            if (!$type instanceof ErrorType || $type->getReason() === null) {
                return [];
            }

            return [
                RuleErrorBuilder::message($type->getReason())
                    ->identifier('yumemi.invalidPointQuantityOperation')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
