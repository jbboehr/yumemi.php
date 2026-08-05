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

use jbboehr\Yumemi\PHPStan\UnitFactorFunctionDynamicReturnTypeExtension;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitFunctionDynamicReturnTypeExtension;
use jbboehr\Yumemi\PHPStan\UnitToFunctionDynamicReturnTypeExtension;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\Units;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\TestCase;

final class NativeUnitCallAnalysisTest extends TestCase
{
    /** @var array{type: null, issue: null, message: null} */
    private const NEUTRAL = ['type' => null, 'issue' => null, 'message' => null];

    public function testIncompleteCallsReturnNeutralAnalysis(): void
    {
        $parser = new UnitExpressionParser();
        $units = Units::default();
        $scope = self::createStub(Scope::class);

        $unitCall = new FuncCall(new Name('unit'), [
            new Arg(new String_('meter'), name: new Identifier('unit')),
        ]);
        $factorCall = new FuncCall(new Name('unit_factor'), [
            new Arg(new String_('meter'), name: new Identifier('to')),
        ]);
        $conversionCall = new FuncCall(new Name('unit_to'), [
            new Arg(new Float_(1.0), name: new Identifier('value')),
            new Arg(new String_('meter'), name: new Identifier('to')),
        ]);

        self::assertSame(self::NEUTRAL, (new UnitFunctionDynamicReturnTypeExtension($parser))->analyseCall(
            $unitCall,
            $scope,
        ));
        self::assertSame(self::NEUTRAL, (new UnitFactorFunctionDynamicReturnTypeExtension(
            $parser,
            $units,
        ))->analyseCall($factorCall, $scope));
        self::assertSame(self::NEUTRAL, (new UnitToFunctionDynamicReturnTypeExtension(
            $parser,
            $units,
        ))->analyseCall($conversionCall, $scope));
    }

    public function testNonStringArgumentsReturnNeutralAnalysis(): void
    {
        $parser = new UnitExpressionParser();
        $units = Units::default();
        $value = new Float_(1.0);
        $integer = new Int_(1);
        $meter = new String_('meter');
        $scope = $this->scopeFor([
            [$integer, new ConstantIntegerType(1)],
            [$meter, new ConstantStringType('meter')],
        ]);

        self::assertSame(self::NEUTRAL, (new UnitFunctionDynamicReturnTypeExtension($parser))->analyseCall(
            new FuncCall(new Name('unit'), [new Arg($value), new Arg($integer)]),
            $scope,
        ));
        self::assertSame(self::NEUTRAL, (new UnitFactorFunctionDynamicReturnTypeExtension(
            $parser,
            $units,
        ))->analyseCall(
            new FuncCall(new Name('unit_factor'), [new Arg($integer), new Arg($meter)]),
            $scope,
        ));
        self::assertSame(self::NEUTRAL, (new UnitToFunctionDynamicReturnTypeExtension(
            $parser,
            $units,
        ))->analyseCall(
            new FuncCall(new Name('unit_to'), [new Arg($value), new Arg($integer), new Arg($meter)]),
            $scope,
        ));
    }

    public function testDynamicAnalysisRetainsTheNativeFallbackType(): void
    {
        $parser = new UnitExpressionParser();
        $units = Units::default();
        $dynamic = new Variable('dynamic');
        $meter = new String_('meter');
        $scope = $this->scopeFor([
            [$dynamic, new StringType()],
            [$meter, new ConstantStringType('meter')],
        ]);

        $unit = (new UnitFunctionDynamicReturnTypeExtension($parser))->analyseCall(
            new FuncCall(new Name('unit'), [new Arg(new Float_(1.0)), new Arg($dynamic)]),
            $scope,
        );
        $factor = (new UnitFactorFunctionDynamicReturnTypeExtension($parser, $units))->analyseCall(
            new FuncCall(new Name('unit_factor'), [new Arg($meter), new Arg($dynamic)]),
            $scope,
        );
        $conversion = (new UnitToFunctionDynamicReturnTypeExtension($parser, $units))->analyseCall(
            new FuncCall(new Name('unit_to'), [new Arg(new Float_(1.0)), new Arg($meter), new Arg($dynamic)]),
            $scope,
        );
        $conversionFromDynamic = (new UnitToFunctionDynamicReturnTypeExtension($parser, $units))->analyseCall(
            new FuncCall(new Name('unit_to'), [new Arg(new Float_(1.0)), new Arg($dynamic), new Arg($meter)]),
            $scope,
        );

        self::assertNull($unit['type']);
        self::assertSame('dynamic', $unit['issue']);
        self::assertNull($factor['type']);
        self::assertSame('dynamic', $factor['issue']);
        self::assertNull($conversion['type']);
        self::assertSame('dynamic', $conversion['issue']);
        self::assertNull($conversionFromDynamic['type']);
        self::assertSame('dynamic', $conversionFromDynamic['issue']);
    }

    public function testBrandedValueUnionMismatchMessageIsDeterministic(): void
    {
        $parser = new UnitExpressionParser();
        $units = Units::default();
        $value = new Variable('value');
        $from = new String_('foot');
        $to = new String_('meter');
        $foot = $parser->parse('foot')->expression();
        $meter = $parser->parse('meter')->expression();
        $second = $parser->parse('second')->expression();
        $scope = $this->scopeFor([
            [$value, new UnionType([
                new UnitFloatType($second),
                new UnitFloatType($meter),
                new UnitFloatType($foot),
            ])],
            [$from, new ConstantStringType('foot')],
            [$to, new ConstantStringType('meter')],
        ]);

        $analysis = (new UnitToFunctionDynamicReturnTypeExtension($parser, $units))->analyseCall(
            new FuncCall(new Name('unit_to'), [new Arg($value), new Arg($from), new Arg($to)]),
            $scope,
        );

        self::assertInstanceOf(ErrorType::class, $analysis['type']);
        self::assertSame('invalid', $analysis['issue']);
        self::assertSame(
            'unit_to() value unit meter does not match from unit international_foot (normalized forms differ).',
            $analysis['message'],
        );
    }

    /**
     * @param list<array{Expr, Type}> $types
     */
    private function scopeFor(array $types): Scope
    {
        $scope = $this->createMock(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static function (Expr $expression) use ($types): Type {
                foreach ($types as [$candidate, $type]) {
                    if ($candidate === $expression) {
                        return $type;
                    }
                }

                throw new \LogicException('Unexpected expression passed to Scope::getType().');
            },
        );

        return $scope;
    }
}
