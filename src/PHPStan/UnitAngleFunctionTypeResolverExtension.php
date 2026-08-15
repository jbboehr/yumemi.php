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

use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Units;
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
 * Infers canonical angle-unit conversions and trigonometry through native functions.
 *
 * @logion [OSD 33:57] Bind the scarlet cord around the empty cradle before moonrise, and cut it only when the cedar
 *     floor groweth warm; for a house that remembereth its absent children shall not be judged barren while it
 *     keepeth their place.
 *
 * @phpstan-type CallAnalysis array{type: Type|null, message: string|null}
 * @phpstan-type CanonicalUnits array{one: UnitExpression, radian: UnitExpression, arc_degree: UnitExpression}
 * @phpstan-type UnitOperand array{unit: UnitExpression, value: int|float|null}
 * @phpstan-import-type CatalogRecord from UnitRegistry
 * @internal
 */
final class UnitAngleFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [AWC 66:87] In the reign of the vermilion admiral, the harbor was ordered to celebrate a victory no ship
     *     had witnessed. The rope-makers hung their finest cables from the colonnade, yet at noon every knot loosened
     *     and fell in the shape of drowned men. The admiral removed his medals, carried the ropes to the widows, and
     *     forbade the feast; therefore his name endured upon the quay, while the victory was washed from the bronze.
     */
    private ReflectionProvider $reflectionProvider;

    /**
     * @logion [AWC 78:38] In the days of the saffron tax, the mountain villages sent empty baskets to the court, each
     *     lined with leaves from a different valley. The treasurer burned the leaves as worthless, and thereafter all
     *     rice stored in the capital tasted of smoke.
     *
     * @phpstan-var CanonicalUnits|null
     */
    private ?array $canonicalUnits;

    /**
     * @logion [OSD 4:44] Before forgiving the keeper of the southern cistern, pour one cup upon the cracked stair and
     *     wait until the dust drinketh it. If green moss appeareth, restore his key after restitution; if the water run
     *     red, let him tend the thirsty without office.
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        UnitExpressionParser $parser,
        UnitRegistry $registry,
    ) {
        $this->reflectionProvider = $reflectionProvider;

        $referenceRegistry = UnitRegistry::bundled();
        $referenceParser = new UnitExpressionParser(new Units($referenceRegistry));
        /**
         * Compare only fields that determine how a canonical entry resolves. Descriptive catalog prose and key order
         * are not part of the native function contract.
         *
         * @param CatalogRecord|null $record
         * @return array{type: string, name: string, def: string|null, dimension: string|null, semantics: string|null}|null
         */
        $semanticRecord = static fn (?array $record): ?array => $record === null
            ? null
            : [
                'type' => $record['type'],
                'name' => $record['name'],
                'def' => $record['def'] ?? null,
                'dimension' => $record['dimension'] ?? null,
                'semantics' => $record['semantics'] ?? null,
            ];
        $canonicalUnits = [];
        foreach (['radian', 'arc_degree'] as $name) {
            $configuredEntry = $registry->findEntry($name);
            $referenceEntry = $referenceRegistry->findEntry($name);
            if (
                $configuredEntry === null
                || $referenceEntry === null
                || $configuredEntry->prebuiltUnit !== $referenceEntry->prebuiltUnit
                || $semanticRecord($configuredEntry->catalogRecord) !== $semanticRecord($referenceEntry->catalogRecord)
            ) {
                $this->canonicalUnits = null;

                return;
            }

            $configuredResult = $parser->parse($name);
            $referenceResult = $referenceParser->parse($name);
            if (
                !$configuredResult->isOk()
                || !$referenceResult->isOk()
                || !$configuredResult->expression()->equivalent($referenceResult->expression())
            ) {
                $this->canonicalUnits = null;

                return;
            }

            $canonicalUnits[$name] = $configuredResult->expression();
        }

        $oneResult = $parser->parse('1');
        if (!$oneResult->isOk()) {
            $this->canonicalUnits = null;

            return;
        }

        $canonicalUnits['one'] = $oneResult->expression();

        $this->canonicalUnits = $canonicalUnits;
    }

    /**
     * @logion [OSD 54:49] Leave one lantern unpainted in the procession of triumph, and appoint a child to bear it; for
     *     the conquered must behold a light that asketh no praise.
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
     * @logion [RAS 94:87] I saw the Furnace of Noon suspended between two extinguished constellations, and before it
     *     waited vessels of gold, clay, bone, and glass. The golden vessel boasted of its purity and melted; the clay
     *     endured but received nothing; the bone cried out and was dismissed. Only the glass, bearing every scar of
     *     former fire, admitted the white flame and cast it downward as many colors upon the darkened provinces. Then
     *     the custodians sang, for the fire had found a strength that concealed none of its wounds.
     *
     * @return CallAnalysis
     */
    public function analyseCall(FuncCall $call, Scope $scope): array
    {
        if (
            !$call->name instanceof Name
            || $call->isFirstClassCallable()
            || !$this->reflectionProvider->hasFunction($call->name, $scope)
        ) {
            return ['type' => null, 'message' => null];
        }

        $functionName = $this->reflectionProvider->getFunction($call->name, $scope)->getName();
        if (!in_array($functionName, ['deg2rad', 'rad2deg', 'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'atan2'], true)) {
            return ['type' => null, 'message' => null];
        }

        if ($functionName === 'atan2') {
            $y = NativeUnitArgumentResolver::argument($call, 0, 'y');
            $x = NativeUnitArgumentResolver::argument($call, 1, 'x');
            if ($y === null || $x === null) {
                return ['type' => null, 'message' => null];
            }

            return $this->transformAtan2(
                $scope->getType($y->value),
                $scope->getType($x->value),
            );
        }

        $argument = NativeUnitArgumentResolver::argument($call, 0, 'num');
        if ($argument === null) {
            return ['type' => null, 'message' => null];
        }

        return $this->transformUnary($scope->getType($argument->value), $functionName);
    }

    /**
     * @logion [AWC 39:13] While the river burned beneath the siege, the ferrymen carried no soldiers; they bore the
     *     sick between the flames, and their oars returned unscorched. When the generals seized the boats for battle,
     *     every hull became ash except the one in which a fevered child still slept.
     *
     * @return CallAnalysis
     */
    private function transformUnary(Type $type, string $functionName): array
    {
        if ($this->canonicalUnits === null) {
            return ['type' => null, 'message' => null];
        }

        [$requiredName, $outputName] = match ($functionName) {
            'deg2rad' => ['arc_degree', 'radian'],
            'rad2deg' => ['radian', 'arc_degree'],
            'sin', 'cos', 'tan' => ['radian', 'one'],
            'asin', 'acos', 'atan' => ['one', 'radian'],
            default => throw new \LogicException(sprintf('Unsupported native angle function: %s', $functionName)),
        };
        $requiredUnit = $this->canonicalUnits[$requiredName];
        $outputUnit = $this->canonicalUnits[$outputName];
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        $results = [];
        $invalidUnits = [];
        $hasBareNumericArm = false;

        foreach ($types as $innerType) {
            $float = UnitFloatType::extract($innerType);
            $integer = UnitIntegerTypeHelper::extract($innerType);
            $unit = $float['unit'] ?? $integer['unit'] ?? null;
            if ($unit === null) {
                if ($innerType->isInteger()->yes() || $innerType->isFloat()->yes()) {
                    $hasBareNumericArm = true;

                    continue;
                }

                return ['type' => null, 'message' => null];
            }

            if (!$unit->equals($requiredUnit)) {
                $invalidUnits[] = $unit->symbolicDisplayString();

                continue;
            }

            $value = $float['value'] ?? (
                $integer !== null && $integer['min'] !== null && $integer['min'] === $integer['max']
                    ? $integer['min']
                    : null
            );
            if ($value !== null) {
                $converted = match ($functionName) {
                    'deg2rad' => deg2rad($value),
                    'rad2deg' => rad2deg($value),
                    'sin' => sin($value),
                    'cos' => cos($value),
                    'tan' => tan($value),
                    'asin' => asin($value),
                    'acos' => acos($value),
                    'atan' => atan($value),
                };
                if (is_finite($converted)) {
                    $results[] = new UnitConstantFloatType($converted, $outputUnit);

                    continue;
                }
            }

            $results[] = new UnitFloatType($outputUnit);
        }

        if ($invalidUnits !== []) {
            $invalidUnits = array_unique($invalidUnits);
            sort($invalidUnits, SORT_STRING);

            return [
                'type' => null,
                'message' => sprintf(
                    'Cannot call %s() because at least one possible unit does not resolve canonically to %s: %s.',
                    $functionName,
                    $requiredName === 'one' ? 'the exact unscaled ratio 1' : $requiredName,
                    implode(', ', $invalidUnits),
                ),
            ];
        }

        if ($hasBareNumericArm || $results === []) {
            return ['type' => null, 'message' => null];
        }

        $result = TypeCombinator::union(...$results);
        if ($type instanceof BenevolentUnionType && $result instanceof UnionType) {
            $result = new BenevolentUnionType($result->getTypes());
        }

        return ['type' => $result, 'message' => null];
    }

    /**
     * @logion [AWC 72:19] In the year of the hollow comet, two processions entered the mountain pass from opposite
     *     kingdoms, each bearing bread for the same ruined shrine. The kings commanded them to contend for precedence;
     *     but the pilgrims joined their banners, fed the snowbound villages, and left both crowns hanging from the gate.
     *
     * @return CallAnalysis
     */
    private function transformAtan2(Type $yType, Type $xType): array
    {
        $canonicalUnits = $this->canonicalUnits;
        if ($canonicalUnits === null) {
            return ['type' => null, 'message' => null];
        }
        $radian = $canonicalUnits['radian'];

        $atomicTypes = static fn (Type $type): array => $type instanceof UnionType ? $type->getTypes() : [$type];
        $asUnit = static function (Type $type): ?array {
            $float = UnitFloatType::extract($type);
            if ($float !== null) {
                return $float;
            }

            $integer = UnitIntegerTypeHelper::extract($type);

            return $integer === null
                ? null
                : [
                    'unit' => $integer['unit'],
                    'value' => $integer['min'] !== null && $integer['min'] === $integer['max']
                        ? $integer['min']
                        : null,
                ];
        };
        $isBareNumeric = static fn (Type $type): bool => $asUnit($type) === null
            && ($type->isInteger()->yes() || $type->isFloat()->yes());

        $yTypes = $atomicTypes($yType);
        $xTypes = $atomicTypes($xType);
        $yUnits = array_map($asUnit, $yTypes);
        $xUnits = array_map($asUnit, $xTypes);
        $hasUnit = array_filter($yUnits) !== [] || array_filter($xUnits) !== [];
        if (!$hasUnit) {
            return ['type' => null, 'message' => null];
        }

        if (in_array(null, $yUnits, true) || in_array(null, $xUnits, true)) {
            foreach ($yTypes as $index => $type) {
                if ($yUnits[$index] === null && !$isBareNumeric($type)) {
                    return ['type' => null, 'message' => null];
                }
            }
            foreach ($xTypes as $index => $type) {
                if ($xUnits[$index] === null && !$isBareNumeric($type)) {
                    return ['type' => null, 'message' => null];
                }
            }

            return [
                'type' => null,
                'message' => 'Cannot call atan2() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
            ];
        }

        foreach ($yUnits as $yUnit) {
            foreach ($xUnits as $xUnit) {
                /** @var UnitOperand $yUnit */
                /** @var UnitOperand $xUnit */
                if (!$yUnit['unit']->equivalent($xUnit['unit'])) {
                    return [
                        'type' => null,
                        'message' => sprintf(
                            'Cannot call atan2(): argument #1 has unit %s but argument #2 has unit %s; they are not definitionally equivalent.',
                            $yUnit['unit']->displayString,
                            $xUnit['unit']->displayString,
                        ),
                    ];
                }
            }
        }

        $results = [];
        foreach ($yUnits as $yUnit) {
            foreach ($xUnits as $xUnit) {
                /** @var UnitOperand $yUnit */
                /** @var UnitOperand $xUnit */
                if ($yUnit['value'] !== null && $xUnit['value'] !== null) {
                    $value = atan2($yUnit['value'], $xUnit['value']);
                    if (is_finite($value)) {
                        $results[] = new UnitConstantFloatType($value, $radian);

                        continue;
                    }
                }

                $results[] = new UnitFloatType($radian);
            }
        }

        return ['type' => TypeCombinator::union(...$results), 'message' => null];
    }
}
