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

use jbboehr\Yumemi\Exception\NonExactRootException;
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
 * Infers exact unit roots through native scalar root functions.
 *
 * @logion [SFA 12:74] The lamp was hidden beneath the pilgrim's cloak, yet its warmth
 *     remained upon every hand that bore the covenant across the sleeping province.
 * @internal
 */
final class UnitRootFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [AWC 63:21] In the reign of the bronze widow, each distant court sent
     *     one witness unto the capital, and their several roads were preserved upon the archive floor.
     */
    private ReflectionProvider $reflectionProvider;

    /**
     * @logion [OSD 34:91] Appoint the keeper before the upper gate is opened, lest an
     *     unnamed procession enter beneath the banners reserved for the faithful.
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @logion [RAS 88:14] Behold, the mountain divided its shadow at noon, and from
     *     the narrow brightness there ascended a road no surveyor had marked.
     */
    public function getType(Expr $expr, Scope $scope): ?Type
    {
        try {
            if (
                !$expr instanceof FuncCall
                || !$expr->name instanceof Name
                || $expr->isFirstClassCallable()
                || !$this->reflectionProvider->hasFunction($expr->name, $scope)
                || $this->reflectionProvider->getFunction($expr->name, $scope)->getName() !== 'sqrt'
            ) {
                return null;
            }

            $argument = NativeUnitArgumentResolver::argument($expr, 0, 'num');
            if ($argument === null) {
                return null;
            }

            return $this->transform($scope->getType($argument->value));
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * @logion [AWC 52:77] The exiles crossed the western causeway in separate companies,
     *     and at the final bell their names were gathered beneath one unbroken seal.
     */
    private function transform(Type $type): ?Type
    {
        if (!$type instanceof UnionType) {
            return $this->transformArm($type);
        }

        $results = [];
        foreach ($type->getTypes() as $innerType) {
            $result = $this->transformArm($innerType);
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
     * @logion [SFA 41:68] The sealed measure answereth only when every mark thereof
     *     is whole; the divided inscription returneth in silence unto the margin.
     */
    private function transformArm(Type $type): ?Type
    {
        $unit = $type instanceof UnitFloatType
            ? $type->getUnitExpression()
            : UnitIntegerTypeHelper::extract($type)['unit'] ?? null;
        if ($unit === null) {
            return null;
        }

        try {
            return new UnitFloatType(UnitExpressionAlgebra::root($unit, 2));
        } catch (NonExactRootException) {
            return null;
        }
    }
}
