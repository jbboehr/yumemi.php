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

use jbboehr\Yumemi\PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ShouldNotHappenExceptionTest extends TestCase
{
    #[DataProvider('messageProvider')]
    public function testAddsActionableIssueLink(string $message, string $expected): void
    {
        $exception = new ShouldNotHappenException($message);

        $this->assertSame(
            $expected . ' Please open an issue on GitHub: https://github.com/jbboehr/yumemi.php/issues',
            $exception->getMessage(),
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function messageProvider(): iterable
    {
        yield 'plain message' => ['Unexpected failure', 'Unexpected failure.'];
        yield 'punctuated message' => ['Unexpected failure!', 'Unexpected failure!'];
        yield 'empty message' => ['', 'Internal error.'];
    }

    public function testRethrowWrapsUnexpectedFailureAndPreservesCause(): void
    {
        $failure = new \UnexpectedValueException('fixture failure');
        $exception = $this->capture(static function () use ($failure): void {
            ShouldNotHappenException::rethrow($failure);
        });

        $this->assertInstanceOf(ShouldNotHappenException::class, $exception);
        $this->assertSame($failure, $exception->getPrevious());
        $this->assertStringStartsWith('fixture failure.', $exception->getMessage());
    }

    public function testRethrowPreservesExistingYumemiFailure(): void
    {
        $failure = new ShouldNotHappenException('fixture failure');
        $exception = $this->capture(static function () use ($failure): void {
            ShouldNotHappenException::rethrow($failure);
        });

        $this->assertSame($failure, $exception);
    }

    public function testRethrowPreservesPHPStanFailure(): void
    {
        $failure = new \PHPStan\ShouldNotHappenException('fixture failure');
        $exception = $this->capture(static function () use ($failure): void {
            ShouldNotHappenException::rethrow($failure);
        });

        $this->assertSame($failure, $exception);
    }

    /**
     * @param \Closure(): void $operation
     */
    private function capture(\Closure $operation): \Throwable
    {
        try {
            $operation();
        } catch (\Throwable $exception) {
            return $exception;
        }

        self::fail('Expected the operation to throw.');
    }
}
