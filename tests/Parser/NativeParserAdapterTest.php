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

namespace jbboehr\Yumemi\Tests\Parser;

use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\NativeParserAdapter;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class NativeParserAdapterTest extends TestCase
{
    public function testAvailabilityProbeDoesNotAutoloadTheNativeBackend(): void
    {
        $autoloaded = false;
        $autoloader = static function (string $class) use (&$autoloaded): void {
            if ($class === 'jbboehr\\Yumemi\\Parser\\NativeParser') {
                $autoloaded = true;
            }
        };
        spl_autoload_register($autoloader);

        try {
            self::assertFalse(NativeParserAdapter::isAvailable());
            self::assertSame('autoload_probe', Parser::parseString('autoload_probe')->toString());
            self::assertFalse($autoloaded);
        } finally {
            spl_autoload_unregister($autoloader);
        }
    }

    public function testFallsBackWhenTheExtensionIsAbsent(): void
    {
        self::assertFalse(extension_loaded('yumemi'));
        self::assertFalse(NativeParserAdapter::isAvailable());

        $ast = Parser::parseString('fallback_without_extension');

        self::assertInstanceOf(Ast\Identifier::class, $ast);
        self::assertSame('fallback_without_extension', $ast->identifier);
    }

    public function testFallsBackWhenTheNativeAbiDoesNotMatch(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 2;

                public static function isCompatible(): bool
                {
                    throw new \LogicException('The incompatible backend must not be queried.');
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    throw new \LogicException('The incompatible backend must not be called.');
                }
            }
            PHP);

        self::assertFalse(NativeParserAdapter::isAvailable());
        self::assertSame('fallback_wrong_abi', Parser::parseString('fallback_wrong_abi')->toString());
    }

    public function testFallsBackWhenTheNativeAbiHasTheWrongType(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = '1';

                public static function isCompatible(): bool
                {
                    throw new \LogicException('A loosely matching ABI must not be queried.');
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    throw new \LogicException('A loosely matching ABI must not be called.');
                }
            }
            PHP);

        self::assertFalse(NativeParserAdapter::isAvailable());
        self::assertSame('fallback_string_abi', Parser::parseString('fallback_string_abi')->toString());
    }

    public function testFallsBackWhenTheNativeLexerIsIncompatible(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 1;

                public static function isCompatible(): bool
                {
                    return false;
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    throw new \LogicException('The incompatible backend must not be called.');
                }
            }
            PHP);

        self::assertFalse(NativeParserAdapter::isAvailable());
        self::assertSame('fallback_incompatible', Parser::parseString('fallback_incompatible')->toString());
    }

    public function testSelectsACompatibleNativeBackendAndAdaptsItsAst(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 1;

                public static function isCompatible(): bool
                {
                    return true;
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    $GLOBALS['yumemi_native_parser_last_input'] = $input;

                    return [
                        'kind' => 'add',
                        'start' => 0,
                        'end' => 19,
                        'left' => [
                            'kind' => 'sub',
                            'start' => 0,
                            'end' => 7,
                            'left' => ['kind' => 'integer', 'start' => 0, 'end' => 1, 'text' => '2'],
                            'right' => ['kind' => 'decimal-number', 'start' => 4, 'end' => 7, 'text' => '1.5'],
                        ],
                        'right' => [
                            'kind' => 'at',
                            'start' => 10,
                            'end' => 19,
                            'left' => ['kind' => 'identifier', 'start' => 10, 'end' => 15, 'text' => 'scale'],
                            'right' => [
                                'kind' => 'div',
                                'start' => 18,
                                'end' => 19,
                                'left' => [
                                    'kind' => 'mul',
                                    'start' => 18,
                                    'end' => 19,
                                    'left' => ['kind' => 'integer', 'start' => null, 'end' => null, 'text' => '-1'],
                                    'right' => ['kind' => 'identifier', 'start' => 18, 'end' => 19, 'text' => 'x'],
                                ],
                                'right' => [
                                    'kind' => 'pow',
                                    'start' => 18,
                                    'end' => 19,
                                    'left' => ['kind' => 'identifier', 'start' => 18, 'end' => 19, 'text' => 'y'],
                                    'right' => ['kind' => 'integer', 'start' => null, 'end' => null, 'text' => '2'],
                                ],
                            ],
                        ],
                    ];
                }
            }

            final class NativeParseException extends \RuntimeException {}
            final class NativeLimitException extends \LengthException {}
            PHP);

        $ast = Parser::parseString('native_backend_probe');

        self::assertTrue(NativeParserAdapter::isAvailable());
        self::assertSame('native_backend_probe', $GLOBALS['yumemi_native_parser_last_input'] ?? null);
        self::assertInstanceOf(Ast\Add::class, $ast);
        self::assertSame('((2 - 1.5) + (scale @ ((-1 * x) / (y ^ 2))))', $ast->toString());
        self::assertNotNull($ast->span);
        self::assertSame(0, $ast->span->start);
        self::assertSame(19, $ast->span->end);
        self::assertInstanceOf(Ast\At::class, $ast->right);
        self::assertInstanceOf(Ast\Div::class, $ast->right->right);
        self::assertInstanceOf(Ast\Mul::class, $ast->right->right->left);
        self::assertInstanceOf(Ast\Integer_::class, $ast->right->right->left->left);
        self::assertNull($ast->right->right->left->left->span);
        self::assertInstanceOf(Ast\Pow::class, $ast->right->right->right);
    }

    public function testTranslatesNativeSyntaxErrorsIntoTheExistingPublicDiagnostic(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 1;

                public static function isCompatible(): bool
                {
                    return true;
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    throw match ($input) {
                        'meter @ )' => new NativeParseException($input, 8, 9, ')', [
                            'integer',
                            'decimal number',
                            '-',
                        ]),
                        default => new NativeParseException($input, 7, 7, 'end of file', [
                            'integer',
                            'decimal number',
                            '-',
                            'identifier',
                            '(',
                        ]),
                    };
                }
            }

            final class NativeParseException extends \RuntimeException
            {
                /** @param list<string> $expected */
                public function __construct(
                    public readonly string $input,
                    public readonly int $start,
                    public readonly int $end,
                    public readonly ?string $unexpected,
                    public readonly array $expected,
                ) {
                    parent::__construct('Native diagnostic text must not leak.');
                }
            }

            final class NativeLimitException extends \LengthException {}
            PHP);

        $this->assertSyntaxError(
            'meter @ )',
            8,
            9,
            'Syntax error, got ), but expecting integer or decimal number or - '
                . "at line 1, column 9 (byte offset 8).\n"
                . "| meter @ )\n"
                . '|         ^',
        );
        $this->assertSyntaxError(
            'meter /',
            7,
            7,
            "Syntax error, unexpected 'end of file' at line 1, column 8 (byte offset 7).\n"
                . "| meter /\n"
                . '|        ^',
        );
    }

    public function testTranslatesNativeLimitErrorsIntoTheExistingPublicException(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 1;

                public static function isCompatible(): bool
                {
                    return true;
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    throw new NativeLimitException('token-bytes', 1024, strlen($input), 0, strlen($input));
                }
            }

            final class NativeParseException extends \RuntimeException {}

            final class NativeLimitException extends \LengthException
            {
                public function __construct(
                    public readonly string $limit,
                    public readonly int $maximum,
                    public readonly int $observed,
                    public readonly ?int $start,
                    public readonly ?int $end,
                ) {
                    parent::__construct('Native limit text must not leak.');
                }
            }
            PHP);

        $input = str_repeat('x', 1025);

        try {
            Parser::parseString($input);
            self::fail('Expected the native parser limit to be translated.');
        } catch (ExpressionLimitExceededException $exception) {
            self::assertSame('token-bytes', $exception->limit);
            self::assertSame(1024, $exception->maximum);
            self::assertSame(1025, $exception->observed);
            self::assertNotNull($exception->span);
            self::assertSame(0, $exception->span->start);
            self::assertSame(1025, $exception->span->end);
            self::assertSame(
                'Unit expression exceeds the identifier or numeric token byte length limit of 1024 (observed 1025).',
                $exception->getMessage(),
            );
            self::assertInstanceOf(
                \jbboehr\Yumemi\Parser\NativeLimitException::class,
                $exception->getPrevious(),
            );
        }
    }

    public function testPreCacheInputLimitRejectsBeforeCallingTheNativeBackend(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 1;

                public static function isCompatible(): bool
                {
                    return true;
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    $GLOBALS['yumemi_native_parser_calls'] = ($GLOBALS['yumemi_native_parser_calls'] ?? 0) + 1;
                    throw new \LogicException('Oversized input reached the native backend.');
                }
            }

            final class NativeParseException extends \RuntimeException {}
            final class NativeLimitException extends \LengthException {}
            PHP);

        try {
            Parser::parseString(str_repeat('a', 4097));
            self::fail('Expected the shared pre-cache input limit to be enforced.');
        } catch (ExpressionLimitExceededException $exception) {
            self::assertSame('input-bytes', $exception->limit);
            self::assertNull($exception->span);
            self::assertSame(0, $GLOBALS['yumemi_native_parser_calls'] ?? 0);
        }
    }

    public function testNativeFailuresAreNotCachedAndLaterSuccessesAreCached(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 1;

                public static function isCompatible(): bool
                {
                    return true;
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    $GLOBALS['yumemi_native_parser_calls'] = ($GLOBALS['yumemi_native_parser_calls'] ?? 0) + 1;
                    if ($GLOBALS['yumemi_native_parser_calls'] === 1) {
                        throw new NativeParseException($input, 0, strlen($input), 'identifier', []);
                    }

                    return [
                        'kind' => 'identifier',
                        'start' => 0,
                        'end' => strlen($input),
                        'text' => $input,
                    ];
                }
            }

            final class NativeParseException extends \RuntimeException
            {
                /** @param list<string> $expected */
                public function __construct(
                    public readonly string $input,
                    public readonly int $start,
                    public readonly int $end,
                    public readonly ?string $unexpected,
                    public readonly array $expected,
                ) {
                    parent::__construct('syntax error');
                }
            }

            final class NativeLimitException extends \LengthException {}
            PHP);

        try {
            Parser::parseString('native_failure_cache_probe');
            self::fail('Expected the first native parse to fail.');
        } catch (ParseException) {
        }

        $ast = Parser::parseString('native_failure_cache_probe');

        self::assertSame('native_failure_cache_probe', $ast->toString());
        self::assertSame($ast, Parser::parseString('native_failure_cache_probe'));
        self::assertSame(2, $GLOBALS['yumemi_native_parser_calls'] ?? 0);
    }

    public function testInputByteLimitAlwaysUsesThePublicNullSpanPolicy(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 1;

                public static function isCompatible(): bool
                {
                    return true;
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    throw new NativeLimitException('input-bytes', 4096, 4097, 0, 4097);
                }
            }

            final class NativeParseException extends \RuntimeException {}

            final class NativeLimitException extends \LengthException
            {
                public function __construct(
                    public readonly string $limit,
                    public readonly int $maximum,
                    public readonly int $observed,
                    public readonly ?int $start,
                    public readonly ?int $end,
                ) {
                    parent::__construct('Native limit text must not leak.');
                }
            }
            PHP);

        try {
            NativeParserAdapter::parse('direct_native_input_limit_probe');
            self::fail('Expected the native input byte limit to be translated.');
        } catch (ExpressionLimitExceededException $exception) {
            self::assertSame('input-bytes', $exception->limit);
            self::assertSame(4096, $exception->maximum);
            self::assertSame(4097, $exception->observed);
            self::assertNull($exception->span);
        }
    }

    public function testRejectsMalformedNeutralAstNodesAtTheAdapterBoundary(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 1;

                public static function isCompatible(): bool
                {
                    return true;
                }

                /** @return array<string, mixed> */
                public static function parse(string $input): array
                {
                    $leaf = ['kind' => 'identifier', 'start' => 0, 'end' => 1, 'text' => 'x'];

                    return match ($input) {
                        'missing_kind' => [],
                        'partial_span' => ['kind' => 'identifier', 'start' => null, 'end' => 1, 'text' => 'x'],
                        'missing_text' => ['kind' => 'identifier', 'start' => 0, 'end' => 1],
                        'missing_child' => ['kind' => 'add', 'start' => 0, 'end' => 1, 'left' => $leaf],
                        'unknown_kind' => [
                            'kind' => 'future-binary',
                            'start' => 0,
                            'end' => 1,
                            'left' => $leaf,
                            'right' => $leaf,
                        ],
                    };
                }
            }

            final class NativeParseException extends \RuntimeException {}
            final class NativeLimitException extends \LengthException {}
            PHP);

        $cases = ['missing_kind', 'partial_span', 'missing_text', 'missing_child', 'unknown_kind'];
        foreach ($cases as $input) {
            try {
                NativeParserAdapter::parse($input);
                self::fail(sprintf('Expected malformed native node %s to be rejected.', $input));
            } catch (\UnexpectedValueException) {
            }
        }

        self::addToAssertionCount(count($cases));
    }

    private function assertSyntaxError(string $input, int $start, int $end, string $message): void
    {
        try {
            Parser::parseString($input);
            self::fail('Expected the native syntax error to be translated.');
        } catch (ParseException $exception) {
            self::assertSame($input, $exception->source);
            self::assertNotNull($exception->span);
            self::assertSame($start, $exception->span->start);
            self::assertSame($end, $exception->span->end);
            self::assertSame($message, $exception->getMessage());
        }
    }
}
