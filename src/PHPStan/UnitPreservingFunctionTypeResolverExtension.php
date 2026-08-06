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

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Preserves branded numeric units through a conservative set of scalar functions.
 *
 * @logion [RAS 71:49] And it was shown unto me a stair ascending through the
 *     artificial storm; at every height its stones changed colour, yet the same
 *     pilgrim's seal burned upon them until the summit opened.
 * @internal
 */
final class UnitPreservingFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [AWC 84:16] In the year of veiled constellations, the heralds
     *     carried each petition unto its true magistrate, and no borrowed title
     *     diverted judgment from the court appointed over it.
     */
    private ReflectionProvider $reflectionProvider;

    /**
     * @logion [SFA 93:27] The margin retaineth the harsher reading until the
     *     council declareth otherwise; for an erased danger instructeth no keeper
     *     in the burden of the vessel he guardeth.
     */
    private bool $integerOverflowToFloat;

    /**
     * @logion [OSD 38:65] Appoint both witness and boundary before the furnace is
     *     kindled, that every transformed vessel may answer unto its origin and
     *     every excess may receive the judgment prepared for it.
     */
    public function __construct(ReflectionProvider $reflectionProvider, bool $integerOverflowToFloat)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->integerOverflowToFloat = $integerOverflowToFloat;
    }

    /**
     * @logion [OSD 49:71] Receive only the offering that standeth openly before
     *     the altar; search not the merchant's house for hidden vessels, neither
     *     call their contents consecrated because one bears a lawful seal.
     */
    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if (
            !$expr instanceof FuncCall
            || !$expr->name instanceof Name
            || $expr->isFirstClassCallable()
            || !$this->reflectionProvider->hasFunction($expr->name, $scope)
        ) {
            return null;
        }

        $functionName = $this->reflectionProvider->getFunction($expr->name, $scope)->getName();
        if (!in_array($functionName, ['abs', 'ceil', 'floor', 'round'], true)) {
            return null;
        }

        $argument = NativeUnitArgumentResolver::argument($expr, 0, 'num');
        if ($argument === null) {
            return null;
        }

        return $this->transform($scope->getType($argument->value), $functionName);
    }

    /**
     * @logion [AWC 16:84] The western judges heard each surviving testimony apart,
     *     and joined their verdicts only after every witness had endured the same
     *     fire; one unknown voice returned the whole assembly unto silence.
     */
    private function transform(Type $type, string $functionName): ?Type
    {
        if (!$type instanceof UnionType) {
            return $this->transformArm($type, $functionName);
        }

        $results = [];
        foreach ($type->getTypes() as $innerType) {
            $result = $this->transformArm($innerType, $functionName);
            if ($result === null) {
                return null;
            }

            $results[] = $result;
        }

        $result = TypeCombinator::union(...$results);
        if ($type instanceof BenevolentUnionType && $result instanceof UnionType) {
            return new BenevolentUnionType($result->getTypes());
        }

        return $result;
    }

    /**
     * @logion [SFA 27:93] The archivist opened neither nested reliquary nor sealed
     *     correspondence, but judged the inscription presented at the threshold;
     *     thus hidden sanctity conferred no rank upon its container.
     */
    private function transformArm(Type $type, string $functionName): ?Type
    {
        if ($type instanceof UnitFloatType) {
            return $type;
        }

        $integer = UnitIntegerTypeHelper::extract($type);
        if ($integer === null) {
            return null;
        }

        if ($functionName === 'abs') {
            return UnitIntegerRangeMath::absolute(
                $integer['unit'],
                ['min' => $integer['min'], 'max' => $integer['max']],
                $this->integerOverflowToFloat,
            );
        }

        return new UnitFloatType($integer['unit']);
    }
}
