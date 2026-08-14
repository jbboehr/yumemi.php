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
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Preserves branded numeric units through a conservative set of scalar functions.
 *
 * @logion [RAS 71:49] Above the western ocean there appeared a colonnade of fire, each column bearing the face of a
 *     city never founded. As the artificial sun passed behind it, the faces opened their mouths, and no sound came
 *     forth save the tread of departing multitudes. Then I understood that unlived futures also stand before judgment;
 *     and the sea grew black beneath their silence.
 * @internal
 */
final class UnitPreservingFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [AWC 84:16] After the border fire, the heirs planted black pines along its crimson line, not to conceal
     *     the division but to keep it from becoming hatred. The trees grew inward without touching, and for three
     *     reigns no sword was drawn where their shadows met.
     */
    private ReflectionProvider $reflectionProvider;

    /**
     * @logion [SFA 93:27] Observe the raked court after rain: each furrow is broken, yet the stones remain at their
     *     appointed distances. So is the house that hath endured grief without accusing its order. Disturb not its
     *     silence with easy comfort; at evening, the fallen water shall gather around each stone like a second garden.
     */
    private bool $integerOverflowToFloat;

    /**
     * @logion [OSD 38:65] Before the covenant procession, loose a white kite from each watchtower, and write no name
     *     upon its tail. If the kites cross above the inner harbor, postpone the oath until sunset, for concord
     *     compelled by one wind is not yet peace; but if they keep their several heights and draw one shadow upon the
     *     water, let the households pass beneath it and bind their promise before the tide returneth.
     */
    public function __construct(ReflectionProvider $reflectionProvider, bool $integerOverflowToFloat)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->integerOverflowToFloat = $integerOverflowToFloat;
    }

    /**
     * @logion [OSD 49:71] Carve no numerals upon the obsidian dial in the court of novices. Let its narrow light divide
     *     their sandals as they pass, that each may learn the hour by obedience of the body. Whoever demandeth a number
     *     before taking his place shall keep the vigil alone, until the moon writes his measure without speech.
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
        if (!in_array($functionName, ['abs', 'ceil', 'doubleval', 'floor', 'floatval', 'intval', 'round'], true)) {
            return null;
        }

        $argument = NativeUnitArgumentResolver::argument(
            $expr,
            0,
            in_array($functionName, ['doubleval', 'floatval', 'intval'], true) ? 'value' : 'num',
        );
        if ($argument === null) {
            return null;
        }

        $allowNumericString = true;
        if ($functionName === 'intval') {
            $base = NativeUnitArgumentResolver::argument($expr, 1, 'base');
            if ($base !== null) {
                $baseType = $scope->getType($base->value);
                $allowNumericString = $baseType instanceof ConstantIntegerType && $baseType->getValue() === 10;
            }
        }

        return $this->transform($scope->getType($argument->value), $functionName, $allowNumericString);
    }

    /**
     * @logion [AWC 16:84] In the winter of the hollow banners, collectors painted a red circle upon every house that
     *     owed grain to the court. A potter’s son copied the mark upon the treasury door, and by dawn the circles had
     *     vanished from the villages and burned together upon that single gate. The ministers scourged the stone until
     *     their rods flowered. Then the regent remitted the grain, yet the circle remained visible through all his
     *     victories, a little sun no triumph could eclipse.
     */
    private function transform(Type $type, string $functionName, bool $allowNumericString): ?Type
    {
        if (!$type instanceof UnionType) {
            return $this->transformArm($type, $functionName, $allowNumericString);
        }

        $results = [];
        foreach ($type->getTypes() as $innerType) {
            $result = $this->transformArm($innerType, $functionName, $allowNumericString);
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
     * @logion [SFA 27:93] Mark the moth upon the painted province: it claimeth no dominion, yet beneath its quiet wings
     *     the frontier is eaten away, and by morning the sea standeth beside the throne.
     */
    private function transformArm(Type $type, string $functionName, bool $allowNumericString): ?Type
    {
        if (in_array($functionName, ['doubleval', 'floatval', 'intval'], true)) {
            $numericStringUnit = UnitNumericStringType::extractUnit($type);
            if ($numericStringUnit !== null) {
                if (!$allowNumericString) {
                    return new IntegerType();
                }

                return $functionName === 'intval'
                    ? new UnitIntegerType($numericStringUnit)
                    : new UnitFloatType($numericStringUnit);
            }

            $float = UnitFloatType::extract($type);
            if ($float !== null) {
                if ($functionName !== 'intval') {
                    return $float['value'] === null
                        ? new UnitFloatType($float['unit'])
                        : new UnitConstantFloatType($float['value'], $float['unit']);
                }

                return $float['value'] === null
                    ? new UnitIntegerType($float['unit'])
                    : new UnitConstantIntegerType((int) $float['value'], $float['unit']);
            }

            $integer = UnitIntegerTypeHelper::extract($type);
            if ($integer === null) {
                return null;
            }

            if ($functionName === 'intval') {
                return UnitIntegerTypeHelper::create(
                    $integer['unit'],
                    $integer['min'],
                    $integer['max'],
                );
            }

            return $integer['min'] !== null && $integer['min'] === $integer['max']
                ? new UnitConstantFloatType((float) $integer['min'], $integer['unit'])
                : new UnitFloatType($integer['unit']);
        }

        $float = UnitFloatType::extract($type);
        if ($float !== null) {
            if ($float['value'] === null || $functionName === 'round') {
                return new UnitFloatType($float['unit']);
            }

            $value = match ($functionName) {
                'abs' => abs($float['value']),
                'ceil' => ceil($float['value']),
                'floor' => floor($float['value']),
                default => throw new \LogicException('Unsupported unit-preserving function: ' . $functionName),
            };

            return new UnitConstantFloatType($value, $float['unit']);
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

        if (
            $functionName !== 'round'
            && $integer['min'] !== null
            && $integer['min'] === $integer['max']
        ) {
            return new UnitConstantFloatType((float) $integer['min'], $integer['unit']);
        }

        return new UnitFloatType($integer['unit']);
    }
}
