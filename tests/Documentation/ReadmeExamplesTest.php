<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Documentation;

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
