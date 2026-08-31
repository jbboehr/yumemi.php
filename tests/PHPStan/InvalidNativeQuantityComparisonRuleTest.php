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

use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\PHPStan\InvalidNativeQuantityComparisonRule;
use jbboehr\Yumemi\Quantity;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BinaryOp\Spaceship;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * @extends RuleTestCase<InvalidNativeQuantityComparisonRule>
 */
final class InvalidNativeQuantityComparisonRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidNativeQuantityComparisonRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }

    public function testNativeRuntimeQuantityComparisonsAreReported(): void
    {
        $message = 'Native comparison is not unit-aware for Quantity or PointQuantity; use equals(), compareTo(), '
            . 'or a named ordering method.';

        $this->analyse([__DIR__ . '/Fixtures/InvalidNativeQuantityComparisons.php'], [
            [$message, 46],
            [$message, 47],
            [$message, 50],
            [$message, 51],
            [$message, 52],
            [$message, 53],
            [$message, 54],
            [$message, 58],
            [$message, 59],
            [$message, 62],
            [$message, 63],
            [$message, 64],
            [$message, 65],
            [$message, 66],
            [$message, 68],
            [$message, 69],
            [$message, 74],
            [$message, 80],
            [$message, 86],
            [$message, 95],
            [$message, 140],
            [$message, 141],
            [$message, 142],
            [$message, 143],
            [$message, 145],
            [$message, 151],
            [$message, 152],
            [$message, 153],
            [$message, 154],
            [$message, 156],
            [$message, 168],
            [$message, 177],
        ]);
    }

    public function testCompoundRuntimeQuantityTypesAreReportedInEitherOperandPosition(): void
    {
        $quantity = new ObjectType(Quantity::class);
        $pointQuantity = new ObjectType(PointQuantity::class);
        $compoundTypes = [
            new UnionType([$quantity, new NullType()]),
            new BenevolentUnionType([$pointQuantity, new IntegerType()]),
            new IntersectionType([$quantity, new ObjectType(\JsonSerializable::class)]),
        ];

        foreach ($compoundTypes as $compoundType) {
            $this->assertComparisonError(new IntegerType(), $compoundType);
            $this->assertComparisonError($compoundType, new IntegerType());
        }
    }

    public function testNullableRuntimeQuantityPresenceChecksAreAllowedInEitherOperandPosition(): void
    {
        foreach ([Quantity::class, PointQuantity::class] as $quantityClass) {
            $nullableQuantity = new UnionType([new ObjectType($quantityClass), new NullType()]);

            foreach ([Equal::class, NotEqual::class, Identical::class, NotIdentical::class] as $operatorClass) {
                foreach (
                    [
                        [$nullableQuantity, new NullType()],
                        [new NullType(), $nullableQuantity],
                    ] as [$leftType, $rightType]
                ) {
                    $left = new Variable('left');
                    $right = new Variable('right');
                    $errors = $this->getRule()->processNode(
                        new $operatorClass($left, $right),
                        $this->scopeFor($left, $leftType, $right, $rightType),
                    );

                    self::assertSame([], $errors, $operatorClass . ' with ' . $quantityClass);
                }
            }
        }
    }

    public function testNullableRuntimeQuantitiesOrderedAgainstNullAreReported(): void
    {
        foreach ([Quantity::class, PointQuantity::class] as $quantityClass) {
            $nullableQuantity = new UnionType([new ObjectType($quantityClass), new NullType()]);

            foreach (
                [Smaller::class, SmallerOrEqual::class, Greater::class, GreaterOrEqual::class, Spaceship::class] as $operatorClass
            ) {
                $this->assertComparisonError($nullableQuantity, new NullType(), $operatorClass);
                $this->assertComparisonError(new NullType(), $nullableQuantity, $operatorClass);
            }
        }
    }

    public function testEveryUnitUnsafeComparisonOperatorReportsAQuantityInTheRightOperand(): void
    {
        $left = new Variable('left');
        $right = new Variable('right');
        $scope = $this->scopeFor($left, new IntegerType(), $right, new ObjectType(Quantity::class));

        foreach ([
            Equal::class,
            NotEqual::class,
            Smaller::class,
            SmallerOrEqual::class,
            Greater::class,
            GreaterOrEqual::class,
            Spaceship::class,
        ] as $operatorClass) {
            $errors = $this->getRule()->processNode(new $operatorClass($left, $right), $scope);

            self::assertCount(1, $errors, $operatorClass);
            self::assertSame('yumemi.nativeQuantityComparison', $errors[0]->getIdentifier());
        }
    }

    public function testStrictIdentityOperatorsRemainAvailable(): void
    {
        $left = new Variable('left');
        $right = new Variable('right');
        $quantity = new ObjectType(Quantity::class);
        $scope = $this->scopeFor($left, $quantity, $right, $quantity);

        foreach ([Identical::class, NotIdentical::class] as $operatorClass) {
            self::assertSame([], $this->getRule()->processNode(new $operatorClass($left, $right), $scope));
        }
    }

    /** @param class-string<BinaryOp> $operatorClass */
    private function assertComparisonError(
        Type $leftType,
        Type $rightType,
        string $operatorClass = Equal::class,
    ): void {
        $left = new Variable('left');
        $right = new Variable('right');
        $errors = $this->getRule()->processNode(
            new $operatorClass($left, $right),
            $this->scopeFor($left, $leftType, $right, $rightType),
        );

        self::assertCount(1, $errors);
        self::assertSame('yumemi.nativeQuantityComparison', $errors[0]->getIdentifier());
    }

    /** @return Scope&NodeCallbackInvoker&CollectedDataEmitter */
    private function scopeFor(Expr $left, Type $leftType, Expr $right, Type $rightType): Scope
    {
        $scope = $this->createMockForIntersectionOfInterfaces([
            Scope::class,
            NodeCallbackInvoker::class,
            CollectedDataEmitter::class,
        ]);
        $scope->method('getType')->willReturnCallback(
            static function (Expr $expression) use ($left, $leftType, $right, $rightType): Type {
                if ($expression === $left || ($expression instanceof Variable && $expression->name === 'left')) {
                    return $leftType;
                }

                if ($expression === $right || ($expression instanceof Variable && $expression->name === 'right')) {
                    return $rightType;
                }

                throw new \LogicException('Unexpected expression passed to Scope::getType().');
            },
        );

        return $scope;
    }
}
