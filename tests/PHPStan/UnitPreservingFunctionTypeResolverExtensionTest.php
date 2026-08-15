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

use jbboehr\Yumemi\PHPStan\UnitConstantFloatType;
use jbboehr\Yumemi\PHPStan\UnitConstantIntegerType;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitPreservingFunctionTypeResolverExtension;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UnitPreservingFunctionTypeResolverExtensionTest extends TestCase
{
    public function testRoundPreservesExactConstantsAcrossPrecisionAndLegacyModeAlternatives(): void
    {
        $precision = new UnionType([
            new ConstantIntegerType(0),
            new ConstantIntegerType(1),
        ]);
        $mode = new UnionType([
            new ConstantIntegerType(PHP_ROUND_HALF_UP),
            new ConstantIntegerType(PHP_ROUND_HALF_DOWN),
        ]);

        $result = $this->round(
            new UnitConstantFloatType(1.25, $this->unit('meter')),
            $precision,
            $mode,
        );

        self::assertSame(
            "1.0&unit_float<'meter'>|1.2&unit_float<'meter'>|1.3&unit_float<'meter'>",
            $result?->describe(VerbosityLevel::precise()),
        );
    }

    public function testRoundSupportsNamedArgumentsAndIntegerConstants(): void
    {
        $result = $this->round(
            new UnitConstantIntegerType(125, $this->unit('meter')),
            new ConstantIntegerType(-1),
            new ConstantIntegerType(PHP_ROUND_HALF_UP),
            PHP_VERSION_ID,
            true,
        );

        self::assertSame("130.0&unit_float<'meter'>", $result?->describe(VerbosityLevel::precise()));

        $defaultPrecision = $this->round(
            new UnitConstantFloatType(1.5, $this->unit('meter')),
            null,
            new ConstantIntegerType(PHP_ROUND_HALF_DOWN),
            PHP_VERSION_ID,
            true,
        );

        self::assertSame("1.0&unit_float<'meter'>", $defaultPrecision?->describe(VerbosityLevel::precise()));
    }

    public function testRoundPreservesOnlyOriginalBenevolentUnions(): void
    {
        $arms = [
            new UnitConstantFloatType(1.5, $this->unit('meter')),
            new UnitConstantFloatType(2.5, $this->unit('second')),
        ];

        $ordinary = $this->round(new UnionType($arms));
        $benevolent = $this->round(new BenevolentUnionType($arms));

        self::assertInstanceOf(UnionType::class, $ordinary);
        self::assertNotInstanceOf(BenevolentUnionType::class, $ordinary);
        self::assertInstanceOf(BenevolentUnionType::class, $benevolent);
        self::assertSame(
            "(2.0&unit_float<'meter'>|3.0&unit_float<'second'>)",
            $benevolent->describe(VerbosityLevel::precise()),
        );
    }

    public function testRoundGeneralizesDynamicInvalidNonfiniteAndCrossEraCalls(): void
    {
        $constant = new UnitConstantFloatType(1.25, $this->unit('meter'));
        $crossEraVersion = PHP_VERSION_ID >= 80400 ? 80399 : 80400;

        self::assertSame("unit_float<'meter'>", $this->describeRound($constant, new IntegerType()));
        self::assertSame(
            "unit_float<'meter'>",
            $this->describeRound($constant, new ConstantFloatType(1.0)),
        );
        self::assertSame(
            "unit_float<'meter'>",
            $this->describeRound($constant, null, new ConstantIntegerType(99)),
        );
        self::assertSame(
            "unit_float<'meter'>",
            $this->describeRound($constant, null, new ConstantStringType('half up')),
        );
        self::assertSame(
            "unit_float<'meter'>",
            $this->describeRound(new UnitConstantFloatType(INF, $this->unit('meter'))),
        );
        self::assertSame("unit_float<'meter'>", $this->describeRound($constant, null, null, $crossEraVersion));
    }

    public function testRoundGeneralizesAnExcessiveCartesianProduct(): void
    {
        $precisions = [];
        for ($precision = -64; $precision <= 64; ++$precision) {
            $precisions[] = new ConstantIntegerType($precision);
        }

        $withinLimit = $this->round(
            new UnitConstantFloatType(1.25, $this->unit('meter')),
            new UnionType(array_slice($precisions, 1)),
        );
        self::assertNotSame("unit_float<'meter'>", $withinLimit?->describe(VerbosityLevel::precise()));
        self::assertSame(
            "unit_float<'meter'>",
            $this->describeRound(
                new UnitConstantFloatType(1.25, $this->unit('meter')),
                new UnionType($precisions),
            ),
        );
    }

    #[DataProvider('php84ModeProvider')]
    public function testRoundSupportsPhp84EnumModesWhenTheAnalyzerCanExecuteThem(
        string $mode,
        string $expected,
    ): void {
        if (PHP_VERSION_ID < 80400) {
            self::markTestSkipped('Native RoundingMode arguments require PHP 8.4 or later.');
        }

        $result = $this->round(
            new UnitConstantFloatType(1.25, $this->unit('meter')),
            new ConstantIntegerType(1),
            new EnumCaseObjectType('RoundingMode', $mode),
        );

        self::assertSame($expected, $result?->describe(VerbosityLevel::precise()));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function php84ModeProvider(): iterable
    {
        yield 'half away from zero' => ['HalfAwayFromZero', "1.3&unit_float<'meter'>"];
        yield 'half towards zero' => ['HalfTowardsZero', "1.2&unit_float<'meter'>"];
        yield 'half even' => ['HalfEven', "1.2&unit_float<'meter'>"];
        yield 'half odd' => ['HalfOdd', "1.3&unit_float<'meter'>"];
    }

    public function testRoundGeneralizesPhp84DirectionalEnumModes(): void
    {
        if (PHP_VERSION_ID < 80400) {
            self::markTestSkipped('Native RoundingMode arguments require PHP 8.4 or later.');
        }

        self::assertSame(
            "unit_float<'meter'>",
            $this->describeRound(
                new UnitConstantFloatType(1.25, $this->unit('meter')),
                new ConstantIntegerType(1),
                new EnumCaseObjectType('RoundingMode', 'TowardsZero'),
            ),
        );
    }

    public function testRoundDoesNotTreatPolyfillEnumOrdinalsAsLegacyModes(): void
    {
        $targetVersion = PHP_VERSION_ID >= 80400 ? PHP_VERSION_ID : 80300;
        $result = $this->round(
            new UnitConstantFloatType(1.5, $this->unit('meter')),
            new ConstantIntegerType(0),
            new ConstantIntegerType(1),
            $targetVersion,
            false,
            new ClassConstFetch(new Name('RoundingMode'), new Identifier('HalfTowardsZero')),
        );

        self::assertSame(
            PHP_VERSION_ID >= 80400 ? "1.0&unit_float<'meter'>" : "unit_float<'meter'>",
            $result?->describe(VerbosityLevel::precise()),
        );
    }

    private function describeRound(
        Type $number,
        ?Type $precision = null,
        ?Type $mode = null,
        int $targetVersion = PHP_VERSION_ID,
    ): ?string {
        return $this->round($number, $precision, $mode, $targetVersion)?->describe(VerbosityLevel::precise());
    }

    private function round(
        Type $number,
        ?Type $precision = null,
        ?Type $mode = null,
        int $targetVersion = PHP_VERSION_ID,
        bool $named = false,
        ?Expr $modeExpression = null,
    ): ?Type {
        $numberExpression = new Variable('number');
        $precisionExpression = new Variable('precision');
        $modeExpression ??= new Variable('mode');
        $arguments = [$named
            ? new Arg($numberExpression, name: new Identifier('num'))
            : new Arg($numberExpression)];
        if ($precision !== null || ($mode !== null && !$named)) {
            $precision ??= new ConstantIntegerType(0);
            $arguments[] = $named
                ? new Arg($precisionExpression, name: new Identifier('precision'))
                : new Arg($precisionExpression);
        }
        if ($mode !== null) {
            $arguments[] = $named
                ? new Arg($modeExpression, name: new Identifier('mode'))
                : new Arg($modeExpression);
        }

        $types = [
            spl_object_id($numberExpression) => $number,
            spl_object_id($precisionExpression) => $precision,
            spl_object_id($modeExpression) => $mode,
        ];
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturnCallback(
            static fn (Name $name): string => $name->toString(),
        );
        $scope->method('getType')->willReturnCallback(
            static fn (Expr $expression): Type => $types[spl_object_id($expression)]
                ?? throw new \LogicException('Unexpected expression.'),
        );
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn('round');
        $reflectionProvider = self::createStub(ReflectionProvider::class);
        $reflectionProvider->method('hasFunction')->willReturn(true);
        $reflectionProvider->method('getFunction')->willReturn($function);
        $extension = new UnitPreservingFunctionTypeResolverExtension(
            $reflectionProvider,
            new PhpVersion($targetVersion, PhpVersion::SOURCE_CONFIG),
            true,
        );

        return $extension->getType(new FuncCall(new Name('round'), $arguments), $scope);
    }

    private function unit(string $expression): UnitExpression
    {
        $parsed = (new UnitExpressionParser())->parse($expression);
        self::assertTrue($parsed->isOk());

        return $parsed->expression();
    }
}
