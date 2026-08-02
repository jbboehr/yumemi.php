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

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan;

use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Parser\Parser;
use PHPStan\Testing\PHPStanTestCase;

final class IlluminateHttpStubTest extends PHPStanTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
            __DIR__ . '/../../yumemi-tags.neon',
            __DIR__ . '/illuminate-http-stub.neon',
        ];
    }

    public function testUnitTagsArePromotedByTheStubParser(): void
    {
        $parser = self::getContainer()->getService('stubParser');
        self::assertInstanceOf(Parser::class, $parser);

        $phpDocs = [];
        foreach ($parser->parseFile(__DIR__ . '/../../stubs/illuminate-http.stub') as $node) {
            if (!$node instanceof Namespace_) {
                continue;
            }

            foreach ($node->stmts as $statement) {
                if (!$statement instanceof ClassLike || $statement->name === null) {
                    continue;
                }

                foreach ($statement->getMethods() as $method) {
                    $phpDocs[$statement->name->toString() . '::' . $method->name->toString()] = $this->methodPhpDoc($method);
                }
            }
        }

        self::assertStringContainsString(
            "unit_int<'second'>|unit_float<'second'>",
            $phpDocs['PendingRequest::timeout'],
        );
        self::assertStringContainsString(
            "unit_int<'second'>|unit_float<'second'>",
            $phpDocs['PendingRequest::connectTimeout'],
        );
        self::assertStringContainsString("array<int, unit_int<'millisecond'>>|int", $phpDocs['PendingRequest::retry']);
        self::assertStringContainsString(
            "(\\Closure(int, mixed): unit_int<'millisecond'>)|unit_int<'millisecond'>",
            $phpDocs['PendingRequest::retry'],
        );
        self::assertStringContainsString("unit_int<'1024 * byte'>", $phpDocs['FileFactory::create']);
        self::assertStringContainsString("unit_int<'1024 * byte'>", $phpDocs['File::create']);
        self::assertStringContainsString(
            "@param unit_int<'1024 * byte'> \$kilobytes",
            $phpDocs['File::size'],
        );
        self::assertStringContainsString("@return unit_int<'byte'>", $phpDocs['File::getSize']);
    }

    private function methodPhpDoc(ClassMethod $method): string
    {
        $docComment = $method->getDocComment();
        self::assertNotNull($docComment);

        return $docComment->getText();
    }
}
