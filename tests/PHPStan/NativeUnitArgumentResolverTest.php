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

use jbboehr\Yumemi\PHPStan\NativeUnitArgumentResolver;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\TestCase;

final class NativeUnitArgumentResolverTest extends TestCase
{
    public function testConstantStringsRequireTheCompleteTypeToBeFiniteStrings(): void
    {
        $this->assertSame(
            ['foot', 'meter'],
            NativeUnitArgumentResolver::constantStrings(TypeCombinator::union(
                new ConstantStringType('meter'),
                new ConstantStringType('foot'),
                new ConstantStringType('meter'),
            )),
        );
        $this->assertNull(NativeUnitArgumentResolver::constantStrings(new StringType()));
        $this->assertNull(NativeUnitArgumentResolver::constantStrings(TypeCombinator::intersect(
            new StringType(),
            new AccessoryLiteralStringType(),
        )));
        $this->assertNull(NativeUnitArgumentResolver::constantStrings(new UnionType([
            new ConstantStringType('meter'),
            new StringType(),
        ])));
        $this->assertNull(NativeUnitArgumentResolver::constantStrings(new ConstantIntegerType(1)));
    }

    public function testArgumentResolvesPositionalAndReorderedNamedArguments(): void
    {
        $value = new Arg(new Float_(1.0));
        $unit = new Arg(new String_('meter'));
        $positional = new FuncCall(new Name('unit'), [$value, $unit]);

        $this->assertSame($value, NativeUnitArgumentResolver::argument($positional, 0, 'value'));
        $this->assertSame($unit, NativeUnitArgumentResolver::argument($positional, 1, 'unit'));

        $namedUnit = new Arg(new String_('meter'), name: new Identifier('unit'));
        $namedValue = new Arg(new Float_(1.0), name: new Identifier('value'));
        $named = new FuncCall(new Name('unit'), [$namedUnit, $namedValue]);

        $this->assertSame($namedValue, NativeUnitArgumentResolver::argument($named, 0, 'value'));
        $this->assertSame($namedUnit, NativeUnitArgumentResolver::argument($named, 1, 'unit'));
        $this->assertNull(NativeUnitArgumentResolver::argument($named, 2, 'missing'));
    }

    public function testArgumentDoesNotTreatUnpackedOrDifferentlyNamedArgumentsAsPositional(): void
    {
        $unpacked = new Arg(new Variable('arguments'), unpack: true);
        $wrongName = new Arg(new String_('meter'), name: new Identifier('other'));

        $this->assertNull(NativeUnitArgumentResolver::argument(
            new FuncCall(new Name('unit'), [$unpacked]),
            0,
            'value',
        ));
        $this->assertNull(NativeUnitArgumentResolver::argument(
            new FuncCall(new Name('unit'), [$wrongName]),
            0,
            'value',
        ));
    }
}
