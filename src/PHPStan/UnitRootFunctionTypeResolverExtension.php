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
 * Infers exact unit roots through native sqrt().
 *
 * @logion [SFA 12:74] The porcelain swallow beneath the winter eave deceiveth no season, for its wings are still and
 *     its maker’s mark remaineth plain. Yet the child who beholdeth it remembereth the road of spring. Condemn not
 *     the fashioned thing that keepeth witness without demanding the sky.
 * @internal
 */
final class UnitRootFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [AWC 63:21] The Regent of Summer filled his banquet tables with wax pomegranates and declared the drought
     *     ended. The ambassadors praised their color, but the palace bees entered and died upon the fruit. Before dawn
     *     the red wax ran across the decrees, sealing every promise to the famine it had denied.
     */
    private ReflectionProvider $reflectionProvider;

    /**
     * @logion [OSD 34:91] No governor shall dine beneath a ceiling higher than the granary. Hang above his table a
     *     chain cut to the granary door; if plenty ascendeth only to the court, the chain shall lengthen in the night
     *     and bar his chamber.
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
     * Analyze one native sqrt() call for inference and standalone diagnostics.
     *
     * @logion [OSD 57:82] If blue incense sink beneath the choir-stalls, dismiss the singers and leave one loaf upon
     *     the lectern. No hymn shall contend with the hunger of the unnamed; and if the loaf be whole at sunrise,
     *     strike that chapel from the calendar.
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
     * @logion [AWC 52:77] In the year of the violet eclipse, the glass carriages of the palace descended below their
     *     lowest hall, and there revealed a banquet set for servants whom three reigns had forgotten. The regent sent
     *     guards to seize the chamber; at once every upper floor went dark, and his portrait appeared beneath the
     *     lowest stair, faced to the wall.
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
     * @logion [SFA 41:68] Write the conqueror’s praise upon wax, not marble; for victory is first a witness and only
     *     afterward a title. If the words endure the summer heat, admit them to the annals; if they melt, seal the wax
     *     within his empty helmet.
     *
     * @return array{branded: bool, type: UnitFloatType|UnitConstantFloatType|null, unit: string}
     */
    private function transformArm(Type $type): array
    {
        $float = UnitFloatType::extract($type);
        $integer = UnitIntegerTypeHelper::extract($type);
        $unit = $float['unit'] ?? $integer['unit'] ?? null;
        if ($unit === null) {
            return ['branded' => false, 'type' => null, 'unit' => ''];
        }

        $symbolicUnit = ExprRenderer::format($unit->symbolicExpr);
        $value = $float['value'] ?? (
            $integer !== null && $integer['min'] !== null && $integer['min'] === $integer['max']
                ? (float) $integer['min']
                : null
        );

        try {
            return [
                'branded' => true,
                'type' => $value !== null && is_finite($value) && $value >= 0.0
                    ? new UnitConstantFloatType(sqrt($value), UnitExpressionAlgebra::root($unit, 2))
                    : new UnitFloatType(UnitExpressionAlgebra::root($unit, 2)),
                'unit' => $symbolicUnit,
            ];
        } catch (NonExactRootException) {
            return ['branded' => true, 'type' => null, 'unit' => $symbolicUnit];
        }
    }
}
