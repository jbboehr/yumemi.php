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

namespace jbboehr\Yumemi\Tests\Extension;

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\DivisionByZeroError;
use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\InternalQuantity;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class OperatorIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        self::assertTrue(
            extension_loaded('yumemi'),
            'Run the extension integration suite with ext-yumemi loaded before Composer.',
        );
    }

    public function testQuantityExtendsTheExtensionBaseClass(): void
    {
        $base = new \ReflectionClass(InternalQuantity::class);
        $parent = (new \ReflectionClass(Quantity::class))->getParentClass();

        self::assertTrue($base->isInternal());
        self::assertTrue($base->isAbstract());
        self::assertInstanceOf(\ReflectionClass::class, $parent);
        self::assertSame(InternalQuantity::class, $parent->getName());
    }

    public function testQuantityArithmeticOperatorsDelegateToMethods(): void
    {
        $units = Units::default();
        $meters = $units->quantity(2, 'meter');
        $feet = $units->quantity(1, 'foot');
        $seconds = $units->quantity(3, 'second');

        self::assertSameQuantity($meters->add($feet), $meters + $feet);
        self::assertSameQuantity($meters->sub($feet), $meters - $feet);
        self::assertSameQuantity($meters->mul($seconds), $meters * $seconds);
        self::assertSameQuantity($meters->div($seconds), $meters / $seconds);
        self::assertSameQuantity($meters->pow(2), $meters ** 2);
        self::assertSameQuantity($meters->pow(-1), $meters ** -1);
    }

    public function testQuantityScalarOperatorsDelegateToMethods(): void
    {
        $meters = Units::default()->quantity(2, 'meter');
        $rational = new Rational(3, 2);

        self::assertSameQuantity($meters->mul(3), $meters * 3);
        self::assertSameQuantity($meters->div($rational), $meters / $rational);
        self::assertSameQuantity($meters->mul(3), 3 * $meters);
        self::assertSameQuantity($meters->mul($rational), $rational * $meters);
        self::assertSameQuantity($meters->rdiv(6), 6 / $meters);
        self::assertSameQuantity($meters->rdiv($rational), $rational / $meters);

        $scalarLeftCompound = 6;
        $scalarLeftCompound /= $meters;
        self::assertSameQuantity($meters->rdiv(6), $scalarLeftCompound);

        $scalarLeftProduct = 6;
        $scalarLeftProduct *= $meters;
        self::assertSameQuantity($meters->mul(6), $scalarLeftProduct);

        $rationalLeftCompound = $rational;
        $rationalLeftCompound /= $meters;
        self::assertSameQuantity($meters->rdiv($rational), $rationalLeftCompound);
    }

    public function testCompoundAssignmentsMatchCanonicalMethodsWithoutMutatingPriorValues(): void
    {
        $units = Units::default();
        $original = $units->quantity(2, 'meter');
        $feet = $units->quantity(3, 'foot');
        $seconds = $units->quantity(4, 'second');
        $actual = $original;

        $prior = $actual;
        $expected = $actual->add($feet);
        $actual += $feet;
        self::assertNotSame($prior, $actual);
        self::assertSameQuantity($expected, $actual);

        $prior = $actual;
        $expected = $actual->sub($feet);
        $actual -= $feet;
        self::assertNotSame($prior, $actual);
        self::assertSameQuantity($expected, $actual);

        $prior = $actual;
        $expected = $actual->mul($seconds);
        $actual *= $seconds;
        self::assertNotSame($prior, $actual);
        self::assertSameQuantity($expected, $actual);

        $prior = $actual;
        $expected = $actual->div($seconds);
        $actual /= $seconds;
        self::assertNotSame($prior, $actual);
        self::assertSameQuantity($expected, $actual);

        $prior = $actual;
        $expected = $actual->pow(-1);
        $actual **= -1;
        self::assertNotSame($prior, $actual);
        self::assertSameQuantity($expected, $actual);

        self::assertNotSame($original, $actual);
        self::assertSame('2', $original->valueToString());
        self::assertSame('meter', $original->unitToString());
    }

    public function testCompoundAssignmentHandlesAliasedAndSelfOperands(): void
    {
        $units = Units::default();
        $original = $units->quantity(2, 'meter');
        $slot = $original;
        $alias = &$slot;

        $expected = $slot->add($units->quantity(3, 'foot'));
        $slot += $units->quantity(3, 'foot');

        self::assertSame($slot, $alias);
        self::assertNotSame($original, $slot);
        self::assertSameQuantity($expected, $slot);
        self::assertSameQuantity($units->quantity(2, 'meter'), $original);

        $prior = $slot;
        $expected = $slot->add($slot);
        $slot += $slot;

        self::assertNotSame($prior, $slot);
        self::assertSameQuantity($expected, $slot);
    }

    public function testFailedCompoundAssignmentPreservesTheOriginalSlot(): void
    {
        $units = Units::default();
        $actual = $units->quantity(2, 'meter');
        $original = $actual;

        try {
            $actual += $units->quantity(3, 'second');
            self::fail('Expected incompatible compound addition to fail.');
        } catch (IncompatibleUnitException) {
        }

        self::assertSame($original, $actual);
        self::assertSameQuantity($units->quantity(2, 'meter'), $actual);
    }

    public function testChainedOperatorPrecedenceMatchesCanonicalMethods(): void
    {
        $units = Units::default();
        $meters = $units->quantity(2, 'meter');
        $feet = $units->quantity(3, 'foot');
        $seconds = $units->quantity(4, 'second');

        self::assertSameQuantity(
            $meters->add($feet)->div($seconds->pow(2)),
            ($meters + $feet) / $seconds ** 2,
        );
    }

    public function testQuantityCloneAndSerializationPreserveExtensionManagedState(): void
    {
        $quantity = Units::default()->quantity(new Rational(3, 2), 'meter / second');
        $clone = clone $quantity;
        $serialized = serialize($quantity);
        $restored = unserialize($serialized, ['allowed_classes' => true]);

        self::assertNotSame($quantity, $clone);
        self::assertSameQuantity($quantity, $clone);
        self::assertInstanceOf(Quantity::class, $restored);
        self::assertNotSame($quantity, $restored);
        self::assertSameQuantity($quantity, $restored);
        self::assertTrue($quantity->equals($restored));
    }

    public function testTemporaryQuantityOperandsRemainValid(): void
    {
        $units = Units::default();
        $meters = $units->quantity(2, 'meter');
        $seconds = $units->quantity(3, 'second');

        self::assertSameQuantity(
            $units->quantity(2, 'meter')->mul($seconds),
            $units->quantity(2, 'meter') * $seconds,
        );
        self::assertSameQuantity(
            $meters->mul($units->quantity(3, 'second')),
            $meters * $units->quantity(3, 'second'),
        );
    }

    public function testQuantityMultiplicationMatchesSourceReceiverAcrossOperandForms(): void
    {
        $units = self::multiplicationUnits();
        $left = $units->quantity(2, 'zeta_factor');
        $right = $units->quantity(3, 'alpha_factor');
        $forward = $left->mul($right);
        $reverse = $right->mul($left);

        self::assertSame('alpha_factor * zeta_factor', $forward->unitToString());
        self::assertSameQuantity($forward, $reverse, 'The fixture must expose canonical symbolic factor ordering.');

        $scenarios = [
            'named variables' => [$forward, static fn (): Quantity => $left * $right],
            'named variables reversed' => [$reverse, static fn (): Quantity => $right * $left],
            'left helper return' => [
                $forward,
                static fn (): Quantity => self::makeQuantity($units, 2, 'zeta_factor') * $right,
            ],
            'right helper return' => [
                $forward,
                static fn (): Quantity => $left * self::makeQuantity($units, 3, 'alpha_factor'),
            ],
            'both helper returns' => [
                $forward,
                static fn (): Quantity => self::makeQuantity($units, 2, 'zeta_factor')
                    * self::makeQuantity($units, 3, 'alpha_factor'),
            ],
            'left expression temporary' => [
                $forward,
                static fn (): Quantity => $left->mul(1) * $right,
            ],
            'right expression temporary' => [
                $forward,
                static fn (): Quantity => $left * $right->mul(1),
            ],
            'both expression temporaries' => [
                $forward,
                static fn (): Quantity => $left->mul(1) * $right->mul(1),
            ],
            'reversed left helper return' => [
                $reverse,
                static fn (): Quantity => self::makeQuantity($units, 3, 'alpha_factor') * $left,
            ],
            'reversed right helper return' => [
                $reverse,
                static fn (): Quantity => $right * self::makeQuantity($units, 2, 'zeta_factor'),
            ],
            'reversed both helper returns' => [
                $reverse,
                static fn (): Quantity => self::makeQuantity($units, 3, 'alpha_factor')
                    * self::makeQuantity($units, 2, 'zeta_factor'),
            ],
            'reversed left expression temporary' => [
                $reverse,
                static fn (): Quantity => $right->mul(1) * $left,
            ],
            'reversed right expression temporary' => [
                $reverse,
                static fn (): Quantity => $right * $left->mul(1),
            ],
            'reversed both expression temporaries' => [
                $reverse,
                static fn (): Quantity => $right->mul(1) * $left->mul(1),
            ],
        ];

        foreach ($scenarios as $label => [$expected, $operation]) {
            self::assertSameQuantity($expected, $operation(), $label);
        }
    }

    public function testQuantityMultiplicationCompoundAssignmentMatchesSourceReceiver(): void
    {
        $units = self::multiplicationUnits();
        $left = $units->quantity(2, 'zeta_factor');
        $right = $units->quantity(3, 'alpha_factor');

        $actual = $left;
        $prior = $actual;
        $actual *= $right;
        self::assertNotSame($prior, $actual);
        self::assertSameQuantity($left->mul($right), $actual, 'forward compound assignment');
        self::assertSameQuantity($units->quantity(2, 'zeta_factor'), $prior, 'forward prior value');

        $actual = $right;
        $prior = $actual;
        $actual *= $left;
        self::assertNotSame($prior, $actual);
        self::assertSameQuantity($right->mul($left), $actual, 'reversed compound assignment');
        self::assertSameQuantity($units->quantity(3, 'alpha_factor'), $prior, 'reversed prior value');

        $actual = $left;
        $prior = $actual;
        $actual *= self::makeQuantity($units, 3, 'alpha_factor');
        self::assertNotSame($prior, $actual);
        self::assertSameQuantity($left->mul($right), $actual, 'temporary-right compound assignment');
        self::assertSameQuantity($units->quantity(2, 'zeta_factor'), $prior, 'temporary-right prior value');
    }

    public function testUnsupportedOperandsAreRejected(): void
    {
        $meters = Units::default()->quantity(2, 'meter');

        self::assertRejectsUnsupportedOperands(static fn (): mixed => 1 + $meters);
        self::assertRejectsUnsupportedOperands(static fn (): mixed => 1 - $meters);
        self::assertRejectsUnsupportedOperands(static fn (): mixed => [] / $meters);
        self::assertRejectsUnsupportedOperands(static fn (): mixed => 2 ** $meters);
    }

    public function testScalarLeftDivisionByZeroRetainsTheMethodException(): void
    {
        $units = Units::default();

        try {
            1 / $units->quantity(0, 'meter');
            self::fail('Scalar-left division by a zero quantity must retain the method-layer exception.');
        } catch (DivisionByZeroError) {
            $this->addToAssertionCount(1);
        }
    }

    public function testCrossContextMultiplicationRetainsTheMethodException(): void
    {
        $leftContext = new Units(new Udunits2UnitRegistry());
        $rightContext = new Units(new Udunits2UnitRegistry());

        try {
            $leftContext->quantity(1, 'meter') * $rightContext->quantity(1, 'meter');
            self::fail('Cross-context operator multiplication must retain the method-layer exception.');
        } catch (IncompatibleQuantityContextException) {
            $this->addToAssertionCount(1);
        }
    }

    public function testCrossContextQuantityMultiplicationMatchesSourceReceiverFailure(): void
    {
        $sharedRegistry = UnitRegistryBuilder::default()->build();
        $contextPairs = [
            'distinct contexts sharing one registry' => [
                new Units($sharedRegistry),
                new Units($sharedRegistry),
                'meter',
                'second',
            ],
            'distinct registry semantics' => [
                new Units(UnitRegistryBuilder::empty()
                    ->baseUnit('context_factor', Dimension::CURRENCY)
                    ->build()),
                new Units(UnitRegistryBuilder::empty()
                    ->baseUnit('context_factor', Dimension::IMAGE_SAMPLE)
                    ->build()),
                'context_factor',
                'context_factor',
            ],
        ];

        foreach ($contextPairs as $fixture => [$leftUnits, $rightUnits, $leftUnit, $rightUnit]) {
            $left = $leftUnits->quantity(2, $leftUnit);
            $right = $rightUnits->quantity(3, $rightUnit);
            $scenarios = [
                'named variables' => [
                    static fn (): Quantity => $left->mul($right),
                    static fn (): Quantity => $left * $right,
                ],
                'named variables reversed' => [
                    static fn (): Quantity => $right->mul($left),
                    static fn (): Quantity => $right * $left,
                ],
                'left helper return' => [
                    static fn (): Quantity => self::makeQuantity($leftUnits, 2, $leftUnit)->mul($right),
                    static fn (): Quantity => self::makeQuantity($leftUnits, 2, $leftUnit) * $right,
                ],
                'right helper return' => [
                    static fn (): Quantity => $left->mul(self::makeQuantity($rightUnits, 3, $rightUnit)),
                    static fn (): Quantity => $left * self::makeQuantity($rightUnits, 3, $rightUnit),
                ],
                'both helper returns' => [
                    static fn (): Quantity => self::makeQuantity($leftUnits, 2, $leftUnit)
                        ->mul(self::makeQuantity($rightUnits, 3, $rightUnit)),
                    static fn (): Quantity => self::makeQuantity($leftUnits, 2, $leftUnit)
                        * self::makeQuantity($rightUnits, 3, $rightUnit),
                ],
                'left expression temporary' => [
                    static fn (): Quantity => $left->mul(1)->mul($right),
                    static fn (): Quantity => $left->mul(1) * $right,
                ],
                'right expression temporary' => [
                    static fn (): Quantity => $left->mul($right->mul(1)),
                    static fn (): Quantity => $left * $right->mul(1),
                ],
                'both expression temporaries' => [
                    static fn (): Quantity => $left->mul(1)->mul($right->mul(1)),
                    static fn (): Quantity => $left->mul(1) * $right->mul(1),
                ],
                'reversed left helper return' => [
                    static fn (): Quantity => self::makeQuantity($rightUnits, 3, $rightUnit)->mul($left),
                    static fn (): Quantity => self::makeQuantity($rightUnits, 3, $rightUnit) * $left,
                ],
                'reversed right helper return' => [
                    static fn (): Quantity => $right->mul(self::makeQuantity($leftUnits, 2, $leftUnit)),
                    static fn (): Quantity => $right * self::makeQuantity($leftUnits, 2, $leftUnit),
                ],
                'reversed both helper returns' => [
                    static fn (): Quantity => self::makeQuantity($rightUnits, 3, $rightUnit)
                        ->mul(self::makeQuantity($leftUnits, 2, $leftUnit)),
                    static fn (): Quantity => self::makeQuantity($rightUnits, 3, $rightUnit)
                        * self::makeQuantity($leftUnits, 2, $leftUnit),
                ],
                'reversed left expression temporary' => [
                    static fn (): Quantity => $right->mul(1)->mul($left),
                    static fn (): Quantity => $right->mul(1) * $left,
                ],
                'reversed right expression temporary' => [
                    static fn (): Quantity => $right->mul($left->mul(1)),
                    static fn (): Quantity => $right * $left->mul(1),
                ],
                'reversed both expression temporaries' => [
                    static fn (): Quantity => $right->mul(1)->mul($left->mul(1)),
                    static fn (): Quantity => $right->mul(1) * $left->mul(1),
                ],
            ];

            foreach ($scenarios as $scenario => [$method, $operator]) {
                $message = $fixture . ': ' . $scenario;
                self::assertSameQuantityFailure(
                    self::quantityContextFailure($method),
                    self::quantityContextFailure($operator),
                    $message,
                );
            }
        }
    }

    public function testFailedCrossContextCompoundMultiplicationPreservesTheOriginalSlotAndAlias(): void
    {
        $leftUnits = new Units(UnitRegistryBuilder::default()->build());
        $rightUnits = new Units(UnitRegistryBuilder::default()->build());
        $left = $leftUnits->quantity(2, 'meter');
        $right = $rightUnits->quantity(3, 'second');

        foreach ([[$left, $right], [$right, $left]] as $index => [$sourceLeft, $sourceRight]) {
            $actual = $sourceLeft;
            $alias = &$actual;
            $expectedFailure = self::quantityContextFailure(
                static fn (): Quantity => $sourceLeft->mul($sourceRight),
            );
            $actualFailure = self::quantityContextFailure(
                static function () use (&$actual, $sourceRight): Quantity {
                    return $actual *= $sourceRight->mul(1);
                },
            );
            $message = $index === 0 ? 'forward compound assignment' : 'reversed compound assignment';

            self::assertSameQuantityFailure($expectedFailure, $actualFailure, $message);
            self::assertSame($sourceLeft, $actual, $message);
            self::assertSame($actual, $alias, $message);
        }
    }

    private static function assertSameQuantity(Quantity $expected, Quantity $actual, string $message = ''): void
    {
        self::assertSame($expected::class, $actual::class, $message);
        self::assertSame($expected->valueToString(), $actual->valueToString(), $message);
        self::assertSame($expected->unit()->toString(), $actual->unit()->toString(), $message);
        self::assertSame($expected->unitToString(), $actual->unitToString(), $message);
        self::assertSame($expected->toString(), $actual->toString(), $message);
        self::assertSame($expected->toExpr()->toString(), $actual->toExpr()->toString(), $message);
        self::assertSame($expected->units(), $actual->units(), $message);

        $expectedNormalized = $expected->normalize();
        $actualNormalized = $actual->normalize();
        self::assertSame($expectedNormalized->valueToString(), $actualNormalized->valueToString(), $message);
        self::assertSame($expectedNormalized->unitToString(), $actualNormalized->unitToString(), $message);
        self::assertSame($expectedNormalized->toString(), $actualNormalized->toString(), $message);
    }

    private static function assertSameQuantityFailure(
        IncompatibleQuantityContextException $expected,
        IncompatibleQuantityContextException $actual,
        string $message,
    ): void {
        self::assertSame($expected::class, $actual::class, $message);
        self::assertSame($expected->getMessage(), $actual->getMessage(), $message);
        self::assertSame($expected->leftContextId, $actual->leftContextId, $message);
        self::assertSame($expected->rightContextId, $actual->rightContextId, $message);
    }

    /** @param \Closure(): Quantity $operation */
    private static function quantityContextFailure(\Closure $operation): IncompatibleQuantityContextException
    {
        try {
            $operation();
        } catch (IncompatibleQuantityContextException $exception) {
            return $exception;
        }

        self::fail('Expected quantity multiplication across Units contexts to fail.');
    }

    private static function multiplicationUnits(): Units
    {
        return new Units(UnitRegistryBuilder::empty()
            ->baseUnit('zeta_factor', Dimension::CURRENCY)
            ->baseUnit('alpha_factor', Dimension::IMAGE_SAMPLE)
            ->build());
    }

    private static function makeQuantity(Units $units, int $value, string $unit): Quantity
    {
        return $units->quantity($value, $unit);
    }

    /**
     * @param \Closure(): mixed $operation
     */
    private function assertRejectsUnsupportedOperands(\Closure $operation): void
    {
        try {
            $operation();
            self::fail('Unsupported scalar-left operators must be rejected.');
        } catch (\TypeError) {
            $this->addToAssertionCount(1);
        }
    }
}
