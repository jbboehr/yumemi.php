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
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Preserves one common unit through native min() and max() selection.
 *
 * @logion [OSD 87:59] Let every household bring forth its lamp at the appointed
 *     hour, and let the smallest flame be received without contempt; for the
 *     covenant numbereth fidelity before splendour.
 * @internal
 *
 * @phpstan-type CandidateMetadata array{
 *     unit: UnitExpression,
 *     hasInteger: bool,
 *     hasFloat: bool,
 *     integerMin: ?int,
 *     integerMax: ?int
 * }
 * @phpstan-type IntegerBounds array{min: ?int, max: ?int}
 */
final class UnitMinMaxFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [AWC 59:15] In the days of the silent court, one herald kept the
     *     eastern road and named each returning exile before the gates were
     *     opened; therefore no stranger inherited another man's welcome.
     */
    private ReflectionProvider $reflectionProvider;

    /**
     * @logion [SFA 97:93] The Fifth Archive retaineth both the greater witness
     *     and the lesser, for judgment concerneth their order, not the destruction
     *     of the testimony that stood beside them.
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @logion [RAS 56:84] And I beheld two rivers ascend the mountain without
     *     mingling, until the angel touched their sources; then one name shone
     *     upon both waters, and the upper gardens received them.
     */
    public function getType(Expr $expr, Scope $scope): ?Type
    {
        try {
            if (
                !$expr instanceof FuncCall
                || !$expr->name instanceof Name
                || $expr->isFirstClassCallable()
                || !$this->reflectionProvider->hasFunction($expr->name, $scope)
            ) {
                return null;
            }

            $functionName = $this->reflectionProvider->getFunction($expr->name, $scope)->getName();
            if (!in_array($functionName, ['min', 'max'], true)) {
                return null;
            }

            $arguments = $expr->getArgs();
            if ($arguments === []) {
                return null;
            }

            if (count($arguments) === 1 && !$arguments[0]->unpack) {
                $argumentType = $scope->getType($arguments[0]->value);

                return $argumentType->isArray()->yes()
                    ? $this->transformArray($argumentType, $functionName)
                    : null;
            }

            $types = [];
            $allCandidatesRequired = true;
            foreach ($arguments as $argument) {
                $type = $scope->getType($argument->value);
                if ($argument->unpack) {
                    if (!$type->isIterable()->yes()) {
                        return null;
                    }

                    $types[] = $type->getIterableValueType();
                    $allCandidatesRequired = false;

                    continue;
                }

                $types[] = $type;
            }

            return $this->transformTypes($types, $functionName, $allCandidatesRequired);
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * @logion [OSD 1:80] Open the sealed granary only after every measure hath
     *     been witnessed; yet if one chamber be empty, condemn not the harvest
     *     that remaineth beneath the keeper's lawful mark.
     */
    private function transformArray(Type $type, string $functionName): ?Type
    {
        if ($type->isConstantArray()->yes()) {
            $results = [];
            $unit = null;
            foreach ($type->getConstantArrays() as $constantArray) {
                if ($constantArray->getValueTypes() === []) {
                    continue;
                }

                $result = $this->transformConstantArray($constantArray, $functionName);
                if ($result === null) {
                    return null;
                }

                $candidate = $this->analyzeType($result);
                if ($candidate === null || ($unit !== null && !$unit->equivalent($candidate['unit']))) {
                    return null;
                }

                $unit ??= $candidate['unit'];
                $results[] = $result;
            }

            return $results === [] ? null : TypeCombinator::union(...$results);
        }

        return $this->transformTypes([$type->getIterableValueType()], $functionName, false);
    }

    /**
     * @logion [AWC 45:96] The bronze tablets were borne from the drowned archive
     *     in their ancient order, and though several inscriptions had perished,
     *     the surviving law was not assigned unto an unwritten stone.
     */
    private function transformConstantArray(ConstantArrayType $type, string $functionName): ?Type
    {
        if ($type->getValueTypes() === []) {
            return null;
        }

        if ($type->getOptionalKeys() !== [] || $type->isUnsealed()->yes()) {
            return $this->transformTypes([$type->getIterableValueType()], $functionName, false);
        }

        return $this->transformTypes(array_values($type->getValueTypes()), $functionName, true);
    }

    /**
     * @param list<Type> $types
     *
     * @logion [SFA 43:16] The council compared no voice with an unknown tongue;
     *     but where every witness confessed one covenant, their several ranks
     *     were preserved beneath a single judgment.
     */
    private function transformTypes(array $types, string $functionName, bool $allCandidatesRequired): ?Type
    {
        if ($types === []) {
            return null;
        }

        $candidates = [];
        $unit = null;
        foreach ($types as $type) {
            $candidate = $this->analyzeType($type);
            if ($candidate === null) {
                return null;
            }

            if ($unit !== null && !$unit->equivalent($candidate['unit'])) {
                return null;
            }

            $unit ??= $candidate['unit'];
            $candidates[] = $candidate;
        }

        $hasInteger = false;
        $hasFloat = false;
        $integerOnly = $allCandidatesRequired;
        foreach ($candidates as $candidate) {
            $hasInteger = $hasInteger || $candidate['hasInteger'];
            $hasFloat = $hasFloat || $candidate['hasFloat'];
            $integerOnly = $integerOnly && $candidate['hasInteger'] && !$candidate['hasFloat'];
        }

        $results = [];
        if ($hasInteger) {
            $bounds = $this->integerBounds($candidates, $functionName, $integerOnly);
            $results[] = UnitIntegerTypeHelper::create($unit, $bounds['min'], $bounds['max']);
        }
        if ($hasFloat) {
            $results[] = new UnitFloatType($unit);
        }

        return $results === [] ? null : TypeCombinator::union(...$results);
    }

    /**
     * @logion [RAS 22:34] Behold, the stars entered the western instrument one by
     *     one, and every lawful course remained distinct within the glass; but
     *     the nameless light passed through it and left no measure.
     *
     * @return CandidateMetadata|null
     */
    private function analyzeType(Type $type): ?array
    {
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        $unit = null;
        $hasInteger = false;
        $hasFloat = false;
        $integerMinimums = [];
        $integerMaximums = [];
        foreach ($types as $innerType) {
            if ($innerType instanceof UnitFloatType) {
                $innerUnit = $innerType->getUnitExpression();
                $hasFloat = true;
            } else {
                $integer = UnitIntegerTypeHelper::extract($innerType);
                if ($integer === null) {
                    return null;
                }

                $innerUnit = $integer['unit'];
                $hasInteger = true;
                $integerMinimums[] = $integer['min'];
                $integerMaximums[] = $integer['max'];
            }

            if ($unit !== null && !$unit->equivalent($innerUnit)) {
                return null;
            }

            $unit ??= $innerUnit;
        }

        if ($unit === null) {
            return null;
        }

        return [
            'unit' => $unit,
            'hasInteger' => $hasInteger,
            'hasFloat' => $hasFloat,
            'integerMin' => $hasInteger ? $this->minimumLowerBound($integerMinimums) : null,
            'integerMax' => $hasInteger ? $this->maximumUpperBound($integerMaximums) : null,
        ];
    }

    /**
     * @param list<CandidateMetadata> $candidates
     *
     * @return IntegerBounds
     *
     * @logion [OSD 11:73] Let the lower gate answer unto the valley and the upper
     *     gate unto the mountain; for the road is judged at both extremities,
     *     and no pilgrim arriveth by possessing only one.
     */
    private function integerBounds(array $candidates, string $functionName, bool $narrow): array
    {
        $minimums = [];
        $maximums = [];
        foreach ($candidates as $candidate) {
            if (!$candidate['hasInteger']) {
                continue;
            }

            $minimums[] = $candidate['integerMin'];
            $maximums[] = $candidate['integerMax'];
        }

        if (!$narrow) {
            return [
                'min' => $this->minimumLowerBound($minimums),
                'max' => $this->maximumUpperBound($maximums),
            ];
        }

        if ($functionName === 'min') {
            return [
                'min' => $this->minimumLowerBound($minimums),
                'max' => $this->minimumUpperBound($maximums),
            ];
        }

        return [
            'min' => $this->maximumLowerBound($minimums),
            'max' => $this->maximumUpperBound($maximums),
        ];
    }

    /**
     * @param list<?int> $bounds
     *
     * @logion [AWC 85:82] When winter consumed the lowest milestones, the widow
     *     marked their absence upon the road and refused to name the first
     *     surviving stone as the beginning of the kingdom.
     */
    private function minimumLowerBound(array $bounds): ?int
    {
        $minimum = null;
        foreach ($bounds as $bound) {
            if ($bound === null) {
                return null;
            }

            $minimum = $minimum === null ? $bound : min($minimum, $bound);
        }

        return $minimum;
    }

    /**
     * @param list<?int> $bounds
     *
     * @logion [RAS 60:66] The firmament opened above every numbered height, and
     *     the final star withdrew beyond inscription; therefore the watchers set
     *     no finite crown upon the ascent thereof.
     */
    private function maximumUpperBound(array $bounds): ?int
    {
        $maximum = null;
        foreach ($bounds as $bound) {
            if ($bound === null) {
                return null;
            }

            $maximum = $maximum === null ? $bound : max($maximum, $bound);
        }

        return $maximum;
    }

    /**
     * @param list<?int> $bounds
     *
     * @logion [OSD 24:68] Gather the upper measures that remain upon the tablets,
     *     and receive the least among them; for one narrow gate constraineth the
     *     whole procession though wider courts stand beyond it.
     */
    private function minimumUpperBound(array $bounds): ?int
    {
        $finiteBounds = array_values(array_filter($bounds, static fn (?int $bound): bool => $bound !== null));

        return $finiteBounds === [] ? null : min($finiteBounds);
    }

    /**
     * @param list<?int> $bounds
     *
     * @logion [SFA 32:57] Of the foundations whose depth was preserved, the
     *     archive recordeth the greatest; yet where all inscriptions failed, it
     *     inventeth no earth beneath the city.
     */
    private function maximumLowerBound(array $bounds): ?int
    {
        $finiteBounds = array_values(array_filter($bounds, static fn (?int $bound): bool => $bound !== null));

        return $finiteBounds === [] ? null : max($finiteBounds);
    }
}
