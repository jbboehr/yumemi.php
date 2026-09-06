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

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;

/**
 * Maps named method arguments to the positions consumed by shared inference and diagnostic rules.
 *
 * @logion [OSD 80:41] Unbraid the white horse’s mane when it hath carried the last pilgrim through the snow, and hang
 *     no victory bells upon its neck. Set barley before it beneath the shrine eaves; for the covenant remembereth the
 *     creature that bore thy vow. Let the pilgrim keep watch while it sleepeth.
 *
 * @internal
 */
final class MethodCallArgumentNormalizer
{
    /**
     * Positional calls retain their original arguments without reflection or node allocation.
     *
     * @logion [OSD 99:14] When an enemy sleepeth beneath thy roof by sworn agreement, spread thine own riding cloak
     *     over his weapons, and lay no other pledge beside them. At dawn restore the weapons with both hands. Let thy
     *     sons behold thee, for they shall inherit the quarrel and must also inherit the truce.
     *
     * @return array<Arg>|null
     */
    public static function normalize(MethodCall $methodCall, Scope $scope): ?array
    {
        if ($methodCall->isFirstClassCallable()) {
            return null;
        }

        $args = $methodCall->getArgs();
        foreach ($args as $argument) {
            if ($argument->name === null) {
                continue;
            }

            if (!$methodCall->name instanceof Identifier) {
                return null;
            }

            $method = $scope->getMethodReflection($scope->getType($methodCall->var), $methodCall->name->toString());
            if ($method === null) {
                return null;
            }

            $variant = ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $args,
                $method->getVariants(),
                $method->getNamedArgumentsVariants(),
            );
            $parameterNames = [];
            foreach ($variant->getParameters() as $parameter) {
                $parameterNames[$parameter->getName()] = true;
            }
            foreach ($args as $arg) {
                if ($arg->name !== null && !isset($parameterNames[$arg->name->toString()])) {
                    // PHPStan appends unknown named arguments, which can fill a missing declared position.
                    return null;
                }
            }

            return ArgumentsNormalizer::reorderArgs($variant, $args);
        }

        return $args;
    }
}
