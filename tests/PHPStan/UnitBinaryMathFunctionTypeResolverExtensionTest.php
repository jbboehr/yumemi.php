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

use jbboehr\Yumemi\PHPStan\UnitBinaryMathFunctionTypeResolverExtension;
use jbboehr\Yumemi\PHPStan\UnitConstantFloatType;
use jbboehr\Yumemi\PHPStan\UnitConstantIntegerType;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitBinaryMathFunctionTypeResolverExtensionTest extends TestCase
{
    public function testIncompleteCallReturnsNeutralAnalysis(): void
    {
        $extension = $this->extension('fdiv');
        $analysis = $extension->analyseCall(
            new FuncCall(new Name('fdiv')),
            $this->scope(new FloatType(), new FloatType()),
        );
        $left = new Variable('left');
        $missingRight = $extension->analyseCall(
            new FuncCall(new Name('fdiv'), [new Arg($left)]),
            $this->scope(new FloatType(), new FloatType()),
        );

        self::assertSame(['type' => null, 'message' => null], $analysis);
        self::assertSame(['type' => null, 'message' => null], $missingRight);
    }

    public function testBareOperandsRemainOwnedByNativePhpstan(): void
    {
        $analysis = $this->analyse('fdiv', new FloatType(), new FloatType());

        self::assertSame(['type' => null, 'message' => null], $analysis);
    }

    public function testFloatDivisionCombinesUnitsAndPreservesFiniteConstants(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new UnitConstantIntegerType(6, $this->unit('meter')),
            new UnitConstantIntegerType(2, $this->unit('second')),
        );

        self::assertSame(
            "3.0&unit_float<'meter / second'>",
            $analysis['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertNull($analysis['message']);
    }

    public function testFloatDivisionByZeroRetainsOnlyTheDerivedBrand(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new UnitConstantFloatType(1.0, $this->unit('meter')),
            new UnitConstantFloatType(0.0, $this->unit('second')),
        );

        self::assertSame(
            "unit_float<'meter / second'>",
            $analysis['type']?->describe(VerbosityLevel::precise()),
        );
    }

    public function testFloatDivisionPreservesBareConstantOperands(): void
    {
        $brandedDividend = $this->analyse(
            'fdiv',
            new UnitConstantIntegerType(6, $this->unit('meter')),
            new ConstantFloatType(2.0),
        );
        $brandedDivisor = $this->analyse(
            'fdiv',
            new ConstantFloatType(6.0),
            new UnitConstantIntegerType(2, $this->unit('second')),
        );

        self::assertSame(
            "3.0&unit_float<'meter'>",
            $brandedDividend['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertSame(
            "3.0&unit_float<'1 / second'>",
            $brandedDivisor['type']?->describe(VerbosityLevel::precise()),
        );
    }

    public function testUnitDetectionIncludesEveryReachableUnionArm(): void
    {
        $unitAfterBareLeftArm = $this->analyse(
            'fmod',
            new UnionType([
                new FloatType(),
                new UnitIntegerType($this->unit('meter')),
            ]),
            new FloatType(),
        );
        $unitAfterBareRightArm = $this->analyse(
            'hypot',
            new FloatType(),
            new UnionType([
                new FloatType(),
                new UnitIntegerType($this->unit('meter')),
            ]),
        );

        self::assertSame(
            'Cannot call fmod() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
            $unitAfterBareLeftArm['message'],
        );
        self::assertNull($unitAfterBareLeftArm['type']);
        self::assertSame(
            'Cannot call hypot() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
            $unitAfterBareRightArm['message'],
        );
        self::assertNull($unitAfterBareRightArm['type']);
    }

    public function testFloatRemainderAndHypotenusePreserveEquivalentUnit(): void
    {
        $remainder = $this->analyse(
            'fmod',
            new UnitConstantIntegerType(7, $this->unit('meter')),
            new UnitConstantIntegerType(3, $this->unit('m')),
        );
        $hypotenuse = $this->analyse(
            'hypot',
            new UnitConstantIntegerType(3, $this->unit('meter')),
            new UnitConstantIntegerType(4, $this->unit('m')),
        );

        self::assertSame("1.0&unit_float<'meter'>", $remainder['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("5.0&unit_float<'meter'>", $hypotenuse['type']?->describe(VerbosityLevel::precise()));
    }

    public function testFloatRemainderByZeroRetainsOnlyTheUnit(): void
    {
        $analysis = $this->analyse(
            'fmod',
            new UnitConstantIntegerType(7, $this->unit('meter')),
            new UnitConstantIntegerType(0, $this->unit('meter')),
        );

        self::assertSame("unit_float<'meter'>", $analysis['type']?->describe(VerbosityLevel::precise()));
        self::assertNull($analysis['message']);
    }

    public function testSameUnitFunctionsRejectBareAndInequivalentOperands(): void
    {
        $mixed = $this->analyse('hypot', new UnitFloatType($this->unit('meter')), new FloatType());
        $inequivalent = $this->analyse(
            'fmod',
            new UnitFloatType($this->unit('meter')),
            new UnitFloatType($this->unit('second')),
        );

        self::assertSame(
            'Cannot call hypot() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
            $mixed['message'],
        );
        self::assertNull($mixed['type']);
        self::assertSame(
            'Cannot call fmod(): argument #1 has unit meter but argument #2 has unit second; they are not definitionally equivalent.',
            $inequivalent['message'],
        );
        self::assertNull($inequivalent['type']);
    }

    public function testSameUnitFunctionDiagnosticPreservesOperandOrder(): void
    {
        $analysis = $this->analyse(
            'fmod',
            new UnitFloatType($this->unit('second')),
            new UnitFloatType($this->unit('meter')),
        );

        self::assertSame(
            'Cannot call fmod(): argument #1 has unit second but argument #2 has unit meter; they are not definitionally equivalent.',
            $analysis['message'],
        );
    }

    public function testSameUnitFunctionFailsClosedAcrossUnionArms(): void
    {
        $analysis = $this->analyse(
            'hypot',
            TypeCombinator::union(
                new UnitIntegerType($this->unit('meter')),
                new FloatType(),
            ),
            new UnitFloatType($this->unit('meter')),
        );

        self::assertNull($analysis['type']);
        self::assertSame(
            'Cannot call hypot() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
            $analysis['message'],
        );
    }

    public function testUnsupportedOperandFallsBackToNativeAnalysis(): void
    {
        $unsupportedRight = $this->analyse(
            'hypot',
            new UnitFloatType($this->unit('meter')),
            new ArrayType(new FloatType(), new FloatType()),
        );
        $unsupportedLeft = $this->analyse(
            'hypot',
            new ArrayType(new FloatType(), new FloatType()),
            new UnitFloatType($this->unit('meter')),
        );

        self::assertSame(['type' => null, 'message' => null], $unsupportedRight);
        self::assertSame(['type' => null, 'message' => null], $unsupportedLeft);
    }

    public function testFloatDivisionDefersNonnumericAlternatives(): void
    {
        $array = new ArrayType(new FloatType(), new FloatType());
        $unit = new UnitFloatType($this->unit('meter'));

        self::assertSame(['type' => null, 'message' => null], $this->analyse('fdiv', $array, $unit));
        self::assertSame(['type' => null, 'message' => null], $this->analyse('fdiv', $unit, $array));
    }

    public function testFloatDivisionReportsUnitExponentOverflow(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new UnitFloatType($this->unit('meter ^ 10000')),
            new UnitFloatType($this->unit('meter ^ -10000')),
        );

        self::assertNull($analysis['type']);
        self::assertSame(
            'Cannot call fdiv() because the resulting unit exceeds the supported exponent range.',
            $analysis['message'],
        );
    }

    public function testFloatDivisionPreservesBenevolentUnitUnion(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new BenevolentUnionType([
                new UnitFloatType($this->unit('meter')),
                new UnitFloatType($this->unit('second')),
            ]),
            new UnitConstantIntegerType(2, $this->unit('minute')),
        );

        self::assertInstanceOf(BenevolentUnionType::class, $analysis['type']);
        self::assertNull($analysis['message']);
    }

    public function testOrdinaryOperandUnionPreventsBenevolentResult(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('meter')),
                new UnitFloatType($this->unit('meter')),
            ]),
            new UnionType([
                new UnitIntegerType($this->unit('second')),
                new UnitIntegerType($this->unit('minute')),
            ]),
        );

        self::assertInstanceOf(UnionType::class, $analysis['type']);
        self::assertNotInstanceOf(BenevolentUnionType::class, $analysis['type']);
        self::assertNull($analysis['message']);
    }

    public function testBenevolenceIsPreservedSymmetricallyAndNeverOverridesAnOrdinaryUnion(): void
    {
        $rightBenevolent = $this->analyse(
            'fdiv',
            new UnitFloatType($this->unit('meter')),
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('second')),
                new UnitIntegerType($this->unit('minute')),
            ]),
        );
        $bothBenevolent = $this->analyse(
            'fdiv',
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('meter')),
                new UnitIntegerType($this->unit('foot')),
            ]),
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('second')),
                new UnitIntegerType($this->unit('minute')),
            ]),
        );
        $ordinaryLeft = $this->analyse(
            'fdiv',
            new UnionType([
                new UnitIntegerType($this->unit('meter')),
                new UnitIntegerType($this->unit('foot')),
            ]),
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('second')),
                new UnitIntegerType($this->unit('minute')),
            ]),
        );

        self::assertInstanceOf(BenevolentUnionType::class, $rightBenevolent['type']);
        self::assertInstanceOf(BenevolentUnionType::class, $bothBenevolent['type']);
        self::assertInstanceOf(UnionType::class, $ordinaryLeft['type']);
        self::assertNotInstanceOf(BenevolentUnionType::class, $ordinaryLeft['type']);
    }

    public function testCartesianConstantUnionsRetainEveryResult(): void
    {
        $division = $this->analyse(
            'fdiv',
            new UnionType([
                new UnitConstantIntegerType(6, $this->unit('meter')),
                new UnitConstantIntegerType(8, $this->unit('meter')),
            ]),
            new UnionType([
                new UnitConstantIntegerType(2, $this->unit('second')),
                new UnitConstantIntegerType(4, $this->unit('second')),
            ]),
        );
        $hypotenuse = $this->analyse(
            'hypot',
            new UnionType([
                new UnitConstantIntegerType(3, $this->unit('meter')),
                new UnitConstantIntegerType(4, $this->unit('meter')),
            ]),
            new UnionType([
                new UnitConstantIntegerType(4, $this->unit('meter')),
                new UnitConstantIntegerType(5, $this->unit('meter')),
            ]),
        );

        self::assertSame([1.5, 2.0, 3.0, 4.0], $this->constantFloatValues($division['type']));
        self::assertCount(4, $this->constantFloatValues($hypotenuse['type']));
    }

    /** @return array{type: Type|null, message: string|null} */
    private function analyse(string $function, Type $leftType, Type $rightType): array
    {
        $left = new Variable('left');
        $right = new Variable('right');

        return $this->extension($function)->analyseCall(
            new FuncCall(new Name($function), [new Arg($left), new Arg($right)]),
            $this->scope($leftType, $rightType),
        );
    }

    private function extension(string $functionName): UnitBinaryMathFunctionTypeResolverExtension
    {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn($functionName);

        $reflectionProvider = self::createStub(ReflectionProvider::class);
        $reflectionProvider->method('hasFunction')->willReturn(true);
        $reflectionProvider->method('getFunction')->willReturn($function);

        return new UnitBinaryMathFunctionTypeResolverExtension($reflectionProvider);
    }

    private function scope(Type $leftType, Type $rightType): Scope
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (Variable $variable): Type => $variable->name === 'left' ? $leftType : $rightType,
        );

        return $scope;
    }

    private function unit(string $unit): UnitExpression
    {
        $result = (new UnitExpressionParser())->parse($unit);
        self::assertTrue($result->isOk(), $result->errorMessage() ?? $unit);

        return $result->expression();
    }

    /** @return list<float> */
    private function constantFloatValues(?Type $type): array
    {
        self::assertNotNull($type);
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        $values = [];
        foreach ($types as $constant) {
            self::assertInstanceOf(ConstantFloatType::class, $constant);
            $values[] = $constant->getValue();
        }
        sort($values);

        return $values;
    }
}
