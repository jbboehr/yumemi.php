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

use jbboehr\Yumemi\PHPStan\InvalidUnitCallRule;
use jbboehr\Yumemi\PHPStan\ShouldNotHappenException;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFactorFunctionDynamicReturnTypeExtension;
use jbboehr\Yumemi\PHPStan\UnitFunctionDynamicReturnTypeExtension;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitOperatorTypeSpecifyingExtension;
use jbboehr\Yumemi\PHPStan\UnitRegistryResultCacheMetaExtension;
use jbboehr\Yumemi\PHPStan\UnitsQuantityReturnTypeExtension;
use jbboehr\Yumemi\PHPStan\UnitToFunctionDynamicReturnTypeExtension;
use jbboehr\Yumemi\PHPStan\YumemiDocTagPromoter;
use jbboehr\Yumemi\PHPStan\YumemiTagPromotingParser;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Units;
use PhpParser\Comment\Doc;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;
use PHPUnit\Framework\TestCase;

final class ShouldNotHappenBoundaryTest extends TestCase
{
    public function testDynamicFunctionReturnBoundaryWrapsUnexpectedFailure(): void
    {
        $failure = new \UnexpectedValueException('function boundary failure');
        $scope = $this->createMock(Scope::class);
        $scope->method('getType')->willThrowException($failure);
        $call = new FuncCall(new Name('jbboehr\Yumemi\unit'), [
            new Arg(new Int_(1)),
            new Arg(new String_('meter')),
        ]);
        $extension = new UnitFunctionDynamicReturnTypeExtension(new UnitExpressionParser());
        $functionReflection = self::createStub(FunctionReflection::class);

        $this->assertWrapped($failure, static function () use ($extension, $functionReflection, $call, $scope): void {
            $extension->getTypeFromFunctionCall(
                $functionReflection,
                $call,
                $scope,
            );
        });
    }

    public function testDynamicMethodReturnBoundaryWrapsUnexpectedFailure(): void
    {
        $failure = new \UnexpectedValueException('method boundary failure');
        $scope = $this->createMock(Scope::class);
        $scope->method('getType')->willThrowException($failure);
        $call = new MethodCall(new Variable('units'), new Identifier('quantity'), [
            new Arg(new Int_(1)),
            new Arg(new String_('meter')),
        ]);
        $extension = new UnitsQuantityReturnTypeExtension(new UnitExpressionParser());
        $methodReflection = self::createStub(MethodReflection::class);

        $this->assertWrapped($failure, static function () use ($extension, $methodReflection, $call, $scope): void {
            $extension->getTypeFromMethodCall(
                $methodReflection,
                $call,
                $scope,
            );
        });
    }

    public function testRuleBoundaryWrapsUnexpectedFailure(): void
    {
        $failure = new \UnexpectedValueException('rule boundary failure');
        $scope = self::createMock(Scope::class);
        $scope->method('resolveName')->willThrowException($failure);
        $parser = new UnitExpressionParser();
        $units = Units::default();
        $rule = new InvalidUnitCallRule(
            new UnitFunctionDynamicReturnTypeExtension($parser),
            new UnitFactorFunctionDynamicReturnTypeExtension($parser, $units),
            new UnitToFunctionDynamicReturnTypeExtension($parser, $units),
        );
        $processNode = new \ReflectionMethod($rule, 'processNode');

        $this->assertWrapped($failure, static function () use ($processNode, $rule, $scope): void {
            $processNode->invoke($rule, new FuncCall(new Name('unit')), $scope);
        });
    }

    public function testOperatorBoundaryWrapsUnexpectedFailure(): void
    {
        $failure = new \UnexpectedValueException('operator boundary failure');
        $bareType = $this->createMock(Type::class);
        $bareType->method('isInteger')->willThrowException($failure);
        $parsed = (new UnitExpressionParser())->parse('meter');
        self::assertTrue($parsed->isOk());
        $unitType = new UnitIntegerType($parsed->expression());
        $extension = new UnitOperatorTypeSpecifyingExtension();

        $this->assertWrapped($failure, static function () use ($extension, $unitType, $bareType): void {
            $extension->specifyType('*', $unitType, $bareType);
        });
    }

    public function testResultCacheBoundaryWrapsUnexpectedFailure(): void
    {
        $failure = new \UnexpectedValueException('cache boundary failure');
        $registry = new class ($failure) extends UnitRegistry {
            public function __construct(
                private readonly \Throwable $failure,
            ) {
                parent::__construct();
            }

            /**
             * @return list<string>
             */
            public function names(): array
            {
                throw $this->failure;
            }
        };
        $extension = new UnitRegistryResultCacheMetaExtension($registry);

        $this->assertWrapped($failure, static function () use ($extension): void {
            $extension->getHash();
        });
    }

    public function testAnnotationTraversalBoundaryWrapsUnexpectedFailure(): void
    {
        $failure = new \UnexpectedValueException('annotation boundary failure');
        $node = new class ($failure) extends Stmt {
            public function __construct(
                private readonly \Throwable $failure,
            ) {
                parent::__construct();
            }

            public function getSubNodeNames(): array
            {
                return [];
            }

            public function getType(): string
            {
                return 'Stmt_FailingFixture';
            }

            public function getDocComment(): ?Doc
            {
                throw $this->failure;
            }
        };
        $wrappedParser = self::createStub(Parser::class);
        $wrappedParser->method('parseString')->willReturn([$node]);
        $parser = $this->promotingParser($wrappedParser);

        $this->assertWrapped($failure, static function () use ($parser): void {
            $parser->parseString('<?php');
        });
    }

    public function testWrappedParserFailuresRemainUnchanged(): void
    {
        $failure = new \UnexpectedValueException('wrapped parser failure');
        $wrappedParser = self::createStub(Parser::class);
        $wrappedParser->method('parseString')->willThrowException($failure);
        $parser = $this->promotingParser($wrappedParser);
        $operation = static fn (): array => $parser->parseString('<?php');

        try {
            $operation();
        } catch (\UnexpectedValueException $exception) {
            $this->assertSame($failure, $exception);

            return;
        }

        self::fail('Expected the wrapped parser failure to propagate.');
    }

    private function promotingParser(Parser $wrappedParser): YumemiTagPromotingParser
    {
        $promoter = (new \ReflectionClass(YumemiDocTagPromoter::class))->newInstanceWithoutConstructor();

        return new YumemiTagPromotingParser($wrappedParser, $promoter);
    }

    /**
     * @param \Closure(): void $operation
     */
    private function assertWrapped(\Throwable $failure, \Closure $operation): void
    {
        try {
            $operation();
        } catch (ShouldNotHappenException $exception) {
            $this->assertSame($failure, $exception->getPrevious());
            $this->assertStringContainsString(
                'Please open an issue on GitHub: https://github.com/jbboehr/yumemi.php/issues',
                $exception->getMessage(),
            );

            return;
        }

        self::fail('Expected the failure to be wrapped.');
    }
}
