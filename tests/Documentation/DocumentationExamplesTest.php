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

namespace jbboehr\Yumemi\Tests\Documentation;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Executes every ```php block in the public documentation to prove the documented examples actually run.
 *
 * Runs each block in-process rather than spawning a PHP subprocess. Two transforms make that safe
 * (see {@see transformExample()}):
 *
 *  - `assert(EXPR)` is rewritten to `\PHPUnit\Framework\Assert::assertTrue((bool) EXPR, …)` so the
 *    documented checks run regardless of the ambient `zend.assertions` ini (which is compile-time and
 *    cannot be forced on at runtime the way a spawned `php -d` could), and register real PHPUnit
 *    assertions;
 *  - the block is wrapped in a unique namespace so its function/class declarations can't collide with
 *    other blocks or with the global declarations {@see DocumentationPhpStanExamplesTest} makes in the same
 *    process.
 */
final class DocumentationExamplesTest extends TestCase
{
    #[DataProvider('documentationPhpExampleProvider')]
    public function testDocumentationPhpExamplesExecute(string $label, string $code): void
    {
        $namespace = '__DocumentationExec_' . substr(md5($label), 0, 12);
        $php = self::transformExample($code, $namespace);

        // Isolate the block's top-level variables in the closure's scope (not $GLOBALS). The unique
        // namespace baked into $php keeps its declarations from colliding with other blocks or with
        // the global declarations DocumentationPhpStanExamplesTest makes in the same process.
        $run = static function () use ($php): void {
            eval($php);
        };

        ob_start();

        try {
            $run();
        } catch (\Throwable $exception) {
            // A failed rewritten assert() (or any other error) fails this block, with its source.
            self::fail(sprintf('%s: %s: %s', $label, $exception::class, $exception->getMessage()));
        } finally {
            $output = ob_get_clean();
        }

        // Registers an assertion for blocks that contain no assert() of their own (the PHPStan
        // examples); the rewritten assert()s already register theirs.
        self::assertIsString($output, $label);
    }

    /**
     * Rewrite a documentation example for safe in-process execution: turn `assert()` into an unconditional
     * PHPUnit assertion, then wrap everything in a unique namespace to isolate declarations.
     */
    private static function transformExample(string $code, string $namespace): string
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        if ($statements === null) {
            throw new \RuntimeException('Unable to parse documentation example.');
        }

        $printer = new Standard();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class ($printer) extends NodeVisitorAbstract {
            public function __construct(private readonly Standard $printer)
            {
            }

            public function leaveNode(Node $node): ?Node
            {
                if (
                    !$node instanceof Node\Stmt\Expression
                    || !$node->expr instanceof Node\Expr\FuncCall
                    || !$node->expr->name instanceof Node\Name
                    || $node->expr->name->toLowerString() !== 'assert'
                    || !isset($node->expr->args[0])
                    || !$node->expr->args[0] instanceof Node\Arg
                ) {
                    return null;
                }

                $condition = $node->expr->args[0]->value;

                $call = new Node\Expr\StaticCall(
                    new Node\Name\FullyQualified('PHPUnit\\Framework\\Assert'),
                    'assertTrue',
                    [
                        new Node\Arg(new Node\Expr\Cast\Bool_($condition)),
                        new Node\Arg(new Node\Scalar\String_(
                            'Documentation example failed: assert(' . $this->printer->prettyPrintExpr($condition) . ')',
                        )),
                    ],
                );

                return new Node\Stmt\Expression($call);
            }
        });

        /** @var list<Node\Stmt> $statements the assert rewrite only ever swaps one Stmt for another */
        $statements = $traverser->traverse($statements);

        // prettyPrint (not prettyPrintFile) omits the <?php tag, as eval() requires.
        return $printer->prettyPrint([new Node\Stmt\Namespace_(new Node\Name($namespace), $statements)]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function documentationPhpExampleProvider(): iterable
    {
        foreach (MarkdownExamples::phpBlocks() as $block) {
            yield $block['label'] => [$block['label'], $block['code']];
        }
    }
}
