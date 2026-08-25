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

namespace jbboehr\Yumemi\Tests;

use jbboehr\Yumemi\Analyzer\ExpressionContextResolver;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\IncompatibleExpressionContextException;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class ExpressionContextTest extends TestCase
{
    public function testSemanticOperationsRejectAnExpressionFromAnotherContext(): void
    {
        [$left, $right] = self::differentWidgetContexts();
        $expression = $left->parse('context_widget');
        $operations = [
            'compatibility' => static fn (): bool => $right->areCompatible($expression, 'meter'),
            'conversion factor' => static fn (): mixed => $right->conversionFactor($expression, 'meter'),
            'exact conversion' => static fn (): mixed => $right->convert(1, $expression, 'meter'),
            'float conversion' => static fn (): float => $right->convertFloat(1.0, $expression, 'meter'),
            'dimension' => static fn (): Dimension => $right->dimension($expression),
            'normalization' => static fn (): mixed => $right->normalize($expression),
            'quantity construction' => static fn (): mixed => $right->quantity(1, $expression),
            'compaction target' => static fn (): mixed => $right->quantity(1, 'meter')->toCompact($expression),
        ];

        foreach ($operations as $name => $operation) {
            try {
                $operation();
                self::fail(sprintf('%s accepted a foreign expression.', $name));
            } catch (IncompatibleExpressionContextException $exception) {
                $this->assertSame(spl_object_id($left), $exception->leftContextId, $name);
                $this->assertSame(spl_object_id($right), $exception->rightContextId, $name);
            }
        }
    }

    public function testExpressionAlgebraRejectsDifferentContextsBeforeReduction(): void
    {
        [$left, $right] = self::differentWidgetContexts();
        $leftExpression = $left->parse('context_widget');
        $rightExpression = $right->parse('context_widget');

        foreach ([
            'left then right' => static fn (): mixed => $leftExpression->mul($rightExpression),
            'right then left' => static fn (): mixed => $rightExpression->mul($leftExpression),
        ] as $name => $operation) {
            try {
                $operation();
                self::fail(sprintf('%s mixed expression contexts.', $name));
            } catch (IncompatibleExpressionContextException $exception) {
                $this->assertEqualsCanonicalizing(
                    [spl_object_id($left), spl_object_id($right)],
                    [$exception->leftContextId, $exception->rightContextId],
                    $name,
                );
            }
        }
    }

    public function testCompositeDimensionRejectsDifferentContextsBeforeEvaluatingLeaves(): void
    {
        [$left, $right] = self::differentWidgetContexts();
        $expression = new Product([
            $left->unit('context_widget'),
            $right->unit('context_widget'),
        ]);

        try {
            $expression->dimension();
            self::fail('Composite dimension evaluation mixed expression contexts.');
        } catch (IncompatibleExpressionContextException $exception) {
            $this->assertEqualsCanonicalizing(
                [spl_object_id($left), spl_object_id($right)],
                [$exception->leftContextId, $exception->rightContextId],
            );
        }
    }

    public function testBindingAnAlreadyBoundCompositePreservesItsIdentity(): void
    {
        $units = Units::default();

        foreach (['meter * second', 'meter ^ 2'] as $input) {
            $expression = $units->parse($input);

            $this->assertSame(
                $expression,
                ExpressionContextResolver::bind($expression, $units),
                $input,
            );
        }
    }

    public function testExpiredExpressionContextFailsClosed(): void
    {
        $units = new Units(UnitRegistryBuilder::empty()
            ->baseUnit('context_primitive', Dimension::CURRENCY)
            ->build());
        $expression = $units->unit('context_primitive');
        $reference = \WeakReference::create($units);
        unset($units);
        gc_collect_cycles();

        $this->assertNull($reference->get());
        $this->expectException(IncompatibleExpressionContextException::class);
        $this->expectExceptionMessage('no longer available');

        $expression->reduce();
    }

    public function testUnitCannotBeReboundToAnotherContext(): void
    {
        $left = Units::default();
        $right = new Units(UnitRegistryBuilder::default()->build());
        $expression = $left->unit('meter');

        $this->assertInstanceOf(Unit::class, $expression);
        $this->expectException(IncompatibleExpressionContextException::class);

        $expression->withUnits($right);
    }

    public function testUnitCannotHideAnotherContextInItsDefinition(): void
    {
        $left = Units::default();
        $right = new Units(UnitRegistryBuilder::default()->build());
        $expression = new Unit('context_derived', $left->unit('meter'));

        $this->expectException(IncompatibleExpressionContextException::class);

        $expression->withUnits($right);
    }

    public function testUnitWithExpiredContextCannotBeRebound(): void
    {
        $left = new Units(UnitRegistryBuilder::default()->build());
        $expression = $left->unit('meter');
        $reference = \WeakReference::create($left);
        unset($left);
        gc_collect_cycles();

        $this->assertNull($reference->get());
        $this->assertInstanceOf(Unit::class, $expression);
        $right = Units::default();

        try {
            $expression->withUnits($right);
            self::fail('A unit with an expired context was rebound.');
        } catch (IncompatibleExpressionContextException $exception) {
            $this->assertStringContainsString('no longer available', $exception->getMessage());
            $this->assertNull($exception->leftContextId);
            $this->assertSame(spl_object_id($right), $exception->rightContextId);
        }
    }

    public function testReceivingContextIsReportedWhenAnExpiredExpressionIsAdmitted(): void
    {
        $left = new Units(UnitRegistryBuilder::default()->build());
        $expression = $left->unit('meter');
        $reference = \WeakReference::create($left);
        unset($left);
        gc_collect_cycles();

        $this->assertNull($reference->get());
        $right = Units::default();

        try {
            $right->dimension($expression);
            self::fail('An expression with an expired context was admitted.');
        } catch (IncompatibleExpressionContextException $exception) {
            $this->assertStringContainsString('no longer available', $exception->getMessage());
            $this->assertNull($exception->leftContextId);
            $this->assertSame(spl_object_id($right), $exception->rightContextId);
        }
    }

    public function testDerivedUnitCannotResolveItsDimensionAfterItsContextExpires(): void
    {
        $units = new Units(UnitRegistryBuilder::default()->build());
        $expression = $units->unit('meter');
        $reference = \WeakReference::create($units);
        unset($units);
        gc_collect_cycles();

        $this->assertNull($reference->get());
        $this->assertInstanceOf(Unit::class, $expression);
        $this->expectException(IncompatibleExpressionContextException::class);
        $this->expectExceptionMessage('no longer available');

        $expression->dimension();
    }

    public function testNormalizedExpressionRetainsItsContext(): void
    {
        [$left, $right] = self::differentWidgetContexts();
        $normalized = $left->normalize($left->parse('context_widget'));

        $this->expectException(IncompatibleExpressionContextException::class);

        $right->convert(1, $normalized, 'meter');
    }

    public function testQuantityUnitAccessorRetainsItsContext(): void
    {
        $left = Units::default();
        $right = new Units(UnitRegistryBuilder::default()->build());
        $expression = $left->quantity(1, 'meter')->unit();

        $this->expectException(IncompatibleExpressionContextException::class);

        $right->convert(1, $expression, 'meter');
    }

    public function testUnboundLeavesAcquireTheSingleLiveContext(): void
    {
        $units = Units::default();
        $expression = $units->unit('meter')->mul(new Unit('second'));

        $this->assertSame('length * time', $expression->dimension()->toString());
        $this->assertSame('1', $units->conversionFactor($expression, 'meter * second')->toString());
    }

    public function testStructuralOperationsRemainContextIndependent(): void
    {
        [$left, $right] = self::differentWidgetContexts();
        $leftExpression = $left->parse('context_widget');
        $rightExpression = $right->parse('context_widget');

        $this->assertTrue($leftExpression->equals($rightExpression));
        $this->assertSame('context_widget', $right->format($leftExpression));
    }

    public function testUnitsCannotBeCloned(): void
    {
        $units = new Units(UnitRegistryBuilder::default()->build());
        $clone = static fn (object $value): object => clone $value;

        $this->expectException(\Error::class);

        $this->assertNotSame($units, $clone($units));
    }

    /**
     * @return array{Units, Units}
     */
    private static function differentWidgetContexts(): array
    {
        return [
            new Units(UnitRegistryBuilder::default()
                ->define('context_widget = 2 * meter')
                ->build()),
            new Units(UnitRegistryBuilder::default()
                ->define('context_widget = 3 * meter')
                ->build()),
        ];
    }
}
