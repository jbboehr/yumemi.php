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
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use jbboehr\Yumemi\PHPStan\UnitNumericStringType;
use jbboehr\Yumemi\PHPStan\UnitRangeFunctionTypeResolverExtension;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitRangeFunctionTypeResolverExtensionTest extends TestCase
{
    public function testOrdinaryAndUnsupportedCallsRemainOwnedByPhpStan(): void
    {
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse(new IntegerType(), new IntegerType()),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse(
                new UnitIntegerType($this->unit('meter')),
                new UnitIntegerType($this->unit('meter')),
                functionName: 'custom_range',
            ),
        );
    }

    public function testSmallConstantIntegerRangesRetainExactValues(): void
    {
        $meter = $this->unit('meter');

        self::assertSame(
            "array{1&unit_int<'meter'>, 2&unit_int<'meter'>, 3&unit_int<'meter'>}",
            $this->describe($this->resolve(
                new UnitConstantIntegerType(1, $meter),
                new UnitConstantIntegerType(3, $meter),
            )),
        );
        self::assertSame(
            "array{3&unit_int<'meter'>, 2&unit_int<'meter'>, 1&unit_int<'meter'>}",
            $this->describe($this->resolve(
                new UnitConstantIntegerType(3, $meter),
                new UnitConstantIntegerType(1, $meter),
            )),
        );
    }

    public function testExplicitIntegerAndFloatStepsRetainExactValues(): void
    {
        $meter = $this->unit('meter');

        self::assertSame(
            "array{0&unit_int<'meter'>, 2&unit_int<'meter'>, 4&unit_int<'meter'>}",
            $this->describe($this->resolve(
                new UnitConstantIntegerType(0, $meter),
                new UnitConstantIntegerType(5, $meter),
                new UnitConstantIntegerType(2, $meter),
            )),
        );
        self::assertSame(
            "array{0.0&unit_float<'meter'>, 0.5&unit_float<'meter'>, 1.0&unit_float<'meter'>}",
            $this->describe($this->resolve(
                new UnitConstantFloatType(0.0, $meter),
                new UnitConstantFloatType(1.0, $meter),
                new UnitConstantFloatType(0.5, $meter),
            )),
        );
        self::assertSame(
            "array{3&unit_int<'meter'>, 2&unit_int<'meter'>, 1&unit_int<'meter'>}",
            $this->describe($this->resolve(
                new UnitConstantIntegerType(3, $meter),
                new UnitConstantIntegerType(1, $meter),
                new UnitConstantIntegerType(-1, $meter),
            )),
        );
        self::assertSame(
            "array{0.0&unit_float<'meter'>, 1.0&unit_float<'meter'>, 2.0&unit_float<'meter'>}",
            $this->describe($this->resolve(
                new UnitConstantFloatType(0.0, $meter),
                new UnitConstantFloatType(2.0, $meter),
                new UnitConstantFloatType(1.0, $meter),
            )),
        );
    }

    public function testConfiguredPhpVersionControlsIncreasingNegativeSteps(): void
    {
        $meter = $this->unit('meter');
        $start = new UnitConstantIntegerType(0, $meter);
        $end = new UnitConstantIntegerType(5, $meter);
        $step = new UnitConstantIntegerType(-2, $meter);

        self::assertSame(
            "array{0&unit_int<'meter'>, 2&unit_int<'meter'>, 4&unit_int<'meter'>}",
            $this->describe($this->resolve($start, $end, $step, targetVersion: 80200)),
        );
        self::assertNull($this->resolve($start, $end, $step, targetVersion: 80300));
        self::assertSame(
            "array{0&unit_int<'meter'>, 2&unit_int<'meter'>, 4&unit_int<'meter'>}",
            $this->describe($this->resolve(
                $start,
                $end,
                new UnitConstantIntegerType(2, $meter),
                targetVersion: 80300,
            )),
        );
        self::assertSame(
            "array{0&unit_int<'meter'>, 5&unit_int<'meter'>}",
            $this->describe($this->resolve(
                $start,
                $end,
                new UnitConstantIntegerType(-5, $meter),
                targetVersion: 80200,
            )),
        );
        self::assertSame(
            "array{5&unit_int<'meter'>}",
            $this->describe($this->resolve(
                $end,
                $end,
                new UnitConstantIntegerType(-1, $meter),
                targetVersion: 80300,
            )),
        );
    }

    public function testKnownThrowingConstantRangesRemainOwnedByPhpStan(): void
    {
        $meter = $this->unit('meter');

        self::assertNull($this->resolve(
            new UnitConstantIntegerType(1, $meter),
            new UnitConstantIntegerType(3, $meter),
            new UnitConstantIntegerType(0, $meter),
        ));
        self::assertNull($this->resolve(
            new UnitConstantFloatType(INF, $meter),
            new UnitConstantFloatType(3.0, $meter),
            new UnitConstantFloatType(1.0, $meter),
        ));
        self::assertNull($this->resolve(
            new UnitConstantFloatType(1.0, $meter),
            new UnitConstantFloatType(3.0, $meter),
            new UnitConstantFloatType(INF, $meter),
        ));
    }

    public function testSuccessfulConstantUnionAlternativesRemainAvailable(): void
    {
        $meter = $this->unit('meter');
        $step = new UnionType([
            new UnitConstantIntegerType(0, $meter),
            new UnitConstantIntegerType(1, $meter),
        ]);

        self::assertSame(
            "array{1&unit_int<'meter'>, 2&unit_int<'meter'>, 3&unit_int<'meter'>}",
            $this->describe($this->resolve(
                new UnitConstantIntegerType(1, $meter),
                new UnitConstantIntegerType(3, $meter),
                $step,
            )),
        );
    }

    public function testPhp82NanStepCanProduceAnEmptyList(): void
    {
        $meter = $this->unit('meter');
        $start = new UnitConstantFloatType(0.0, $meter);
        $end = new UnitConstantFloatType(5.0, $meter);

        self::assertSame(
            'array{}',
            $this->describe($this->resolve(
                $start,
                $end,
                new UnitConstantFloatType(NAN, $meter),
                targetVersion: 80200,
            )),
        );
        self::assertNull($this->resolve(
            $start,
            $end,
            new UnitConstantFloatType(NAN, $meter),
            targetVersion: 80300,
        ));
        self::assertSame(
            "list<unit_float<'meter'>>",
            $this->describe($this->resolve(
                $start,
                $end,
                new UnitFloatType($meter),
                targetVersion: 80200,
            )),
        );
        self::assertSame(
            "non-empty-list<unit_float<'meter'>>",
            $this->describe($this->resolve(
                $start,
                $end,
                new UnitFloatType($meter),
                targetVersion: 80300,
            )),
        );
    }

    public function testPhp82NanEndpointsFallBackToANonEmptyFloatList(): void
    {
        $meter = $this->unit('meter');

        self::assertSame(
            "non-empty-list<unit_float<'meter'>>",
            $this->describe($this->resolve(
                new UnitConstantFloatType(NAN, $meter),
                new UnitConstantFloatType(5.0, $meter),
                new UnitConstantFloatType(1.0, $meter),
                targetVersion: 80200,
            )),
        );
        self::assertNull($this->resolve(
            new UnitConstantFloatType(NAN, $meter),
            new UnitConstantFloatType(5.0, $meter),
            new UnitConstantFloatType(1.0, $meter),
            targetVersion: 80300,
        ));
    }

    public function testExactRangeThresholdAndEstimateBoundaries(): void
    {
        $meter = $this->unit('meter');

        $this->assertExactRangeCount(50, 1, 50, 1, $meter);
        self::assertNotInstanceOf(
            ConstantArrayType::class,
            $this->resolve(
                new UnitConstantIntegerType(1, $meter),
                new UnitConstantIntegerType(51, $meter),
                new UnitConstantIntegerType(1, $meter),
            ),
        );
        $this->assertExactRangeCount(21, 30, 50, 1, $meter);
        $this->assertExactRangeCount(16, 0, 30, 2, $meter);

        $floatRange = $this->resolve(
            new UnitConstantFloatType(0.0, $meter),
            new UnitConstantFloatType(49.6, $meter),
            new UnitConstantFloatType(1.0, $meter),
        );
        self::assertInstanceOf(ConstantArrayType::class, $floatRange);
        self::assertCount(50, $floatRange->getValueTypes());
    }

    public function testLargeAndDynamicIntegerRangesRetainEndpointBounds(): void
    {
        $meter = $this->unit('meter');
        $boundedStart = UnitIntegerTypeHelper::create($meter, 0, 10);
        $boundedEnd = UnitIntegerTypeHelper::create($meter, 100, 200);

        self::assertSame(
            "non-empty-list<unit_int<'meter'>&int<0, 200>>",
            $this->describe($this->resolve($boundedStart, $boundedEnd)),
        );
        self::assertSame(
            "non-empty-list<unit_int<'meter'>&int<0, 100>>",
            $this->describe($this->resolve(
                new UnitConstantIntegerType(0, $meter),
                new UnitConstantIntegerType(100, $meter),
            )),
        );
        self::assertSame(
            "non-empty-list<unit_int<'meter'>&int<0, 12>>",
            $this->describe($this->resolve(
                UnitIntegerTypeHelper::create($meter, 0, 10),
                new UnitConstantIntegerType(12, $meter),
            )),
        );
        self::assertSame(
            "non-empty-list<unit_int<'meter'>&int<0, 20>>",
            $this->describe($this->resolve(
                new UnitConstantIntegerType(0, $meter),
                UnitIntegerTypeHelper::create($meter, 10, 20),
            )),
        );
    }

    public function testPossibleIntegerAndFloatRangesRetainBothCarriers(): void
    {
        $meter = $this->unit('meter');
        $start = new UnionType([
            new UnitIntegerType($meter),
            new UnitFloatType($meter),
        ]);

        self::assertSame(
            "non-empty-list<unit_float<'meter'>|unit_int<'meter'>>",
            $this->describe($this->resolve($start, new UnitIntegerType($meter))),
        );

        $benevolent = new BenevolentUnionType([
            new UnitIntegerType($meter),
            new UnitFloatType($meter),
        ]);
        self::assertSame(
            "non-empty-list<(unit_float<'meter'>|unit_int<'meter'>)>",
            $this->describe($this->resolve($benevolent, new UnitIntegerType($meter))),
        );
    }

    public function testEachFloatArgumentPositionProducesOnlyFloatValues(): void
    {
        $meter = $this->unit('meter');
        $integer = new UnitIntegerType($meter);
        $float = new UnitFloatType($meter);

        self::assertSame(
            "non-empty-list<unit_float<'meter'>>",
            $this->describe($this->resolve($float, $integer)),
        );
        self::assertSame(
            "non-empty-list<unit_float<'meter'>>",
            $this->describe($this->resolve($integer, $float)),
        );
        self::assertSame(
            "non-empty-list<unit_float<'meter'>>",
            $this->describe($this->resolve($integer, $integer, $float, targetVersion: 80300)),
        );
    }

    public function testEveryIntegerAlternativeContributesToTheGenericHull(): void
    {
        $meter = $this->unit('meter');
        $starts = new UnionType([
            UnitIntegerTypeHelper::create($meter, 10, 20),
            UnitIntegerTypeHelper::create($meter, -5, 0),
            new UnitFloatType($meter),
        ]);
        $ends = new UnionType([
            UnitIntegerTypeHelper::create($meter, 100, 120),
            UnitIntegerTypeHelper::create($meter, 200, 220),
        ]);

        self::assertSame(
            "non-empty-list<(unit_int<'meter'>&int<-5, 220>)|unit_float<'meter'>>",
            $this->describe($this->resolve($starts, $ends)),
        );
    }

    public function testExplicitStepDoesNotMakeAnOrdinarySourceUnionBenevolent(): void
    {
        $meter = $this->unit('meter');
        $start = new UnionType([
            new UnitConstantIntegerType(1, $meter),
            new UnitConstantIntegerType(2, $meter),
        ]);
        $end = new BenevolentUnionType([
            new UnitConstantIntegerType(3, $meter),
            new UnitConstantIntegerType(4, $meter),
        ]);

        $result = $this->resolve($start, $end, new UnitConstantIntegerType(1, $meter));
        self::assertInstanceOf(UnionType::class, $result);
        self::assertNotInstanceOf(BenevolentUnionType::class, $result);
    }

    public function testDefinitionallyEquivalentSpellingsAreAccepted(): void
    {
        self::assertNotNull($this->resolve(
            new UnitIntegerType($this->unit('meter')),
            new UnitIntegerType($this->unit('m')),
            new UnitIntegerType($this->unit('100 * centimeter')),
        ));
    }

    public function testOmittedStepIsContextualButExplicitBareStepIsRejected(): void
    {
        $meter = new UnitIntegerType($this->unit('meter'));

        self::assertNotNull($this->resolve($meter, $meter));
        self::assertSame(
            'Cannot call range() with unit-bearing and unbranded arguments; both endpoints and any explicit step need one definitionally equivalent unit.',
            $this->analyse($meter, $meter, new ConstantIntegerType(1))['message'],
        );
    }

    public function testMixedAndIncompatibleUnitsFailClosed(): void
    {
        $meter = new UnitIntegerType($this->unit('meter'));
        $second = new UnitIntegerType($this->unit('second'));

        self::assertSame(
            'Cannot call range() with units meter and second because they are not definitionally equivalent.',
            $this->analyse($meter, $second)['message'],
        );
        self::assertSame(
            'Cannot call range() with unit-bearing and unbranded arguments; both endpoints and any explicit step need one definitionally equivalent unit.',
            $this->analyse($meter, new IntegerType())['message'],
        );
    }

    public function testNumericStringsAndUnsupportedAlternativesAreRejected(): void
    {
        $meterUnit = $this->unit('meter');
        $meter = new UnitIntegerType($meterUnit);
        $message = 'Cannot call range() with a unit-bearing argument unless both endpoints and any explicit step are int or float unit values; cast numeric strings before constructing the range.';

        self::assertSame(
            $message,
            $this->analyse(new UnitNumericStringType($meterUnit), $meter)['message'],
        );
        self::assertSame(
            $message,
            $this->analyse($meter, new UnionType([$meter, new StringType()]))['message'],
        );
        self::assertSame(
            $message,
            $this->analyse($meter, $meter, new UnitNumericStringType($meterUnit))['message'],
        );
    }

    public function testImpossibleArgumentsLeaveTheCallToPhpStan(): void
    {
        $meter = new UnitIntegerType($this->unit('meter'));

        self::assertSame(['type' => null, 'message' => null], $this->analyse(new NeverType(), $meter));
        self::assertSame(['type' => null, 'message' => null], $this->analyse($meter, new NeverType()));
        self::assertSame(['type' => null, 'message' => null], $this->analyse($meter, $meter, new NeverType()));
    }

    public function testNamedArgumentsAreResolvedByTheirNativeNames(): void
    {
        $meter = $this->unit('meter');

        self::assertSame(
            "array{1&unit_int<'meter'>, 3&unit_int<'meter'>}",
            $this->describe($this->resolve(
                new UnitConstantIntegerType(1, $meter),
                new UnitConstantIntegerType(3, $meter),
                new UnitConstantIntegerType(2, $meter),
                named: true,
            )),
        );
    }

    /** @return array{type: ?Type, message: ?string} */
    private function analyse(
        Type $startType,
        Type $endType,
        ?Type $stepType = null,
        string $functionName = 'range',
        bool $named = false,
        int $targetVersion = PHP_VERSION_ID,
    ): array {
        $start = new Variable('start');
        $end = new Variable('end');
        $step = new Variable('step');
        $types = [
            spl_object_id($start) => $startType,
            spl_object_id($end) => $endType,
            spl_object_id($step) => $stepType,
        ];
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (Expr $expression): Type => $types[spl_object_id($expression)] ?? throw new \LogicException(),
        );
        $arguments = [
            new Arg($start, name: $named ? new Identifier('start') : null),
            new Arg($end, name: $named ? new Identifier('end') : null),
        ];
        if ($stepType !== null) {
            $arguments[] = new Arg($step, name: $named ? new Identifier('step') : null);
        }

        return $this->extension($functionName, $targetVersion)->analyseCall(
            new FuncCall(new Name($functionName), $arguments),
            $scope,
        );
    }

    private function resolve(
        Type $startType,
        Type $endType,
        ?Type $stepType = null,
        bool $named = false,
        int $targetVersion = PHP_VERSION_ID,
    ): ?Type {
        return $this->analyse(
            $startType,
            $endType,
            $stepType,
            named: $named,
            targetVersion: $targetVersion,
        )['type'];
    }

    private function extension(string $functionName, int $targetVersion): UnitRangeFunctionTypeResolverExtension
    {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn($functionName);

        $reflectionProvider = self::createStub(ReflectionProvider::class);
        $reflectionProvider->method('hasFunction')->willReturn(true);
        $reflectionProvider->method('getFunction')->willReturn($function);

        return new UnitRangeFunctionTypeResolverExtension(
            $reflectionProvider,
            new PhpVersion($targetVersion, PhpVersion::SOURCE_CONFIG),
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

    private function assertExactRangeCount(
        int $expectedCount,
        int $start,
        int $end,
        int $step,
        UnitExpression $unit,
    ): void {
        $result = $this->resolve(
            new UnitConstantIntegerType($start, $unit),
            new UnitConstantIntegerType($end, $unit),
            new UnitConstantIntegerType($step, $unit),
        );

        self::assertInstanceOf(ConstantArrayType::class, $result);
        self::assertCount($expectedCount, $result->getValueTypes());
    }
}
