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

use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Internal\DeserializationContext;
use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class FiberDeserializationTest extends TestCase
{
    public function testInterleavedNativeRestoresPreserveQuantityAndPointContexts(): void
    {
        $first = new Units(UnitRegistry::bundled());
        $second = new Units(UnitRegistry::bundled());
        // Keep cleanup deterministic even when testing the original shared-slot implementation.
        DeserializationContext::run(Units::default(), function () use ($first, $second): void {
            $fibers = [];
            foreach ([$first, $second] as $units) {
                $payload = serialize([
                    new SuspendingDeserializationFixture(),
                    $units->quantity(2, 'meter'),
                    $units->point(0, 'celsius'),
                    Units::default()->quantity(1, 'second'),
                ]);
                $fiber = new \Fiber(static fn (): mixed => $units->deserialize($payload));
                $fiber->start();
                $fibers[] = $fiber;
            }
            foreach ($fibers as $fiber) {
                $fiber->resume();
            }
            foreach ([$first, $second] as $index => $units) {
                $restored = $fibers[$index]->getReturn();
                $this->assertIsArray($restored);
                $this->assertInstanceOf(Quantity::class, $restored[1]);
                $this->assertInstanceOf(PointQuantity::class, $restored[2]);
                $this->assertInstanceOf(Quantity::class, $restored[3]);
                $this->assertSame($units, $restored[1]->units());
                $this->assertSame($units, $restored[2]->units());
                $this->assertSame(Units::default(), $restored[3]->units());
                $this->assertSame('200', $restored[1]->valueIn('centimeter')->toString());
                $this->assertSame('32', $restored[2]->valueIn('fahrenheit')->toString());
            }
        });
    }

    public function testRawRestoreCannotBorrowASuspendedFibersContext(): void
    {
        $units = new Units(UnitRegistry::bundled());
        $quantityPayload = serialize($units->quantity(1, 'meter'));
        $pointPayload = serialize($units->point(0, 'celsius'));
        $fiber = new \Fiber(static fn (): mixed => $units->deserialize(serialize(new SuspendingDeserializationFixture())));
        $fiber->start();
        $errors = [];
        try {
            foreach ([$quantityPayload, $pointPayload] as $payload) {
                try {
                    unserialize($payload);
                } catch (UnexpectedValueException $exception) {
                    $errors[] = $exception;
                }
            }
        } finally {
            $fiber->resume();
        }

        $this->assertCount(2, $errors);
        foreach ($errors as $error) {
            $this->assertStringContainsString('Units::deserialize()', $error->getMessage());
        }
    }
}

final class SuspendingDeserializationFixture
{
    public function __wakeup(): void
    {
        \Fiber::suspend();
    }
}
