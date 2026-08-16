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

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\PHPStan\UnitArrayAggregationFunctionTypeResolverExtension;
use jbboehr\Yumemi\PHPStan\UnitConstantFloatType;
use jbboehr\Yumemi\PHPStan\UnitConstantIntegerType;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use jbboehr\Yumemi\PHPStan\UnitNumericStringType;
use jbboehr\Yumemi\PHPStan\UnitOperatorTypeSpecifyingExtension;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitArrayAggregationFunctionTypeResolverExtensionTest extends TestCase
{
    public function testUnsupportedFunctionIsNotClaimed(): void
    {
        $array = new ArrayType(new IntegerType(), new UnitIntegerType($this->unit('meter')));

        self::assertNull($this->resolve('array_reduce', $array));
    }

    public function testMalformedAndUnpackedCallsAreNotClaimed(): void
    {
        $scope = self::createStub(Scope::class);
        self::assertNull($this->extension('array_sum', true)->getType(
            new FuncCall(new Name('array_sum')),
            $scope,
        ));

        $array = new ArrayType(new IntegerType(), new UnitIntegerType($this->unit('meter')));
        self::assertNull($this->resolve('array_sum', $array, unpack: true));
    }

    public function testLiteralEmptyAndNestedBrandsAreNotClaimed(): void
    {
        self::assertNull($this->resolve('array_sum', ConstantArrayTypeBuilder::createEmpty()->getArray()));

        $nested = new ArrayType(
            new IntegerType(),
            new ArrayType(new IntegerType(), new UnitIntegerType($this->unit('meter'))),
        );
        self::assertNull($this->resolve('array_sum', $nested));
    }

    public function testConstantIntegerAndFloatArraysRetainExactSums(): void
    {
        $meter = $this->unit('meter');
        $integers = ConstantArrayTypeBuilder::createEmpty();
        $integers->setOffsetValueType(null, new UnitConstantIntegerType(2, $meter));
        $integers->setOffsetValueType(null, new UnitConstantIntegerType(3, $meter));

        self::assertSame(
            "5&unit_int<'meter'>",
            $this->describe($this->resolve('array_sum', $integers->getArray())),
        );

        $mixed = ConstantArrayTypeBuilder::createEmpty();
        $mixed->setOffsetValueType(null, new UnitConstantIntegerType(2, $meter));
        $mixed->setOffsetValueType(null, new UnitConstantFloatType(1.5, $meter));

        self::assertSame(
            "3.5&unit_float<'meter'>",
            $this->describe($this->resolve('array_sum', $mixed->getArray())),
        );
    }

    public function testConstantIntegerOverflowUsesTheConfiguredCarrierPolicy(): void
    {
        $meter = $this->unit('meter');
        $array = ConstantArrayTypeBuilder::createEmpty();
        $array->setOffsetValueType(null, new UnitConstantIntegerType(PHP_INT_MAX, $meter));
        $array->setOffsetValueType(null, new UnitConstantIntegerType(1, $meter));

        self::assertSame(
            "unit_float<'meter'>",
            $this->describe($this->resolve('array_sum', $array->getArray())),
        );
        self::assertSame(
            "unit_int<'meter'>",
            $this->describe($this->resolve('array_sum', $array->getArray(), integerOverflowToFloat: false)),
        );
    }

    public function testKnownIntegerRangesAreAdded(): void
    {
        $meter = $this->unit('meter');
        $array = ConstantArrayTypeBuilder::createEmpty();
        $array->setOffsetValueType(
            null,
            UnitIntegerTypeHelper::create($meter, 1, 3),
        );
        $array->setOffsetValueType(
            null,
            UnitIntegerTypeHelper::create($meter, 4, 5),
        );

        self::assertSame(
            "unit_int<'meter'>&int<5, 8>",
            $this->describe($this->resolve('array_sum', $array->getArray())),
        );
    }

    public function testOptionalAndEmptyAlternativesUseTheBrandedAdditiveIdentity(): void
    {
        $meter = $this->unit('meter');
        $optional = ConstantArrayTypeBuilder::createEmpty();
        $optional->setOffsetValueType(
            new ConstantIntegerType(0),
            new UnitConstantIntegerType(3, $meter),
            true,
        );

        self::assertSame(
            "0&unit_int<'meter'>|3&unit_int<'meter'>",
            $this->describe($this->resolve('array_sum', $optional->getArray())),
        );

        $nonEmpty = ConstantArrayTypeBuilder::createEmpty();
        $nonEmpty->setOffsetValueType(null, new UnitConstantIntegerType(3, $meter));

        self::assertSame(
            "0&unit_int<'meter'>|3&unit_int<'meter'>",
            $this->describe($this->resolve('array_sum', new UnionType([
                ConstantArrayTypeBuilder::createEmpty()->getArray(),
                $nonEmpty->getArray(),
            ]))),
        );
    }

    public function testGeneralIntegerAndFloatArraysRetainPossibleNativeCarriers(): void
    {
        $meter = $this->unit('meter');
        $integers = new ArrayType(new IntegerType(), new UnitIntegerType($meter));
        $floats = new ArrayType(new IntegerType(), new UnitFloatType($meter));

        self::assertSame(
            "(unit_float<'meter'>|unit_int<'meter'>)",
            $this->describe($this->resolve('array_sum', $integers)),
        );
        self::assertSame(
            "0&unit_int<'meter'>|unit_float<'meter'>",
            $this->describe($this->resolve('array_sum', $floats)),
        );
    }

    public function testNonEmptyBoundedCollectionRetainsItsMinimumSum(): void
    {
        $meter = $this->unit('meter');
        $array = TypeCombinator::intersect(
            new ArrayType(new IntegerType(), new UnitConstantIntegerType(2, $meter)),
            new NonEmptyArrayType(),
        );

        self::assertSame(
            "((unit_int<'meter'>&int<2, max>)|unit_float<'meter'>)",
            $this->describe($this->resolve('array_sum', $array)),
        );
    }

    public function testUnsealedShapeModelsZeroOrMoreExtraValues(): void
    {
        $meter = $this->unit('meter');
        $array = new ConstantArrayType(
            [],
            [],
            unsealed: [new IntegerType(), new UnitConstantIntegerType(2, $meter)],
        );

        self::assertSame(
            "0&unit_int<'meter'>|(unit_int<'meter'>&int<2, max>)|unit_float<'meter'>",
            $this->describe($this->resolve('array_sum', $array)),
        );
    }

    public function testEquivalentAliasesUseOneResultUnit(): void
    {
        $array = ConstantArrayTypeBuilder::createEmpty();
        $array->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('meter')));
        $array->setOffsetValueType(null, new UnitConstantIntegerType(3, $this->unit('100 * centimeter')));

        self::assertSame(
            "5&unit_int<'meter'>",
            $this->describe($this->resolve('array_sum', $array->getArray())),
        );
    }

    public function testBareOrInequivalentValuesPreventInference(): void
    {
        $meter = $this->unit('meter');
        $mixed = new ArrayType(
            new IntegerType(),
            TypeCombinator::union(new UnitIntegerType($meter), new IntegerType()),
        );
        self::assertNull($this->resolve('array_sum', $mixed));

        $units = new ArrayType(
            new IntegerType(),
            new UnionType([
                new UnitIntegerType($meter),
                new UnitIntegerType($this->unit('foot')),
            ]),
        );
        self::assertNull($this->resolve('array_sum', $units));
    }

    public function testNumericStringBrandIsNotImplicitlyPromoted(): void
    {
        $array = new ArrayType(new IntegerType(), new UnitNumericStringType($this->unit('meter')));

        self::assertNull($this->resolve('array_sum', $array));
    }

    public function testAnalysisReportsDeterministicMessages(): void
    {
        $meter = $this->unit('meter');
        $mixed = new ArrayType(
            new IntegerType(),
            new UnionType([new UnitIntegerType($meter), new FloatType()]),
        );
        $mixedMessage = 'Cannot call array_sum() with unit-bearing and unbranded values; every possible summand needs one definitionally equivalent unit.';
        self::assertSame(
            ['type' => null, 'message' => $mixedMessage],
            $this->analyse('array_sum', $mixed),
        );

        $units = new ArrayType(
            new IntegerType(),
            new UnionType([
                new UnitIntegerType($meter),
                new UnitIntegerType($this->unit('foot')),
            ]),
        );
        self::assertSame(
            'Cannot call array_sum() with units international_foot and meter because they are not definitionally equivalent.',
            $this->analyse('array_sum', $units)['message'],
        );

        $bareFirst = new ArrayType(
            new IntegerType(),
            new UnionType([new FloatType(), new UnitIntegerType($meter)]),
        );
        self::assertSame($mixedMessage, $this->analyse('array_sum', $bareFirst)['message']);

        $neverFirst = new ArrayType(
            new IntegerType(),
            new UnionType([new NeverType(), new UnitIntegerType($meter)]),
        );
        self::assertNotNull($this->resolve('array_sum', $neverFirst));
    }

    public function testFixedIntegerAndFloatProductsRetainValuesAndComposedUnits(): void
    {
        $integers = ConstantArrayTypeBuilder::createEmpty();
        $integers->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('meter')));
        $integers->setOffsetValueType(null, new UnitConstantIntegerType(3, $this->unit('second')));

        self::assertSame(
            "6&unit_int<'meter * second'>",
            $this->describe($this->resolve('array_product', $integers->getArray())),
        );

        $mixed = ConstantArrayTypeBuilder::createEmpty();
        $mixed->setOffsetValueType(null, new ConstantIntegerType(4));
        $mixed->setOffsetValueType(null, new UnitConstantFloatType(1.5, $this->unit('meter')));
        $mixed->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('second')));

        self::assertSame(
            "12.0&unit_float<'meter * second'>",
            $this->describe($this->resolve('array_product', $mixed->getArray())),
        );
    }

    public function testFixedProductsRetainIntegerRangesAndOverflowPolicy(): void
    {
        $ranges = ConstantArrayTypeBuilder::createEmpty();
        $ranges->setOffsetValueType(null, UnitIntegerTypeHelper::create($this->unit('meter'), 2, 3));
        $ranges->setOffsetValueType(null, UnitIntegerTypeHelper::create($this->unit('second'), -2, 4));

        self::assertSame(
            "unit_int<'meter * second'>&int<-6, 12>",
            $this->describe($this->resolve('array_product', $ranges->getArray())),
        );

        $overflow = ConstantArrayTypeBuilder::createEmpty();
        $overflow->setOffsetValueType(null, new UnitConstantIntegerType(PHP_INT_MAX, $this->unit('meter')));
        $overflow->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('second')));

        self::assertSame(
            "unit_float<'meter * second'>",
            $this->describe($this->resolve('array_product', $overflow->getArray())),
        );
        self::assertSame(
            "unit_int<'meter * second'>",
            $this->describe($this->resolve('array_product', $overflow->getArray(), integerOverflowToFloat: false)),
        );
    }

    public function testFixedProductsAcceptBareFactorsAndOptionalKeys(): void
    {
        $withBare = ConstantArrayTypeBuilder::createEmpty();
        $withBare->setOffsetValueType(null, new ConstantIntegerType(4));
        $withBare->setOffsetValueType(null, new UnitConstantIntegerType(3, $this->unit('meter')));

        self::assertSame(
            "12&unit_int<'meter'>",
            $this->describe($this->resolve('array_product', $withBare->getArray())),
        );

        $withGeneralBare = ConstantArrayTypeBuilder::createEmpty();
        $withGeneralBare->setOffsetValueType(null, new IntegerType());
        $withGeneralBare->setOffsetValueType(null, new UnitIntegerType($this->unit('meter')));

        self::assertSame(
            "(unit_float<'meter'>|unit_int<'meter'>)",
            $this->describe($this->resolve('array_product', $withGeneralBare->getArray())),
        );

        $optional = ConstantArrayTypeBuilder::createEmpty();
        $optional->setOffsetValueType(
            new ConstantIntegerType(0),
            new UnitConstantIntegerType(3, $this->unit('meter')),
            true,
        );
        $optional->setOffsetValueType(
            new ConstantIntegerType(1),
            new UnitConstantIntegerType(2, $this->unit('second')),
        );

        self::assertSame(
            "2&unit_int<'second'>|6&unit_int<'meter * second'>",
            $this->describe($this->resolve('array_product', $optional->getArray())),
        );

        $optionalOnly = ConstantArrayTypeBuilder::createEmpty();
        $optionalOnly->setOffsetValueType(
            new ConstantIntegerType(0),
            new UnitConstantIntegerType(3, $this->unit('meter')),
            true,
        );

        self::assertSame(
            "1&unit_int<'1'>|3&unit_int<'meter'>",
            $this->describe($this->resolve('array_product', $optionalOnly->getArray())),
        );
    }

    public function testFixedProductsComposeDifferentUnitsAndCanCancelToOne(): void
    {
        $squared = ConstantArrayTypeBuilder::createEmpty();
        $squared->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('meter')));
        $squared->setOffsetValueType(null, new UnitConstantIntegerType(3, $this->unit('meter')));

        self::assertSame(
            "6&unit_int<'meter ^ 2'>",
            $this->describe($this->resolve('array_product', $squared->getArray())),
        );

        $cancelled = ConstantArrayTypeBuilder::createEmpty();
        $cancelled->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('meter')));
        $cancelled->setOffsetValueType(null, new UnitConstantIntegerType(3, $this->unit('1 / meter')));

        self::assertSame(
            "6&unit_int<'1'>",
            $this->describe($this->resolve('array_product', $cancelled->getArray())),
        );

        $meters = ConstantArrayTypeBuilder::createEmpty();
        $meters->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('meter')));
        self::assertSame(
            "1&unit_int<'1'>|2&unit_int<'meter'>",
            $this->describe($this->resolve('array_product', new UnionType([
                ConstantArrayTypeBuilder::createEmpty()->getArray(),
                $meters->getArray(),
            ]))),
        );
    }

    public function testProductInferenceFailsClosedForUnknownOrMixedShapes(): void
    {
        $general = new ArrayType(new IntegerType(), new UnitIntegerType($this->unit('meter')));
        $this->assertProductError(
            'Cannot infer a unit for array_product() unless every possible input array has a sealed, statically known shape.',
            $general,
        );

        $unsealed = new ConstantArrayType(
            [],
            [],
            unsealed: [new IntegerType(), new UnitIntegerType($this->unit('meter'))],
        );
        $this->assertProductError(
            'Cannot infer a unit for array_product() unless every possible input array has a sealed, statically known shape.',
            $unsealed,
        );

        $unitShape = ConstantArrayTypeBuilder::createEmpty();
        $unitShape->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('meter')));
        $bareShape = ConstantArrayTypeBuilder::createEmpty();
        $bareShape->setOffsetValueType(null, new ConstantIntegerType(2));
        $this->assertProductError(
            'Cannot infer one array_product() result unit when a possible nonempty array shape has no unit-bearing factor.',
            new UnionType([$unitShape->getArray(), $bareShape->getArray()]),
        );

        $optionalUnit = ConstantArrayTypeBuilder::createEmpty();
        $optionalUnit->setOffsetValueType(
            new ConstantIntegerType(0),
            new UnitConstantIntegerType(2, $this->unit('meter')),
            true,
        );
        $optionalUnit->setOffsetValueType(new ConstantIntegerType(1), new ConstantIntegerType(3));
        $this->assertProductError(
            'Cannot infer one array_product() result unit when a possible nonempty array shape has no unit-bearing factor.',
            $optionalUnit->getArray(),
        );

        $fixedAndGeneral = new UnionType([$unitShape->getArray(), $general]);
        $this->assertProductError(
            'Cannot infer a unit for array_product() unless every possible input array has a sealed, statically known shape.',
            $fixedAndGeneral,
        );

        $generalBare = new ArrayType(new IntegerType(), new IntegerType());
        $this->assertProductError(
            'Cannot infer a unit for array_product() unless every possible input array has a sealed, statically known shape.',
            new UnionType([$unitShape->getArray(), $generalBare]),
        );

        $unsealedBare = new ConstantArrayType(
            [],
            [],
            unsealed: [new IntegerType(), new IntegerType()],
        );
        $this->assertProductError(
            'Cannot infer a unit for array_product() unless every possible input array has a sealed, statically known shape.',
            new UnionType([$unitShape->getArray(), $unsealedBare]),
        );

        $this->assertProductError(
            'Cannot infer a unit for array_product() unless every possible input array has a sealed, statically known shape.',
            new UnionType([$unitShape->getArray(), $unsealed]),
        );
    }

    public function testProductInferenceRejectsImplicitStringCoercionAndNestedBrands(): void
    {
        $numericString = ConstantArrayTypeBuilder::createEmpty();
        $numericString->setOffsetValueType(null, new UnitNumericStringType($this->unit('meter')));
        $this->assertProductError(
            'Cannot call array_product() with a unit-bearing array unless every possible factor is an explicit int or float; cast numeric strings before multiplication.',
            $numericString->getArray(),
        );

        $generalNumericString = new ArrayType(
            new IntegerType(),
            new UnitNumericStringType($this->unit('meter')),
        );
        $this->assertProductError(
            'Cannot call array_product() with a unit-bearing array unless every possible factor is an explicit int or float; cast numeric strings before multiplication.',
            $generalNumericString,
        );

        $unitOrBare = ConstantArrayTypeBuilder::createEmpty();
        $unitOrBare->setOffsetValueType(null, new UnionType([
            new UnitIntegerType($this->unit('meter')),
            new IntegerType(),
        ]));
        $this->assertProductError(
            'Cannot infer one array_product() result unit when a possible nonempty array shape has no unit-bearing factor.',
            $unitOrBare->getArray(),
        );

        $floatUnitOrBare = ConstantArrayTypeBuilder::createEmpty();
        $floatUnitOrBare->setOffsetValueType(null, new UnionType([
            new UnitFloatType($this->unit('meter')),
            new FloatType(),
        ]));
        $this->assertProductError(
            'Cannot infer one array_product() result unit when a possible nonempty array shape has no unit-bearing factor.',
            $floatUnitOrBare->getArray(),
        );

        $optionalUnitOrBare = ConstantArrayTypeBuilder::createEmpty();
        $optionalUnitOrBare->setOffsetValueType(
            new ConstantIntegerType(0),
            new UnionType([
                new UnitIntegerType($this->unit('meter')),
                new IntegerType(),
            ]),
            true,
        );
        $this->assertProductError(
            'Cannot infer one array_product() result unit when a possible nonempty array shape has no unit-bearing factor.',
            $optionalUnitOrBare->getArray(),
        );

        $unitOrNumericString = ConstantArrayTypeBuilder::createEmpty();
        $unitOrNumericString->setOffsetValueType(null, new UnionType([
            new UnitIntegerType($this->unit('meter')),
            new UnitNumericStringType($this->unit('second')),
        ]));
        $this->assertProductError(
            'Cannot call array_product() with a unit-bearing array unless every possible factor is an explicit int or float; cast numeric strings before multiplication.',
            $unitOrNumericString->getArray(),
        );

        $nested = ConstantArrayTypeBuilder::createEmpty();
        $nested->setOffsetValueType(
            null,
            new ArrayType(new IntegerType(), new UnitIntegerType($this->unit('meter'))),
        );
        $nested->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('second')));
        $this->assertProductError(
            'Cannot call array_product() with a unit-bearing array unless every possible factor is an explicit int or float; cast numeric strings before multiplication.',
            $nested->getArray(),
        );

        $string = ConstantArrayTypeBuilder::createEmpty();
        $string->setOffsetValueType(null, new StringType());
        $string->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('meter')));
        $this->assertProductError(
            'Cannot call array_product() with a unit-bearing array unless every possible factor is an explicit int or float; cast numeric strings before multiplication.',
            $string->getArray(),
        );
    }

    public function testProductInferenceReportsDerivedUnitOverflow(): void
    {
        $array = ConstantArrayTypeBuilder::createEmpty();
        $array->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('meter ^ 10000')));
        $array->setOffsetValueType(null, new UnitConstantIntegerType(3, $this->unit('meter')));

        $this->assertProductError(
            'Cannot infer array_product() because its product unit exceeds the supported exponent range.',
            $array->getArray(),
        );
    }

    public function testProductInferenceBoundsFiniteAlternatives(): void
    {
        $alternatives = [];
        for ($exponent = 1; $exponent <= 129; ++$exponent) {
            $alternatives[] = new UnitIntegerType($this->unit("meter ^ {$exponent}"));
        }

        $array = ConstantArrayTypeBuilder::createEmpty();
        $array->setOffsetValueType(null, new UnionType($alternatives));

        $this->assertProductError(
            'Cannot infer array_product() because its fixed input shape produces more than 128 possible unit products.',
            $array->getArray(),
        );

        $optionalExpansion = ConstantArrayTypeBuilder::createEmpty();
        $optionalExpansion->setOffsetValueType(null, new UnionType(array_slice($alternatives, 0, 65)));
        $optionalExpansion->setOffsetValueType(
            new ConstantIntegerType(1),
            new UnitIntegerType($this->unit('second')),
            true,
        );
        $this->assertProductError(
            'Cannot infer array_product() because its fixed input shape produces more than 128 possible unit products.',
            $optionalExpansion->getArray(),
        );

        $exactBoundary = ConstantArrayTypeBuilder::createEmpty();
        $exactBoundary->setOffsetValueType(null, new UnionType(array_slice($alternatives, 0, 64)));
        $exactBoundary->setOffsetValueType(
            new ConstantIntegerType(1),
            new UnitIntegerType($this->unit('second')),
            true,
        );
        $analysis = $this->analyse('array_product', $exactBoundary->getArray());
        self::assertNull($analysis['message']);
        self::assertNotNull($analysis['type']);

        $requiredBoundary = ConstantArrayTypeBuilder::createEmpty();
        $requiredBoundary->setOffsetValueType(null, new UnionType([
            new UnitIntegerType($this->unit('meter')),
            new UnitIntegerType($this->unit('second')),
        ]));
        $requiredBoundary->setOffsetValueType(null, new UnionType(array_slice($alternatives, 0, 64)));
        $analysis = $this->analyse('array_product', $requiredBoundary->getArray());
        self::assertNull($analysis['message']);
        self::assertNotNull($analysis['type']);
    }

    private function assertProductError(string $message, Type $type): void
    {
        self::assertSame(
            ['type' => null, 'message' => $message],
            $this->analyse('array_product', $type),
        );
    }

    private function resolve(
        string $functionName,
        Type $type,
        bool $integerOverflowToFloat = true,
        bool $unpack = false,
    ): ?Type {
        return $this->analyse($functionName, $type, $integerOverflowToFloat, $unpack)['type'];
    }

    /** @return array{type: ?Type, message: ?string} */
    private function analyse(
        string $functionName,
        Type $type,
        bool $integerOverflowToFloat = true,
        bool $unpack = false,
    ): array {
        $variable = new Variable('values');
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (Expr $expression): Type => $expression === $variable ? $type : throw new \LogicException(),
        );

        return $this->extension($functionName, $integerOverflowToFloat)->analyseCall(
            new FuncCall(new Name($functionName), [new Arg($variable, unpack: $unpack)]),
            $scope,
        );
    }

    private function extension(
        string $functionName,
        bool $integerOverflowToFloat,
    ): UnitArrayAggregationFunctionTypeResolverExtension {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn($functionName);

        $reflectionProvider = self::createStub(ReflectionProvider::class);
        $reflectionProvider->method('hasFunction')->willReturn(true);
        $reflectionProvider->method('getFunction')->willReturn($function);

        return new UnitArrayAggregationFunctionTypeResolverExtension(
            $reflectionProvider,
            new UnitOperatorTypeSpecifyingExtension($integerOverflowToFloat),
        );
    }

    private function describe(?Type $type): ?string
    {
        return $type?->describe(VerbosityLevel::precise());
    }

    private function unit(string $unit): UnitExpression
    {
        $result = (new UnitExpressionParser())->parse($unit);
        self::assertTrue($result->isOk(), $result->errorMessage() ?? $unit);

        return $result->expression();
    }
}
