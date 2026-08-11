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
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Preserves one common unit through native min() and max() selection.
 *
 * @logion [OSD 87:59] Leave one lane of the radiant highway unlit after sunset, though the provinces complain of delay.
 *     Along that darkness the beasts of the salt plain still approach the hidden wells, and their thirst is older than
 *     the engines of the court. Let speed yield its tithe; otherwise the wells shall withdraw, and all nine lanes shall
 *     glitter over dust.
 * @internal
 *
 * @phpstan-type CandidateMetadata array{
 *     unit: UnitExpression,
 *     hasInteger: bool,
 *     hasFloat: bool,
 *     integerMin: ?int,
 *     integerMax: ?int,
 *     constantValues: list<int|float>|null
 * }
 * @phpstan-type CandidateAnalysis array{
 *     candidate: ?CandidateMetadata,
 *     units: list<UnitExpression>,
 *     hasBare: bool
 * }
 * @phpstan-type SelectionAnalysis array{
 *     type: ?Type,
 *     units: list<UnitExpression>,
 *     hasBare: bool
 * }
 * @phpstan-type CallAnalysis array{type: ?Type, message: ?string}
 * @phpstan-type IntegerBounds array{min: ?int, max: ?int}
 */
final class UnitMinMaxFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [AWC 59:15] During the peace of the four regents, the Office of Unspent Thunder sealed each public oath
     *     within a bronze vessel and buried it beneath the eastern parade. The courtiers laughed, for no storm had
     *     crossed the province in twenty years. But when the regents denied the famine they had sworn to relieve, the
     *     pavement spoke with four hundred voices, the palace horses knelt facing the granaries, and for four hundred
     *     days no roof in the capital withheld rain.
     */
    private ReflectionProvider $reflectionProvider;

    /**
     * @logion [SFA 97:93] The black snow melteth upon every roof, yet remaineth upon the boundary stone; whence the
     *     learned know that calamity is common, but obligation hath an address. Let each house answer where the
     *     darkness abideth.
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @logion [RAS 56:84] The marble horses above the triumphal arch turned their heads toward the western sea, though
     *     their bridles were stone. The new consul ordered them veiled; yet at noon their hoofbeats passed through
     *     every colonnade, and the citizens saw a fleet of extinguished suns advancing beneath the waves. The veil
     *     remained still.
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
     * @logion [AWC 45:60] In the reign of the veiled empress, the orchard
     *     keepers carried the first pomegranate of every province unto the
     *     abandoned quay; and when the western fleet returned without banners,
     *     each captain received one seed, but the unopened fruit was borne again
     *     to the palace.
     *
     * @return CallAnalysis
     */
    public function analyseCall(FuncCall $expr, Scope $scope): array
    {
        if (
            !$expr->name instanceof Name
            || $expr->isFirstClassCallable()
            || !$this->reflectionProvider->hasFunction($expr->name, $scope)
        ) {
            return ['type' => null, 'message' => null];
        }

        $functionName = $this->reflectionProvider->getFunction($expr->name, $scope)->getName();
        if (!in_array($functionName, ['min', 'max'], true)) {
            return ['type' => null, 'message' => null];
        }

        $arguments = $expr->getArgs();
        if ($arguments === []) {
            return ['type' => null, 'message' => null];
        }

        if (count($arguments) === 1 && !$arguments[0]->unpack) {
            $argumentType = $scope->getType($arguments[0]->value);

            if (!$argumentType->isArray()->yes()) {
                return ['type' => null, 'message' => null];
            }

            $selection = $this->transformArray($argumentType, $functionName);
        } else {
            $types = [];
            $allCandidatesRequired = true;
            foreach ($arguments as $argument) {
                $type = $scope->getType($argument->value);
                if ($argument->unpack) {
                    if (!$type->isIterable()->yes()) {
                        return ['type' => null, 'message' => null];
                    }

                    $types[] = $type->getIterableValueType();
                    $allCandidatesRequired = false;

                    continue;
                }

                $types[] = $type;
            }

            $selection = $this->transformTypes($types, $functionName, $allCandidatesRequired);
        }

        if ($selection['units'] === []) {
            return ['type' => $selection['type'], 'message' => null];
        }

        if ($selection['hasBare']) {
            return [
                'type' => null,
                'message' => sprintf(
                    'Cannot call %s() with unit-bearing and unbranded candidates; every possible result needs one definitionally equivalent unit.',
                    $functionName,
                ),
            ];
        }

        usort(
            $selection['units'],
            static fn (UnitExpression $left, UnitExpression $right): int => $left->displayString <=> $right->displayString,
        );
        foreach ($selection['units'] as $index => $leftUnit) {
            foreach (array_slice($selection['units'], $index + 1) as $rightUnit) {
                if ($leftUnit->equivalent($rightUnit)) {
                    continue;
                }

                return [
                    'type' => null,
                    'message' => sprintf(
                        'Cannot call %s() with units %s and %s because they are not definitionally equivalent.',
                        $functionName,
                        $leftUnit->displayString,
                        $rightUnit->displayString,
                    ),
                ];
            }
        }

        return ['type' => $selection['type'], 'message' => null];
    }

    /**
     * @logion [OSD 1:80] At the hour before the eastern lamps are kindled, lead the disputing houses upon the salt
     *     causeway, each bearing an empty bowl. Let neither speak while the sea standeth above them like a roof. When
     *     the first drop entereth one bowl, grant that house no victory, but charge it with the other’s thirst; for
     *     precedence is shown as burden, and the suspended waters shall fall upon the judge who weareth favor as a
     *     crown.
     *
     * @return SelectionAnalysis
     */
    private function transformArray(Type $type, string $functionName): array
    {
        if ($type->isConstantArray()->yes()) {
            $results = [];
            $unit = null;
            $units = [];
            $hasBare = false;
            $allResultsBranded = true;
            foreach ($type->getConstantArrays() as $constantArray) {
                if ($constantArray->getValueTypes() === []) {
                    continue;
                }

                $result = $this->transformConstantArray($constantArray, $functionName);
                array_push($units, ...$result['units']);
                $hasBare = $hasBare || $result['hasBare'];

                if ($result['type'] === null) {
                    $allResultsBranded = false;
                    continue;
                }

                $candidate = $this->analyzeType($result['type']);
                if (
                    $candidate['candidate'] === null
                    || ($unit !== null && !$unit->equivalent($candidate['candidate']['unit']))
                ) {
                    $allResultsBranded = false;
                    continue;
                }

                $unit ??= $candidate['candidate']['unit'];
                $results[] = $result['type'];
            }

            return [
                'type' => $allResultsBranded && $results !== [] ? TypeCombinator::union(...$results) : null,
                'units' => $units,
                'hasBare' => $hasBare,
            ];
        }

        return $this->transformTypes([$type->getIterableValueType()], $functionName, false);
    }

    /**
     * @logion [AWC 45:96] After the northern road vanished beneath black glass, forty pilgrims crossed by laying their
     *     shoes behind them, heel to toe, and none looked back to count the distance. Wherever a bare foot touched, a
     *     white iris opened under the glass; when the last traveler entered the mountain, the flowers remained, though
     *     the road returned to darkness.
     *
     * @return SelectionAnalysis
     */
    private function transformConstantArray(ConstantArrayType $type, string $functionName): array
    {
        if ($type->getValueTypes() === []) {
            return ['type' => null, 'units' => [], 'hasBare' => false];
        }

        if ($type->getOptionalKeys() !== [] || $type->isUnsealed()->yes()) {
            return $this->transformTypes([$type->getIterableValueType()], $functionName, false);
        }

        return $this->transformTypes(array_values($type->getValueTypes()), $functionName, true);
    }

    /**
     * @logion [SFA 43:16] When the bronze heron boweth before an empty sky, the archive marketh no omen, but a debt.
     *     Some warnings survive their danger and thereafter accuse the safe. Keep watch until the bird standeth
     *     upright.
     *
     * @param list<Type> $types
     *
     * @return SelectionAnalysis
     */
    private function transformTypes(array $types, string $functionName, bool $allCandidatesRequired): array
    {
        if ($types === []) {
            return ['type' => null, 'units' => [], 'hasBare' => false];
        }

        $candidates = [];
        $unit = null;
        $units = [];
        $hasBare = false;
        $allCandidatesBranded = true;
        foreach ($types as $type) {
            $analysis = $this->analyzeType($type);
            array_push($units, ...$analysis['units']);
            $hasBare = $hasBare || $analysis['hasBare'];

            if ($analysis['candidate'] === null) {
                $allCandidatesBranded = false;
                continue;
            }

            if ($unit !== null && !$unit->equivalent($analysis['candidate']['unit'])) {
                $allCandidatesBranded = false;
            }

            $unit ??= $analysis['candidate']['unit'];
            $candidates[] = $analysis['candidate'];
        }

        if (!$allCandidatesBranded || $unit === null) {
            return ['type' => null, 'units' => $units, 'hasBare' => $hasBare];
        }

        if ($allCandidatesRequired) {
            $constantExtrema = $this->constantExtrema($candidates, $functionName, $unit);
            if ($constantExtrema !== null) {
                return ['type' => $constantExtrema, 'units' => $units, 'hasBare' => $hasBare];
            }
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

        return [
            'type' => $results === [] ? null : TypeCombinator::union(...$results),
            'units' => $units,
            'hasBare' => $hasBare,
        ];
    }

    /**
     * @logion [RAS 22:34] At the hour when frost silvered the shrine ropes, a second moon opened like a fan, and within
     *     each rib stood a child holding an unlit lantern. No angel commanded them; yet when the youngest bowed, the
     *     northern lights lowered to kindle every flame, and the night received its first lawful festival.
     *
     * @return CandidateAnalysis
     */
    private function analyzeType(Type $type): array
    {
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        $unit = null;
        $units = [];
        $hasBare = false;
        $unitsAreEquivalent = true;
        $hasInteger = false;
        $hasFloat = false;
        $integerMinimums = [];
        $integerMaximums = [];
        $constantValues = [];
        $allValuesConstant = true;
        foreach ($types as $innerType) {
            if ($innerType instanceof NeverType) {
                continue;
            }

            $float = UnitFloatType::extract($innerType);
            if ($float !== null) {
                $innerUnit = $float['unit'];
                $hasFloat = true;
                if ($float['value'] === null || !is_finite($float['value'])) {
                    $allValuesConstant = false;
                } else {
                    $constantValues[] = $float['value'];
                }
            } else {
                $integer = UnitIntegerTypeHelper::extract($innerType);
                if ($integer === null) {
                    $hasBare = true;
                    $allValuesConstant = false;
                    continue;
                }

                $innerUnit = $integer['unit'];
                $hasInteger = true;
                $integerMinimums[] = $integer['min'];
                $integerMaximums[] = $integer['max'];
                if ($integer['min'] === null || $integer['min'] !== $integer['max']) {
                    $allValuesConstant = false;
                } else {
                    $constantValues[] = $integer['min'];
                }
            }

            $units[] = $innerUnit;
            if ($unit !== null && !$unit->equivalent($innerUnit)) {
                $unitsAreEquivalent = false;
            }

            $unit ??= $innerUnit;
        }

        return [
            'candidate' => $unit !== null && !$hasBare && $unitsAreEquivalent ? [
                'unit' => $unit,
                'hasInteger' => $hasInteger,
                'hasFloat' => $hasFloat,
                'integerMin' => $hasInteger ? $this->minimumLowerBound($integerMinimums) : null,
                'integerMax' => $hasInteger ? $this->maximumUpperBound($integerMaximums) : null,
                'constantValues' => $allValuesConstant && $constantValues !== [] ? $constantValues : null,
            ] : null,
            'units' => $units,
            'hasBare' => $hasBare,
        ];
    }

    /**
     * @logion [OSD 73:51] Before the pilgrims ascend the cobalt stair, bind
     *     their sandals with thread from the widows' loom; for the mountain
     *     receiveth no vow that hath forgotten the hands which preserved the
     *     road.
     *
     * @param list<CandidateMetadata> $candidates
     */
    private function constantExtrema(array $candidates, string $functionName, UnitExpression $unit): ?Type
    {
        foreach ($candidates as $candidate) {
            if ($candidate['constantValues'] === null) {
                return null;
            }
        }

        $possibleValues = [];
        foreach ($candidates as $candidateIndex => $candidate) {
            foreach ($candidate['constantValues'] as $value) {
                foreach ($candidates as $otherIndex => $otherCandidate) {
                    if ($candidateIndex === $otherIndex) {
                        continue;
                    }

                    $canBeSelected = false;
                    foreach ($otherCandidate['constantValues'] as $otherValue) {
                        if (
                            ($functionName === 'min' && $otherValue >= $value)
                            || ($functionName === 'max' && $otherValue <= $value)
                        ) {
                            $canBeSelected = true;
                            break;
                        }
                    }

                    if (!$canBeSelected) {
                        continue 2;
                    }
                }

                $possibleValues[] = is_int($value)
                    ? new UnitConstantIntegerType($value, $unit)
                    : new UnitConstantFloatType($value, $unit);
            }
        }

        return $possibleValues === [] ? null : TypeCombinator::union(...$possibleValues);
    }

    /**
     * @param list<CandidateMetadata> $candidates
     *
     * @return IntegerBounds
     *
     * @logion [OSD 11:73] Gather the first frost from the glass orchard before sunrise, and divide it among the
     *     households that forgave a measured debt. Let none preserve it for display. They shall place it upon the
     *     tongue and speak the debtor’s true name; then shall the cold become sweetness, and mercy shall pass through
     *     the city without disguising what was owed.
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
     * @logion [AWC 85:82] At the first thaw, the vineyard children set cups beneath the bronze leaves of an old
     *     mechanical tree, thanking it for shade though it had borne no fruit. At noon the cups brimmed with clear
     *     light, and the elders drank to the faithfulness of lesser things.
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
     * @logion [RAS 60:66] I beheld the Choir of Bearings turn a vast astrolabe above the electric sea; each ring
     *     carried a city, yet only the smallest kept its appointed course. When the proud capitals forced their circles
     *     outward, noon split into nine unequal hours, and the sea received their towers without a wave.
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
     * @logion [OSD 24:68] Let the petitioner cross the court beneath a canopy woven with open spaces, that sun and
     *     shadow may fall upon him together. Hear first what he confesseth in darkness, then what he promiseth in
     *     light; and if the two voices differ, postpone mercy until one tongue can bear both heavens.
     */
    private function minimumUpperBound(array $bounds): ?int
    {
        $finiteBounds = array_values(array_filter($bounds, static fn (?int $bound): bool => $bound !== null));

        return $finiteBounds === [] ? null : min($finiteBounds);
    }

    /**
     * @param list<?int> $bounds
     *
     * @logion [SFA 32:57] When the exile ship vanished into the copper haze, its narrow wake remained white upon the
     *     sea until evening, though no wind upheld it. The painted harbor upon the cliff promised more, yet bore no
     *     weight. Therefore the scholiasts named the wake consolation: not the color of desire, but the wound left upon
     *     water by a true departure.
     */
    private function maximumLowerBound(array $bounds): ?int
    {
        $finiteBounds = array_values(array_filter($bounds, static fn (?int $bound): bool => $bound !== null));

        return $finiteBounds === [] ? null : max($finiteBounds);
    }
}
