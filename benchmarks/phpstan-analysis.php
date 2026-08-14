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

declare(strict_types=1);

namespace jbboehr\Yumemi\Benchmarks;

const PHPSTAN_WORKLOADS = [
    'baseline',
    'bootstrap',
    'plain',
    'scalar',
    'types',
    'operators',
    'preserving',
    'extrema',
    'roots',
    'binary-math',
    'builtins',
    'helper-baseline',
    'helpers',
    'native',
    'quantity',
    'tags',
    'mixed',
];
const DEFAULT_PHPSTAN_WORKLOADS = [
    'baseline',
    'bootstrap',
    'plain',
    'scalar',
    'native',
    'quantity',
    'tags',
    'mixed',
];

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @param array<string, false|string|list<string>> $options
 */
function main(array $options): int
{
    $iterations = positiveOption($options, 'iterations', 3);
    $warmup = nonNegativeOption($options, 'warmup', 1);
    $cases = positiveOption($options, 'cases', 100);
    $workloads = workloadOption($options['workload'] ?? null);
    $root = dirname(__DIR__);
    $temporaryRoot = sys_get_temp_dir() . '/yumemi-phpstan-benchmark-' . bin2hex(random_bytes(6));

    if (!mkdir($temporaryRoot, 0700, true) && !is_dir($temporaryRoot)) {
        throw new \RuntimeException(sprintf('Unable to create benchmark directory %s.', $temporaryRoot));
    }

    printf(
        "PHPStan analysis benchmark: %d cases, %d measured iteration%s, %d warmup\n",
        $cases,
        $iterations,
        $iterations === 1 ? '' : 's',
        $warmup,
    );
    printf("Every run uses an isolated PHPStan result-cache directory.\n\n");

    $results = [];
    try {
        foreach ($workloads as $workload) {
            $fixture = $temporaryRoot . '/' . $workload . '.php';
            writeFile($fixture, renderWorkload($workload, $cases));

            $samples = [];
            for ($iteration = -$warmup; $iteration < $iterations; ++$iteration) {
                $run = $workload . '-' . ($iteration < 0 ? 'warmup-' . abs($iteration) : $iteration + 1);
                $configuration = writeConfiguration(
                    $root,
                    $temporaryRoot,
                    $run,
                    !in_array($workload, ['baseline', 'plain', 'helper-baseline'], true),
                    $workload === 'tags' || $workload === 'mixed',
                );
                $elapsed = analyseFixture($root, $configuration, $fixture);
                removeDirectory($temporaryRoot . '/cache-' . $run);
                if ($iteration >= 0) {
                    $samples[] = $elapsed;
                }
            }

            if ($samples === []) {
                throw new \LogicException('A measured benchmark workload produced no samples.');
            }
            $results[$workload] = $samples;
        }
    } finally {
        removeDirectory($temporaryRoot);
    }

    printf("%-15s %12s %12s %12s\n", 'workload', 'median', 'minimum', 'maximum');
    printf("%-15s %12s %12s %12s\n", str_repeat('-', 15), str_repeat('-', 12), str_repeat('-', 12), str_repeat('-', 12));
    foreach ($results as $workload => $samples) {
        printf(
            "%-15s %9.2f ms %9.2f ms %9.2f ms\n",
            $workload,
            median($samples) * 1000,
            min($samples) * 1000,
            max($samples) * 1000,
        );
    }

    return 0;
}

/**
 * @param array<string, false|string|list<string>> $options
 */
function positiveOption(array $options, string $name, int $default): int
{
    $value = integerOption($options, $name, $default);
    if ($value < 1) {
        throw new \InvalidArgumentException(sprintf('--%s must be a positive integer.', $name));
    }

    return $value;
}

/**
 * @param array<string, false|string|list<string>> $options
 */
function nonNegativeOption(array $options, string $name, int $default): int
{
    $value = integerOption($options, $name, $default);
    if ($value < 0) {
        throw new \InvalidArgumentException(sprintf('--%s must be a non-negative integer.', $name));
    }

    return $value;
}

/**
 * @param array<string, false|string|list<string>> $options
 */
function integerOption(array $options, string $name, int $default): int
{
    $raw = $options[$name] ?? null;
    if ($raw === null) {
        return $default;
    }
    if (!is_string($raw) || filter_var($raw, FILTER_VALIDATE_INT) === false) {
        throw new \InvalidArgumentException(sprintf('--%s must be an integer.', $name));
    }

    return (int) $raw;
}

/**
 * @param false|string|list<string>|null $raw
 *
 * @return non-empty-list<string>
 */
function workloadOption(false|string|array|null $raw): array
{
    if ($raw === null || $raw === false) {
        return DEFAULT_PHPSTAN_WORKLOADS;
    }
    if ($raw === 'all') {
        return PHPSTAN_WORKLOADS;
    }
    if (!is_string($raw)) {
        throw new \InvalidArgumentException('--workload must be a comma-separated list.');
    }

    $workloads = array_values(array_unique(array_filter(
        array_map('trim', explode(',', $raw)),
        static fn (string $workload): bool => $workload !== '',
    )));
    if ($workloads === []) {
        throw new \InvalidArgumentException('--workload must name at least one workload.');
    }

    foreach ($workloads as $workload) {
        if (!in_array($workload, PHPSTAN_WORKLOADS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown workload %s; expected one of %s.',
                $workload,
                implode(', ', PHPSTAN_WORKLOADS),
            ));
        }
    }

    return $workloads;
}

function renderWorkload(string $workload, int $cases): string
{
    $header = <<<'PHP'
<?php

declare(strict_types=1);

namespace YumemiPhpStanBenchmark;

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_factor;
use function jbboehr\Yumemi\unit_to;

PHP;

    return $header . match ($workload) {
        'baseline' => "final class BaselineMarker {}\n",
        'bootstrap' => "final class BootstrapMarker {}\n",
        'plain' => renderCases('scalar', $cases),
        'scalar' => renderCases('scalar', $cases),
        'types' => renderCases('types', $cases),
        'operators' => renderCases('operators', $cases),
        'preserving' => renderCases('preserving', $cases),
        'extrema' => renderCases('extrema', $cases),
        'roots' => renderCases('roots', $cases),
        'binary-math' => renderCases('binary-math', $cases),
        'builtins' => renderCases('builtins', $cases),
        'helper-baseline' => renderCases('helpers', $cases),
        'helpers' => renderCases('helpers', $cases),
        'native' => renderCases('native', $cases),
        'quantity' => renderCases('quantity', $cases),
        'tags' => renderCases('tags', $cases),
        'mixed' => renderMixedCases($cases),
        default => throw new \InvalidArgumentException(sprintf('Unknown workload %s.', $workload)),
    };
}

function renderCases(string $workload, int $cases, int $offset = 0): string
{
    $result = '';
    for ($index = 0; $index < $cases; ++$index) {
        $name = sprintf('%sCase%04d', str_replace('-', '_', $workload), $offset + $index);
        $result .= match ($workload) {
            'scalar' => scalarCase($name),
            'types' => typeCase($name),
            'operators' => operatorCase($name),
            'preserving' => preservingCase($name),
            'extrema' => extremaCase($name),
            'roots' => rootCase($name),
            'binary-math' => binaryMathCase($name),
            'builtins' => builtinCase($name),
            'helpers' => helperCase($name),
            'native' => nativeCase($name),
            'quantity' => quantityCase($name),
            'tags' => tagCase($name),
            default => throw new \InvalidArgumentException(sprintf('Unknown case workload %s.', $workload)),
        };
    }

    return $result;
}

function renderMixedCases(int $cases): string
{
    $workloads = ['scalar', 'native', 'quantity', 'tags'];
    $base = intdiv($cases, count($workloads));
    $remainder = $cases % count($workloads);
    $offset = 0;
    $result = '';

    foreach ($workloads as $index => $workload) {
        $count = $base + ($index < $remainder ? 1 : 0);
        $result .= renderCases($workload, $count, $offset);
        $offset += $count;
    }

    return $result;
}

function scalarCase(string $name): string
{
    return <<<PHP
/**
 * @param int<-1000, 1000> \$distance
 * @param int<1, 100>      \$duration
 */
function {$name}(int \$distance, int \$duration): float
{
    \$speed = \$distance / \$duration;
    \$magnitude = abs(\$distance);
    \$lower = min(\$distance, \$magnitude);
    \$upper = max(\$distance, \$magnitude);
    \$root = sqrt(\$duration * \$duration);

    return \$speed + \$lower + \$upper + \$root;
}

PHP;
}

function nativeCase(string $name): string
{
    return <<<PHP
/**
 * @param unit_int<'meter'>&int<-1000, 1000> \$distance
 * @param unit_int<'second'>&int<1, 100>      \$duration
 */
function {$name}(int \$distance, int \$duration): void
{
    \$speed = \$distance / \$duration;
    \$magnitude = abs(\$distance);
    \$lower = min(\$distance, \$magnitude);
    \$upper = max(\$distance, \$magnitude);
    \$root = sqrt(\$distance * \$distance);
    \$ratio = fdiv(\$distance, \$distance);
    \$remainder = fmod(\$distance, \$distance);
    \$hypotenuse = hypot(\$distance, \$distance);
    \$feet = unit_to(\$distance, 'meter', 'international_foot');
    \$factor = unit_factor('international_foot', 'meter');
    \$seconds = unit(\$duration, 'second');
}

PHP;
}

function typeCase(string $name): string
{
    return <<<PHP
/**
 * @param unit_int<'meter'>&int<-1000, 1000> \$distance
 * @param unit_int<'second'>&int<1, 100>      \$duration
 */
function {$name}(int \$distance, int \$duration): void
{
}

PHP;
}

function operatorCase(string $name): string
{
    return <<<PHP
/**
 * @param unit_int<'meter'>&int<-1000, 1000> \$distance
 * @param unit_int<'second'>&int<1, 100>      \$duration
 */
function {$name}(int \$distance, int \$duration): float
{
    \$sum = \$distance + \$distance;
    \$speed = \$distance / \$duration;
    \$area = \$distance * \$distance;
    \$scaled = \$distance * 2;

    return \$speed;
}

PHP;
}

function builtinCase(string $name): string
{
    return <<<PHP
/**
 * @param unit_int<'meter'>&int<-1000, 1000> \$distance
 */
function {$name}(int \$distance): float
{
    \$magnitude = abs(\$distance);
    \$lower = min(\$distance, \$magnitude);
    \$upper = max(\$distance, \$magnitude);
    \$root = sqrt(\$distance * \$distance);
    \$ratio = fdiv(\$distance, \$distance);
    \$remainder = fmod(\$distance, \$distance);
    \$hypotenuse = hypot(\$distance, \$distance);

    return \$root;
}

PHP;
}

function preservingCase(string $name): string
{
    return <<<PHP
/**
 * @param unit_int<'meter'>&int<-1000, 1000> \$distance
 */
function {$name}(int \$distance): int
{
    return abs(\$distance);
}

PHP;
}

function extremaCase(string $name): string
{
    return <<<PHP
/**
 * @param unit_int<'meter'>&int<-1000, 1000> \$left
 * @param unit_int<'meter'>&int<0, 2000>      \$right
 */
function {$name}(int \$left, int \$right): int
{
    \$lower = min(\$left, \$right);
    \$upper = max(\$left, \$right);

    return \$lower + \$upper;
}

PHP;
}

function rootCase(string $name): string
{
    return <<<PHP
/**
 * @param unit_int<'meter^2'>&int<0, 1000000> \$area
 */
function {$name}(int \$area): float
{
    return sqrt(\$area);
}

PHP;
}

function binaryMathCase(string $name): string
{
    return <<<PHP
/**
 * @param unit_int<'meter'>&int<1, 1000> \$left
 * @param unit_int<'meter'>&int<1, 100>  \$right
 */
function {$name}(int \$left, int \$right): float
{
    \$ratio = fdiv(\$left, \$right);
    \$remainder = fmod(\$left, \$right);
    \$hypotenuse = hypot(\$left, \$right);

    return \$ratio;
}

PHP;
}

function helperCase(string $name): string
{
    return <<<PHP
/**
 * @param int<-1000, 1000> \$distance
 * @param int<1, 100>      \$duration
 */
function {$name}(int \$distance, int \$duration): float
{
    \$meters = unit(\$distance, 'meter');
    \$seconds = unit(\$duration, 'second');
    \$feet = unit_to(\$meters, 'meter', 'international_foot');
    \$factor = unit_factor('international_foot', 'meter');

    return \$factor;
}

PHP;
}

function quantityCase(string $name): string
{
    return <<<PHP
function {$name}(Units \$units): void
{
    \$distance = \$units->quantity(100, 'meter');
    \$duration = \$units->quantity(2, 'second');
    \$speed = \$distance->div(\$duration);
    \$area = \$distance->pow(2);
    \$root = \$area->root(2);
    \$feet = \$distance->to('international_foot');
    \$sum = \$distance->add(\$feet);
    \$point = \$units->point(20, 'celsius');
    \$rise = \$units->deltaQuantity(10, 'celsius');
    \$convertedPoint = \$point->add(\$rise)->to('fahrenheit');
}

PHP;
}

function tagCase(string $name): string
{
    return <<<PHP
/**
 * @param int<0, 1000>                            \$distance
 * @yumemi-param unit_int<'meter'>&int<0, 1000>   \$distance
 * @param int<1, 100>                             \$duration
 * @yumemi-param unit_int<'second'>&int<1, 100>   \$duration
 * @return float
 * @yumemi-return unit_float<'meter / second'>
 */
function {$name}(int \$distance, int \$duration): float
{
    return \$distance / \$duration;
}

PHP;
}

function writeConfiguration(
    string $root,
    string $temporaryRoot,
    string $run,
    bool $includeExtension,
    bool $promoteTags,
): string {
    $configuration = $temporaryRoot . '/' . $run . '.neon';
    $includes = $includeExtension ? [neonString($root . '/extension.neon')] : [];
    if ($promoteTags) {
        $includes[] = neonString($root . '/yumemi-tags.neon');
    }

    $includeBlock = $includes === [] ? '' : "includes:\n" . implode(
        "\n",
        array_map(static fn (string $include): string => '    - ' . $include, $includes),
    ) . "\n";
    $cache = neonString($temporaryRoot . '/cache-' . $run);
    writeFile($configuration, <<<NEON
{$includeBlock}parameters:
    level: max
    tmpDir: {$cache}
NEON);

    return $configuration;
}

function neonString(string $value): string
{
    return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}

function analyseFixture(string $root, string $configuration, string $fixture): float
{
    $command = [
        PHP_BINARY,
        $root . '/vendor/bin/phpstan',
        'analyse',
        '--configuration=' . $configuration,
        '--no-progress',
        '--error-format=raw',
        $fixture,
    ];
    $stdoutPath = $configuration . '.stdout';
    $stderrPath = $configuration . '.stderr';
    // proc_open() requires the output argument even though these descriptors write directly to files.
    $pipes = [];
    $started = hrtime(true);
    $process = proc_open(
        $command,
        [
            1 => ['file', $stdoutPath, 'w'],
            2 => ['file', $stderrPath, 'w'],
        ],
        $pipes,
        $root,
    );
    if (!is_resource($process)) {
        throw new \RuntimeException('Unable to start PHPStan.');
    }

    $status = proc_close($process);
    $elapsed = (hrtime(true) - $started) / 1_000_000_000;

    if ($status !== 0) {
        $stdout = file_get_contents($stdoutPath);
        $stderr = file_get_contents($stderrPath);

        throw new \RuntimeException(sprintf(
            "PHPStan benchmark analysis failed with status %d.\n%s%s",
            $status,
            $stdout === false ? '' : $stdout,
            $stderr === false ? '' : $stderr,
        ));
    }

    return $elapsed;
}

/**
 * @param non-empty-list<float> $samples
 */
function median(array $samples): float
{
    sort($samples, SORT_NUMERIC);
    $middle = intdiv(count($samples), 2);

    return count($samples) % 2 === 1
        ? $samples[$middle]
        : ($samples[$middle - 1] + $samples[$middle]) / 2;
}

function writeFile(string $path, string $contents): void
{
    if (file_put_contents($path, $contents) === false) {
        throw new \RuntimeException(sprintf('Unable to write benchmark file %s.', $path));
    }
}

function removeDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        if (!$item instanceof \SplFileInfo) {
            throw new \LogicException('Recursive directory iteration returned an unexpected value.');
        }
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($directory);
}

/** @var array<string, false|string|list<string>> $options */
$options = getopt('', ['iterations:', 'warmup:', 'cases:', 'workload:']);

try {
    exit(main($options));
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
