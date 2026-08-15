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

use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitUnionTypeHelper;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\TestCase;

final class UnitUnionTypeHelperTest extends TestCase
{
    public function testReturnsOnlyDirectTopLevelAlternatives(): void
    {
        $parsed = (new UnitExpressionParser())->parse('second');
        self::assertTrue($parsed->isOk());

        $brand = new UnitIntegerType($parsed->expression());
        $closure = new ClosureType([], $brand, false);
        $array = new ArrayType(new IntegerType(), $brand);
        $union = new UnionType([$closure, $array]);

        self::assertSame([$closure], UnitUnionTypeHelper::directAlternatives($closure));
        self::assertSame([$array], UnitUnionTypeHelper::directAlternatives($array));
        self::assertSame($union->getTypes(), UnitUnionTypeHelper::directAlternatives($union));
    }

    public function testReturnsDirectAlternativesFromBenevolentUnion(): void
    {
        $source = new BenevolentUnionType([
            new IntegerType(),
            new StringType(),
        ]);

        self::assertSame($source->getTypes(), UnitUnionTypeHelper::directAlternatives($source));
    }

    public function testCombinesOrdinaryMappedResultsWithoutAddingBenevolence(): void
    {
        $result = UnitUnionTypeHelper::combineMapped(
            [new ConstantIntegerType(1), new StringType()],
            new IntegerType(),
        );

        self::assertInstanceOf(UnionType::class, $result);
        self::assertNotInstanceOf(BenevolentUnionType::class, $result);
    }

    public function testPreservesBenevolenceWhenEveryUnionSourceIsBenevolent(): void
    {
        $left = new BenevolentUnionType([
            new IntegerType(),
            new StringType(),
        ]);
        $right = new BenevolentUnionType([
            new FloatType(),
            new StringType(),
        ]);

        $result = UnitUnionTypeHelper::combineMapped(
            [new ConstantIntegerType(1), new StringType()],
            $left,
            $right,
        );

        self::assertInstanceOf(BenevolentUnionType::class, $result);
    }

    public function testOrdinaryUnionSourceOverridesBenevolence(): void
    {
        $benevolentSource = new BenevolentUnionType([
            new IntegerType(),
            new StringType(),
        ]);
        $ordinarySource = new UnionType([
            new FloatType(),
            new StringType(),
        ]);
        $benevolentResult = new BenevolentUnionType([
            new IntegerType(),
            new StringType(),
        ]);

        $mappedResults = UnitUnionTypeHelper::combineMapped(
            [new ConstantIntegerType(1), new StringType()],
            $benevolentSource,
            $ordinarySource,
        );
        $alreadyBenevolentResult = UnitUnionTypeHelper::combineMapped(
            [$benevolentResult],
            $ordinarySource,
        );

        self::assertInstanceOf(UnionType::class, $mappedResults);
        self::assertNotInstanceOf(BenevolentUnionType::class, $mappedResults);
        self::assertInstanceOf(UnionType::class, $alreadyBenevolentResult);
        self::assertNotInstanceOf(BenevolentUnionType::class, $alreadyBenevolentResult);
    }

    public function testDoesNotWrapAResultCollapsedToOneType(): void
    {
        $source = new BenevolentUnionType([
            new IntegerType(),
            new StringType(),
        ]);

        $result = UnitUnionTypeHelper::combineMapped(
            [new IntegerType(), new ConstantIntegerType(1)],
            $source,
        );

        self::assertSame(IntegerType::class, $result::class);
    }
}
