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

use jbboehr\Yumemi\PHPStan\UnitArraySumFunctionTypeResolverExtension;
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
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitArraySumFunctionTypeResolverExtensionTest extends TestCase
{
    public function testUnsupportedFunctionIsNotClaimed(): void
    {
        $array = new ArrayType(new IntegerType(), new UnitIntegerType($this->unit('meter')));

        self::assertNull($this->resolve('array_product', $array));
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
    ): UnitArraySumFunctionTypeResolverExtension {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn($functionName);

        $reflectionProvider = self::createStub(ReflectionProvider::class);
        $reflectionProvider->method('hasFunction')->willReturn(true);
        $reflectionProvider->method('getFunction')->willReturn($function);

        return new UnitArraySumFunctionTypeResolverExtension(
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
