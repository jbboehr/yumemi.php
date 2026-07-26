<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Tests\Documentation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReadmeExamplesTest extends TestCase
{
    #[DataProvider('readmePhpExampleProvider')]
    public function testReadmePhpExamplesExecute(string $label, string $code): void
    {
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-d', 'zend.assertions=1', '-d', 'assert.exception=1'],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            self::projectRoot(),
        );

        self::assertIsResource($process);

        fwrite($pipes[0], $code);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), $label . "\n" . trim($stderr . "\n" . $stdout));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function readmePhpExampleProvider(): iterable
    {
        $readme = self::projectRoot() . '/README.md';
        $contents = file_get_contents($readme);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read ' . $readme);
        }

        preg_match_all('/```php\s*\R(.*?)\R```/s', $contents, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            throw new \RuntimeException('README.md must contain at least one PHP example.');
        }

        foreach ($matches as $index => $match) {
            $label = sprintf('README.md PHP example %d', $index + 1);

            yield $label => [$label, $match[1]];
        }
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
