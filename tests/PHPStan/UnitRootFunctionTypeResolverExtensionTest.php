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

use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitRootFunctionTypeResolverExtension;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\TestCase;

final class UnitRootFunctionTypeResolverExtensionTest extends TestCase
{
    public function testIncompleteCallReturnsCompleteNeutralAnalysis(): void
    {
        $analysis = $this->extension()->analyseCall(new FuncCall(new Name('sqrt')), $this->scope(new FloatType()));

        self::assertSame(['type' => null, 'message' => null], $analysis);
    }

    public function testUnbrandedArmDoesNotHideLaterInvalidBrandedArm(): void
    {
        $type = new UnionType([
            new FloatType(),
            new UnitIntegerType($this->unit('meter')),
        ]);

        $analysis = $this->analyse($type);

        self::assertNull($analysis['type']);
        self::assertSame(
            'Cannot call sqrt() because at least one possible unit lacks an exact symbolic square root: meter.',
            $analysis['message'],
        );
    }

    public function testDuplicateInvalidUnitsAppearOnceInDiagnostic(): void
    {
        $meter = $this->unit('meter');
        $type = new UnionType([
            new UnitFloatType($meter),
            new UnitIntegerType($meter),
        ]);

        $analysis = $this->analyse($type);

        self::assertSame(
            'Cannot call sqrt() because at least one possible unit lacks an exact symbolic square root: meter.',
            $analysis['message'],
        );
    }

    public function testBenevolentUnionIsPreservedAfterRooting(): void
    {
        $type = new BenevolentUnionType([
            new UnitFloatType($this->unit('meter^2')),
            new UnitIntegerType($this->unit('second^2')),
        ]);

        $analysis = $this->analyse($type);

        self::assertInstanceOf(BenevolentUnionType::class, $analysis['type']);
        self::assertNull($analysis['message']);
    }

    /** @return array{type: Type|null, message: string|null} */
    private function analyse(Type $type): array
    {
        $value = new Variable('value');

        return $this->extension()->analyseCall(
            new FuncCall(new Name('sqrt'), [new Arg($value)]),
            $this->scope($type),
        );
    }

    private function extension(): UnitRootFunctionTypeResolverExtension
    {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn('sqrt');

        $reflectionProvider = self::createStub(ReflectionProvider::class);
        $reflectionProvider->method('hasFunction')->willReturn(true);
        $reflectionProvider->method('getFunction')->willReturn($function);

        return new UnitRootFunctionTypeResolverExtension($reflectionProvider);
    }

    private function scope(Type $type): Scope
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn($type);

        return $scope;
    }

    private function unit(string $unit): UnitExpression
    {
        $result = (new UnitExpressionParser())->parse($unit);
        self::assertTrue($result->isOk(), $result->errorMessage() ?? $unit);

        return $result->expression();
    }
}
