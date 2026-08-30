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

namespace jbboehr\Yumemi\Tests\Differential;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

final class Udunits2CliTest extends TestCase
{
    /** @var array<string, false|string> */
    private array $environment = [];

    private string $directory;
    private string $executable;
    private string $xmlFile;

    protected function setUp(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('The executable fixture requires a POSIX-compatible platform.');
        }

        foreach (
            ['PATH', 'UDUNITS2_BIN', 'UDUNITS2_XML', 'UDUNITS_XML_DIR', 'YUMEMI_UDUNITS2_INHERITED'] as $name
        ) {
            $this->environment[$name] = getenv($name);
        }

        $this->directory = sys_get_temp_dir() . '/yumemi udunits2 cli-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->directory, 0o777, true));

        $this->xmlFile = $this->directory . '/udunits2.xml';
        self::assertNotFalse(file_put_contents($this->xmlFile, '<unit-system/>'));

        $this->executable = $this->directory . '/fake udunits2';
        self::assertNotFalse(file_put_contents($this->executable, $this->executableSource()));
        self::assertTrue(chmod($this->executable, 0o755));

        putenv('UDUNITS2_BIN=' . $this->executable);
        putenv('UDUNITS2_XML=' . $this->xmlFile);
        putenv('UDUNITS_XML_DIR');
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }

        if (isset($this->executable) && is_file($this->executable)) {
            unlink($this->executable);
        }

        if (isset($this->xmlFile) && is_file($this->xmlFile)) {
            unlink($this->xmlFile);
        }

        if (isset($this->directory) && is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function testRunsTheConfiguredExecutableWithoutAShellAndPreservesItsResult(): void
    {
        $client = Udunits2Cli::discover();
        self::assertNotNull($client);

        $result = $client->convert('1', 'meter', 'international_foot');

        self::assertSame(Udunits2Cli::CONVERTED, $result['status']);
        self::assertSame(3.5, $result['value']);
        self::assertSame(7, $result['exitCode']);
        self::assertSame("1 meter = 3.5\n", $result['stdout']);
        self::assertSame('locale=C/C', $result['stderr']);
    }

    public function testPassesArgumentsLiterallyWithoutShellExpansion(): void
    {
        $sentinel = $this->directory . '/shell-expanded';
        $from = 'meter $(touch ' . $sentinel . ')';
        $client = Udunits2Cli::discover();
        self::assertNotNull($client);

        $result = $client->convert('2', $from, 'international_foot');

        self::assertSame(Udunits2Cli::CONVERTED, $result['status']);
        self::assertSame("2 {$from} = 3.5\n", $result['stdout']);
        self::assertFileDoesNotExist($sentinel);
    }

    public function testInheritsTheCurrentEnvironmentWhileOverridingTheLocale(): void
    {
        putenv('YUMEMI_UDUNITS2_INHERITED=present');
        $client = Udunits2Cli::discover();
        self::assertNotNull($client);

        $result = $client->convert('environment', 'meter', 'international_foot');

        self::assertSame('locale=C/C; inherited=present', $result['stderr']);
    }

    /**
     * @phpstan-param 'incompatible'|'unrecognized' $input
     * @phpstan-param 'incompatible'|'unrecognized' $expectedStatus
     */
    #[DataProvider('failureClassificationProvider')]
    public function testClassifiesKnownUdunits2Failures(string $input, string $expectedStatus): void
    {
        $client = Udunits2Cli::discover();
        self::assertNotNull($client);

        $result = $client->convert($input, 'meter', 'second');

        self::assertSame($expectedStatus, $result['status']);
        self::assertNull($result['value']);
        self::assertSame(1, $result['exitCode']);
    }

    /**
     * @return iterable<string, array{'incompatible'|'unrecognized', 'incompatible'|'unrecognized'}>
     */
    public static function failureClassificationProvider(): iterable
    {
        yield 'incompatible units' => ['incompatible', Udunits2Cli::INCOMPATIBLE];
        yield 'unrecognized unit' => ['unrecognized', Udunits2Cli::UNRECOGNIZED];
    }

    public function testDiscoversAnExecutableByNameOnPath(): void
    {
        $path = $this->environment['PATH'];
        putenv('PATH=' . $this->directory . PATH_SEPARATOR . ($path === false ? '' : $path));
        putenv('UDUNITS2_BIN=' . basename($this->executable));
        $client = Udunits2Cli::discover();
        self::assertNotNull($client);

        self::assertSame(Udunits2Cli::CONVERTED, $client->convert('1', 'meter', 'international_foot')['status']);
    }

    public function testRejectsANonFiniteConversionResult(): void
    {
        $client = Udunits2Cli::discover();
        self::assertNotNull($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('UDUNITS2 returned a non-finite conversion result.');

        $client->convert('non-finite', 'meter', 'international_foot');
    }

    public function testReportsUnexpectedExitStatusAndBothOutputStreams(): void
    {
        $client = Udunits2Cli::discover();
        self::assertNotNull($client);

        try {
            $client->convert('unexpected', 'meter', 'international_foot');
            self::fail('An unexpected UDUNITS2 response should fail.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('Unexpected UDUNITS2 result (exit 23).', $exception->getMessage());
            self::assertStringContainsString("stdout:\nunexpected stdout", $exception->getMessage());
            self::assertStringContainsString("stderr:\nunexpected stderr", $exception->getMessage());
        }
    }

    public function testTranslatesTheFiveSecondTimeoutWithConversionContext(): void
    {
        $client = Udunits2Cli::discover();
        self::assertNotNull($client);

        try {
            $client->convert('timeout', 'meter', 'international_foot');
            self::fail('A timed-out UDUNITS2 process should fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'UDUNITS2 timed out while converting timeout meter to international_foot.',
                $exception->getMessage(),
            );
            self::assertInstanceOf(ProcessTimedOutException::class, $exception->getPrevious());
            self::assertSame(5.0, $exception->getPrevious()->getExceededTimeout());
        }
    }

    private function executableSource(): string
    {
        $phpBinary = PHP_BINARY;
        $expectedXmlFile = var_export($this->xmlFile, true);

        return <<<PHP
#!{$phpBinary}
<?php

if (
    \$argv[1] !== '-U'
    || \$argv[2] !== '-H'
    || \$argv[4] !== '-W'
    || \$argv[6] !== {$expectedXmlFile}
) {
    fwrite(STDERR, 'unexpected arguments');
    exit(9);
}

if (str_starts_with(\$argv[3], 'incompatible ')) {
    fwrite(STDERR, "Units are not convertible\n");
    exit(1);
}

if (str_starts_with(\$argv[3], 'unrecognized ')) {
    fwrite(STDERR, "Don't recognize input\n");
    exit(1);
}

if (str_starts_with(\$argv[3], 'non-finite ')) {
    fwrite(STDOUT, \$argv[3] . " = 1e999\n");
    exit(0);
}

if (str_starts_with(\$argv[3], 'unexpected ')) {
    fwrite(STDOUT, "unexpected stdout\n");
    fwrite(STDERR, 'unexpected stderr');
    exit(23);
}

if (str_starts_with(\$argv[3], 'timeout ')) {
    sleep(30);
    exit(0);
}

fwrite(STDOUT, \$argv[3] . " = 3.5\n");
fwrite(STDERR, 'locale=' . getenv('LANG') . '/' . getenv('LC_ALL'));
if (str_starts_with(\$argv[3], 'environment ')) {
    fwrite(STDERR, '; inherited=' . getenv('YUMEMI_UDUNITS2_INHERITED'));
}
exit(7);
PHP;
    }
}
