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

use jbboehr\Yumemi\PHPStan\UnitConstantIntegerType;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitMinMaxFunctionTypeResolverExtension;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitMinMaxFunctionTypeResolverExtensionTest extends TestCase
{
    public function testUnsupportedFunctionDoesNotClaimBrandedArray(): void
    {
        $array = new ArrayType(new IntegerType(), new UnitIntegerType($this->unit('meter')));

        self::assertNull($this->resolve('abs', [$array], [false]));
    }

    public function testSingleScalarArgumentIsNotClaimed(): void
    {
        self::assertNull($this->resolve('min', [new UnitConstantIntegerType(1, $this->unit('meter'))], [false]));
    }

    public function testNonIterableUnpackIsNotClaimed(): void
    {
        self::assertNull($this->resolve('min', [new UnitIntegerType($this->unit('meter'))], [true]));
    }

    public function testFirstUnitSpellingRepresentsEquivalentCandidates(): void
    {
        $type = $this->resolve('max', [
            new UnitConstantIntegerType(1, $this->unit('meter')),
            new UnitConstantIntegerType(2, $this->unit('100 * centimeter')),
        ]);

        self::assertSame("2&unit_int<'meter'>", $type?->describe(VerbosityLevel::precise()));
    }

    public function testUnboundedIntegerExtremaRemainSound(): void
    {
        $meter = $this->unit('meter');
        $types = [new UnitIntegerType($meter), new UnitConstantIntegerType(5, $meter)];

        self::assertSame(
            "unit_int<'meter'>&int<min, 5>",
            $this->resolve('min', $types)?->describe(VerbosityLevel::precise()),
        );
        self::assertSame(
            "unit_int<'meter'>&int<5, max>",
            $this->resolve('max', $types)?->describe(VerbosityLevel::precise()),
        );
    }

    public function testMixedCarriersRetainBothPossibleNativeTypes(): void
    {
        $meter = $this->unit('meter');
        $type = $this->resolve('min', [
            new UnitFloatType($meter),
            new UnitConstantIntegerType(5, $meter),
        ]);

        self::assertSame(
            "5&unit_int<'meter'>|unit_float<'meter'>",
            $type?->describe(VerbosityLevel::precise()),
        );
    }

    public function testBareCandidatePreventsBrandInference(): void
    {
        self::assertNull($this->resolve('min', [
            new UnitConstantIntegerType(1, $this->unit('meter')),
            new ConstantIntegerType(2),
        ]));
    }

    public function testInequivalentCandidatePreventsBrandInference(): void
    {
        self::assertNull($this->resolve('max', [
            new UnitConstantIntegerType(1, $this->unit('meter')),
            new UnitConstantIntegerType(2, $this->unit('foot')),
        ]));
    }

    public function testConstantArrayIsFoldedByTheYumemiExtension(): void
    {
        $meter = $this->unit('meter');
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $builder->setOffsetValueType(null, new UnitConstantIntegerType(3, $meter));
        $builder->setOffsetValueType(null, new UnitConstantIntegerType(1, $meter));

        self::assertSame(
            "1&unit_int<'meter'>",
            $this->resolve('min', [$builder->getArray()], [false])?->describe(VerbosityLevel::precise()),
        );
    }

    public function testConstantArrayUnionFailsClosedWhenOneArmContainsABareValue(): void
    {
        $meter = $this->unit('meter');
        $branded = ConstantArrayTypeBuilder::createEmpty();
        $branded->setOffsetValueType(null, new UnitConstantIntegerType(3, $meter));
        $mixed = ConstantArrayTypeBuilder::createEmpty();
        $mixed->setOffsetValueType(null, new UnitConstantIntegerType(1, $meter));
        $mixed->setOffsetValueType(null, new ConstantIntegerType(2));

        self::assertNull($this->resolve('min', [new UnionType([
            $branded->getArray(),
            $mixed->getArray(),
        ])], [false]));
    }

    public function testConstantArrayUnionFailsClosedWhenArmsUseDifferentUnits(): void
    {
        $meters = ConstantArrayTypeBuilder::createEmpty();
        $meters->setOffsetValueType(null, new UnitConstantIntegerType(1, $this->unit('meter')));
        $feet = ConstantArrayTypeBuilder::createEmpty();
        $feet->setOffsetValueType(null, new UnitConstantIntegerType(2, $this->unit('foot')));

        self::assertNull($this->resolve('max', [new UnionType([
            $meters->getArray(),
            $feet->getArray(),
        ])], [false]));
    }

    public function testEmptyConstantArrayArmDoesNotHideValidReturningArm(): void
    {
        $branded = ConstantArrayTypeBuilder::createEmpty();
        $branded->setOffsetValueType(null, new UnitConstantIntegerType(3, $this->unit('meter')));

        self::assertSame(
            "3&unit_int<'meter'>",
            $this->resolve('min', [new UnionType([
                ConstantArrayTypeBuilder::createEmpty()->getArray(),
                $branded->getArray(),
            ])], [false])?->describe(VerbosityLevel::precise()),
        );
    }

    public function testGeneralBrandedArrayRetainsItsValueType(): void
    {
        $array = new ArrayType(new IntegerType(), new UnitIntegerType($this->unit('meter')));

        self::assertSame(
            "unit_int<'meter'>",
            $this->resolve('max', [$array], [false])?->describe(VerbosityLevel::precise()),
        );
    }

    public function testOptionalArrayEntryDoesNotBecomeMandatory(): void
    {
        $meter = $this->unit('meter');
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $builder->setOffsetValueType(new ConstantIntegerType(0), new UnitConstantIntegerType(3, $meter));
        $builder->setOffsetValueType(new ConstantIntegerType(1), new UnitConstantIntegerType(1, $meter), true);

        self::assertSame(
            "unit_int<'meter'>&int<1, 3>",
            $this->resolve('min', [$builder->getArray()], [false])?->describe(VerbosityLevel::precise()),
        );
    }

    public function testArgumentsAfterOptionalUnpackRemainCandidates(): void
    {
        $meter = $this->unit('meter');
        $optional = new ArrayType(new IntegerType(), new UnitConstantIntegerType(6, $meter));

        self::assertSame(
            "unit_int<'meter'>&int<1, 6>",
            $this->resolve('min', [
                new UnitConstantIntegerType(5, $meter),
                $optional,
                new UnitConstantIntegerType(1, $meter),
            ], [false, true, false])?->describe(VerbosityLevel::precise()),
        );
    }

    /**
     * @param non-empty-list<Type> $types
     * @param list<bool>|null $unpacked
     */
    private function resolve(string $functionName, array $types, ?array $unpacked = null): ?Type
    {
        $arguments = [];
        $typesByExpression = [];
        foreach ($types as $index => $type) {
            $variable = new Variable('value' . $index);
            $arguments[] = new Arg($variable, unpack: $unpacked[$index] ?? false);
            $typesByExpression[spl_object_id($variable)] = $type;
        }

        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (Expr $expression): Type => $typesByExpression[spl_object_id($expression)],
        );

        return $this->extension($functionName)->getType(
            new FuncCall(new Name($functionName), $arguments),
            $scope,
        );
    }

    private function extension(string $functionName): UnitMinMaxFunctionTypeResolverExtension
    {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn($functionName);

        $reflectionProvider = self::createStub(ReflectionProvider::class);
        $reflectionProvider->method('hasFunction')->willReturn(true);
        $reflectionProvider->method('getFunction')->willReturn($function);

        return new UnitMinMaxFunctionTypeResolverExtension($reflectionProvider);
    }

    private function unit(string $unit): UnitExpression
    {
        $result = (new UnitExpressionParser())->parse($unit);
        self::assertTrue($result->isOk(), $result->errorMessage() ?? $unit);

        return $result->expression();
    }
}
