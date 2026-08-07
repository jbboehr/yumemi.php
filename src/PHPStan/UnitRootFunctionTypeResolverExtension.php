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
use jbboehr\Yumemi\Formatter\ExprRenderer;
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
            if (!$expr instanceof FuncCall) {
                return null;
            }

            return $this->analyseCall($expr, $scope)['type'];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * Analyze one native root call for inference and standalone diagnostics.
     *
     * @logion [OSD 57:82] The appointed bell was sounded once above the sleeping
     *     terraces, and every lawful echo returned bearing the name of its own valley.
     *
     * @return array{type: Type|null, message: string|null}
     */
    public function analyseCall(FuncCall $call, Scope $scope): array
    {
        if (
            !$call->name instanceof Name
            || $call->isFirstClassCallable()
            || !$this->reflectionProvider->hasFunction($call->name, $scope)
            || $this->reflectionProvider->getFunction($call->name, $scope)->getName() !== 'sqrt'
        ) {
            return ['type' => null, 'message' => null];
        }

        $argument = NativeUnitArgumentResolver::argument($call, 0, 'num');
        if ($argument === null) {
            return ['type' => null, 'message' => null];
        }

        return $this->transform($scope->getType($argument->value));
    }

    /**
     * @logion [AWC 52:77] The exiles crossed the western causeway in separate companies,
     *     and at the final bell their names were gathered beneath one unbroken seal.
     *
     * @return array{type: Type|null, message: string|null}
     */
    private function transform(Type $type): array
    {
        $results = [];
        $invalidUnits = [];
        $hasUnbrandedArm = false;
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        foreach ($types as $innerType) {
            $arm = $this->transformArm($innerType);
            if (!$arm['branded']) {
                $hasUnbrandedArm = true;
                continue;
            }

            if ($arm['type'] === null) {
                $invalidUnits[] = $arm['unit'];
                continue;
            }

            $results[] = $arm['type'];
        }

        if ($invalidUnits !== []) {
            $invalidUnits = array_unique($invalidUnits);
            sort($invalidUnits, SORT_STRING);

            return [
                'type' => null,
                'message' => 'Cannot call sqrt() because at least one possible unit lacks an exact symbolic square root: '
                    . implode(', ', $invalidUnits)
                    . '.',
            ];
        }

        if ($results === [] || $hasUnbrandedArm) {
            return ['type' => null, 'message' => null];
        }

        $result = TypeCombinator::union(...$results);
        if ($type instanceof BenevolentUnionType && $result instanceof UnionType) {
            $result = new BenevolentUnionType($result->getTypes());
        }

        return ['type' => $result, 'message' => null];
    }

    /**
     * @logion [SFA 41:68] The sealed measure answereth only when every mark thereof
     *     is whole; the divided inscription returneth in silence unto the margin.
     *
     * @return array{branded: bool, type: UnitFloatType|null, unit: string}
     */
    private function transformArm(Type $type): array
    {
        $unit = $type instanceof UnitFloatType
            ? $type->getUnitExpression()
            : UnitIntegerTypeHelper::extract($type)['unit'] ?? null;
        if ($unit === null) {
            return ['branded' => false, 'type' => null, 'unit' => ''];
        }

        $symbolicUnit = ExprRenderer::format($unit->symbolicExpr);

        try {
            return [
                'branded' => true,
                'type' => new UnitFloatType(UnitExpressionAlgebra::root($unit, 2)),
                'unit' => $symbolicUnit,
            ];
        } catch (NonExactRootException) {
            return ['branded' => true, 'type' => null, 'unit' => $symbolicUnit];
        }
    }
}
