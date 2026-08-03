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

/**
 * @phpstan-type ConversionResult array{
 *     status: 'converted'|'incompatible'|'unrecognized',
 *     value: float|null,
 *     exitCode: int,
 *     stdout: string,
 *     stderr: string
 * }
 */
final class Udunits2Cli
{
    public const CONVERTED = 'converted';
    public const INCOMPATIBLE = 'incompatible';
    public const UNRECOGNIZED = 'unrecognized';

    private const TIMEOUT_SECONDS = 5.0;

    private function __construct(
        private readonly string $binary,
        private readonly string $xmlFile,
    ) {
    }

    public static function discover(): ?self
    {
        if (!function_exists('proc_open')) {
            return null;
        }

        $configuredBinary = getenv('UDUNITS2_BIN');
        $binary = self::findExecutable(
            $configuredBinary !== false && $configuredBinary !== '' ? $configuredBinary : 'udunits2',
        );

        $configuredXml = getenv('UDUNITS2_XML');
        if ($configuredXml !== false && $configuredXml !== '') {
            $xmlFile = $configuredXml;
        } else {
            $xmlDirectory = getenv('UDUNITS_XML_DIR');
            $xmlFile = $xmlDirectory !== false && $xmlDirectory !== ''
                ? $xmlDirectory . '/udunits2.xml'
                : '';
        }

        return $binary !== null && is_file($xmlFile)
            ? new self($binary, $xmlFile)
            : null;
    }

    /**
     * @phpstan-return ConversionResult
     */
    public function convert(string $value, string $from, string $to): array
    {
        $command = [
            $this->binary,
            '-U',
            '-H',
            $value . ' ' . $from,
            '-W',
            $to,
            $this->xmlFile,
        ];

        $environment = getenv();
        $environment['LANG'] = 'C';
        $environment['LC_ALL'] = 'C';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes, null, $environment, ['bypass_shell' => true]);

        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start the UDUNITS2 executable.');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + self::TIMEOUT_SECONDS;
        $status = proc_get_status($process);

        while ($status['running']) {
            self::appendAvailableOutput($pipes[1], $stdout);
            self::appendAvailableOutput($pipes[2], $stderr);

            if (microtime(true) >= $deadline) {
                proc_terminate($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                throw new \RuntimeException(sprintf(
                    'UDUNITS2 timed out while converting %s %s to %s.',
                    $value,
                    $from,
                    $to,
                ));
            }

            usleep(10_000);
            $status = proc_get_status($process);
        }

        self::appendAvailableOutput($pipes[1], $stdout);
        self::appendAvailableOutput($pipes[2], $stderr);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $closeExitCode = proc_close($process);
        $exitCode = $status['exitcode'] >= 0 ? $status['exitcode'] : $closeExitCode;
        $output = $stdout . "\n" . $stderr;

        if (str_contains($output, 'Units are not convertible')) {
            return self::result(self::INCOMPATIBLE, null, $exitCode, $stdout, $stderr);
        }

        if (str_contains($output, 'Don\'t recognize')) {
            return self::result(self::UNRECOGNIZED, null, $exitCode, $stdout, $stderr);
        }

        if (preg_match(
            '/^\s*[^\r\n]+ = (?<value>[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?)(?:\s|$)/m',
            $stdout,
            $matches,
        ) === 1) {
            $convertedValue = (float) $matches['value'];
            if (!is_finite($convertedValue)) {
                throw new \RuntimeException('UDUNITS2 returned a non-finite conversion result.');
            }

            return self::result(self::CONVERTED, $convertedValue, $exitCode, $stdout, $stderr);
        }

        throw new \RuntimeException(sprintf(
            "Unexpected UDUNITS2 result (exit %d).\nstdout:\n%s\nstderr:\n%s",
            $exitCode,
            $stdout,
            $stderr,
        ));
    }

    private static function findExecutable(string $command): ?string
    {
        if (str_contains($command, '/')) {
            return is_file($command) && is_executable($command) ? $command : null;
        }

        $path = getenv('PATH');
        if ($path === false) {
            return null;
        }

        foreach (explode(PATH_SEPARATOR, $path) as $directory) {
            $candidate = $directory . DIRECTORY_SEPARATOR . $command;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param resource $stream
     */
    private static function appendAvailableOutput($stream, string &$output): void
    {
        $chunk = stream_get_contents($stream);
        if ($chunk !== false) {
            $output .= $chunk;
        }
    }

    /**
     * @phpstan-param 'converted'|'incompatible'|'unrecognized' $status
     * @phpstan-return ConversionResult
     */
    private static function result(
        string $status,
        ?float $value,
        int $exitCode,
        string $stdout,
        string $stderr,
    ): array {
        return [
            'status' => $status,
            'value' => $value,
            'exitCode' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
}
