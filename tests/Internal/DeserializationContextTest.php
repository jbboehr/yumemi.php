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

namespace jbboehr\Yumemi\Tests\Internal;

use jbboehr\Yumemi\Internal\DeserializationContext;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeserializationContextTest extends TestCase
{
    #[DataProvider('completionOrderProvider')]
    public function testOverlappingFibersKeepTheirOwnContexts(bool $firstFinishesFirst): void
    {
        $main = new Units(UnitRegistry::bundled());
        $first = new Units(UnitRegistry::bundled());
        $second = new Units(UnitRegistry::bundled());

        DeserializationContext::run($main, function () use ($main, $first, $second, $firstFinishesFirst): void {
            $makeFiber = static fn (Units $units): \Fiber => new \Fiber(static fn (): mixed =>
                DeserializationContext::run($units, static function (): ?Units {
                    \Fiber::suspend();
                    return DeserializationContext::current();
                }));
            $left = $makeFiber($first);
            $right = $makeFiber($second);
            $left->start();
            $right->start();
            $mainWhileSuspended = DeserializationContext::current();
            foreach ($firstFinishesFirst ? [$left, $right] : [$right, $left] as $fiber) {
                $fiber->resume();
            }

            $this->assertSame($main, $mainWhileSuspended);
            $this->assertSame($first, $left->getReturn());
            $this->assertSame($second, $right->getReturn());
            $this->assertSame($main, DeserializationContext::current());
        });
        $this->assertNull(DeserializationContext::current());
    }

    /** @return iterable<string, array{bool}> */
    public static function completionOrderProvider(): iterable
    {
        yield 'first completes first' => [true];
        yield 'second completes first' => [false];
    }

    #[DataProvider('parentExecutionProvider')]
    public function testChildFiberDoesNotInheritTheParentContext(bool $parentIsFiber): void
    {
        $units = new Units(UnitRegistry::bundled());
        $operation = static fn (): mixed => DeserializationContext::run($units, static function (): array {
            $child = new \Fiber(static fn (): ?Units => DeserializationContext::current());
            $child->start();
            return [$child->getReturn(), DeserializationContext::current()];
        });
        if ($parentIsFiber) {
            $parent = new \Fiber($operation);
            $parent->start();
            $result = $parent->getReturn();
        } else {
            $result = $operation();
        }

        $this->assertIsArray($result);
        $this->assertTrue($result[0] === null);
        $this->assertSame($units, $result[1]);
        $this->assertNull(DeserializationContext::current());
    }

    /** @return iterable<string, array{bool}> */
    public static function parentExecutionProvider(): iterable
    {
        yield 'main execution' => [false];
        yield 'parent fiber' => [true];
    }

    public function testFailureInOneFiberPreservesAnotherSuspendedScope(): void
    {
        $main = new Units(UnitRegistry::bundled());
        $first = new Units(UnitRegistry::bundled());
        $second = new Units(UnitRegistry::bundled());
        $failure = new \RuntimeException('Restore failed.');

        DeserializationContext::run($main, function () use ($main, $first, $second, $failure): void {
            $left = new \Fiber(static fn (): mixed => DeserializationContext::run($first, static function () use ($failure): void {
                \Fiber::suspend();
                throw $failure;
            }));
            $right = new \Fiber(static fn (): mixed => DeserializationContext::run($second, static function (): ?Units {
                \Fiber::suspend();
                return DeserializationContext::current();
            }));
            $left->start();
            $right->start();
            $caught = null;
            try {
                $left->resume();
            } catch (\RuntimeException $exception) {
                $caught = $exception;
            }
            $right->resume();

            $this->assertSame($failure, $caught);
            $this->assertSame($second, $right->getReturn());
            $this->assertSame($main, DeserializationContext::current());
        });
        $this->assertNull(DeserializationContext::current());
    }

    public function testNestedFiberScopeRestoresOuterContextAfterAnException(): void
    {
        $outer = new Units(UnitRegistry::bundled());
        $inner = new Units(UnitRegistry::bundled());
        $fiber = new \Fiber(static function () use ($outer, $inner): array {
            $observations = [];
            DeserializationContext::run($outer, static function () use ($inner, &$observations): void {
                try {
                    DeserializationContext::run($inner, static function (): void {
                        \Fiber::suspend(DeserializationContext::current());
                        throw new \RuntimeException('Nested restore failed.');
                    });
                } catch (\RuntimeException) {
                    $observations[] = DeserializationContext::current();
                }
            });
            $observations[] = DeserializationContext::current();
            return $observations;
        });
        $suspended = $fiber->start();
        $fiber->resume();

        $this->assertSame($inner, $suspended);
        $this->assertSame([$outer, null], $fiber->getReturn());
        $this->assertNull(DeserializationContext::current());
    }

    public function testCompletedFiberDoesNotRetainItsContext(): void
    {
        $fiber = new \Fiber(static function (): \WeakReference {
            $units = new Units(UnitRegistry::bundled());
            $reference = \WeakReference::create($units);
            DeserializationContext::run($units, static fn (): null => null);
            return $reference;
        });
        $fiber->start();
        $reference = $fiber->getReturn();

        $this->assertInstanceOf(\WeakReference::class, $reference);
        $this->assertNull($reference->get());
        $this->assertNull(DeserializationContext::current());
    }

    public function testAbandonedSuspendedFiberReleasesItsContext(): void
    {
        $fiber = new \Fiber(static function (): void {
            $units = new Units(UnitRegistry::bundled());
            $reference = \WeakReference::create($units);
            DeserializationContext::run($units, static fn (): mixed => \Fiber::suspend($reference));
        });
        $reference = $fiber->start();
        unset($fiber);
        gc_collect_cycles();

        $this->assertInstanceOf(\WeakReference::class, $reference);
        $this->assertNull($reference->get());
        $this->assertNull(DeserializationContext::current());
    }

    public function testAbandoningOneSuspendedFiberPreservesSiblingAndMainContexts(): void
    {
        $main = new Units(UnitRegistry::bundled());
        $survivingUnits = new Units(UnitRegistry::bundled());
        $abandonedReference = null;

        DeserializationContext::run($main, function () use ($main, $survivingUnits, &$abandonedReference): void {
            $abandoned = new \Fiber(static function () use (&$abandonedReference): void {
                $units = new Units(UnitRegistry::bundled());
                $abandonedReference = \WeakReference::create($units);
                DeserializationContext::run($units, static fn (): mixed => \Fiber::suspend());
            });
            $survivor = new \Fiber(static fn (): mixed => DeserializationContext::run(
                $survivingUnits,
                static function (): ?Units {
                    \Fiber::suspend(DeserializationContext::current());

                    return DeserializationContext::current();
                },
            ));

            $abandoned->start();
            $survivingAtSuspend = $survivor->start();
            unset($abandoned);
            gc_collect_cycles();

            $this->assertInstanceOf(\WeakReference::class, $abandonedReference);
            $this->assertNull($abandonedReference->get());
            $this->assertSame($survivingUnits, $survivingAtSuspend);
            $this->assertSame($main, DeserializationContext::current());

            $survivor->resume();

            $this->assertSame($survivingUnits, $survivor->getReturn());
            $this->assertSame($main, DeserializationContext::current());
        });
        $this->assertNull(DeserializationContext::current());
    }

    public function testThrowingIntoSuspendedFiberReleasesItsContext(): void
    {
        $reference = null;
        $fiber = new \Fiber(static function () use (&$reference): void {
            $units = new Units(UnitRegistry::bundled());
            $reference = \WeakReference::create($units);
            DeserializationContext::run($units, static fn (): mixed => \Fiber::suspend());
        });
        $failure = new \RuntimeException('Deserialization cancelled.');
        $fiber->start();

        try {
            $fiber->throw($failure);
            self::fail('The Fiber should expose the injected failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
        gc_collect_cycles();

        $this->assertTrue($fiber->isTerminated());
        $this->assertInstanceOf(\WeakReference::class, $reference);
        $this->assertNull($reference->get());
        $this->assertNull(DeserializationContext::current());
    }
}
