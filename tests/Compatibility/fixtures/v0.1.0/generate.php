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

use Composer\InstalledVersions;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;

const RELEASE = '0.1.0';
const SOURCE_REFERENCE = '80d022de4ee0b5d5f7a9656ad307c09602d85452';

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('Release persistence fixtures must be generated from the command line.');
}

if (basename(__DIR__) !== 'v' . RELEASE) {
    throw new RuntimeException('The producer directory must match its immutable release version.');
}

$autoload = $argv[1] ?? null;

if (!is_string($autoload) || $autoload === '' || !is_file($autoload)) {
    throw new RuntimeException('Usage: php generate.php /path/to/release-project/vendor/autoload.php');
}

require $autoload;

$prettyVersion = InstalledVersions::getPrettyVersion('jbboehr/yumemi');
$sourceReference = InstalledVersions::getReference('jbboehr/yumemi');

if ($prettyVersion === null || ltrim($prettyVersion, 'v') !== RELEASE) {
    throw new RuntimeException(sprintf(
        'Expected jbboehr/yumemi %s, got %s.',
        RELEASE,
        $prettyVersion ?? 'no installed package',
    ));
}

if ($sourceReference !== SOURCE_REFERENCE) {
    throw new RuntimeException(sprintf(
        'Expected source reference %s, got %s.',
        SOURCE_REFERENCE,
        $sourceReference ?? 'none',
    ));
}

$default = Units::default();
$custom = new Units(
    UnitRegistryBuilder::empty()
        ->baseUnit('credit', 'currency')
        ->define('voucher = 2 * credit')
        ->define('credit_point = credit @ 10')
        ->build(),
);
$prefix = $default->describePrefix('milli');
$unit = $default->describe('millifoot');

if ($prefix === null || $unit === null || $unit->prefixDecomposition === null) {
    throw new RuntimeException('The 0.1.0 catalog did not provide the descriptor fixtures.');
}

$cases = [
    'rational-large-negative' => [
        'kind' => 'rational',
        'value' => new Rational(gmp_init('-123456789012345678901234567890'), gmp_init(43)),
    ],
    'dimension-fixed-and-custom' => [
        'kind' => 'dimension',
        'value' => Dimension::fromNamedPowers([
            'length' => 1,
            'mass' => 2,
            'time' => -3,
            'currency' => -2,
            'information' => 3,
        ]),
    ],
    'quantity-default' => [
        'kind' => 'quantity',
        'value' => $default->quantity(new Rational(2, 7), 'international_foot / second'),
    ],
    'quantity-named-dimension' => [
        'kind' => 'quantity',
        'value' => $default->quantity(24, 'pixels'),
    ],
    'point-default-affine' => [
        'kind' => 'point-quantity',
        'value' => $default->point(new Rational(641, 2), 'fahrenheit'),
    ],
    'custom-registry-graph' => [
        'kind' => 'custom-registry-graph',
        'value' => [
            'default' => $default->quantity(1, 'meter'),
            'quantity' => $custom->quantity(3, releaseFixtureVoucherName()),
            'point' => $custom->point(4, releaseFixturePointName()),
        ],
    ],
    'prefix-descriptor-milli' => [
        'kind' => 'prefix-descriptor',
        'value' => $prefix,
    ],
    'unit-descriptor-millifoot' => [
        'kind' => 'unit-descriptor',
        'value' => $unit,
    ],
    'prefix-decomposition-millifoot' => [
        'kind' => 'prefix-decomposition',
        'value' => $unit->prefixDecomposition,
    ],
];

$serializedDirectory = __DIR__ . '/serialized';
$jsonDirectory = __DIR__ . '/json';

foreach ([$serializedDirectory, $jsonDirectory] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create fixture directory: ' . $directory);
    }
}

$inventory = [];

foreach ($cases as $id => $case) {
    $serializedPath = 'serialized/' . $id . '.b64';
    $jsonPath = 'json/' . $id . '.json';
    $serialized = base64_encode(serialize($case['value'])) . "\n";
    $json = json_encode(
        $case['value'],
        JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
    ) . "\n";

    if (file_put_contents(__DIR__ . '/' . $serializedPath, $serialized) === false) {
        throw new RuntimeException('Unable to write serialized fixture: ' . $serializedPath);
    }

    if (file_put_contents(__DIR__ . '/' . $jsonPath, $json) === false) {
        throw new RuntimeException('Unable to write JSON fixture: ' . $jsonPath);
    }

    $inventory[] = [
        'id' => $id,
        'kind' => $case['kind'],
        'serialized' => $serializedPath,
        'json' => $jsonPath,
    ];
}

$manifest = [
    'schema' => 'yumemi.release-persistence/v1',
    'release' => RELEASE,
    'sourceReference' => SOURCE_REFERENCE,
    'producer' => [
        'php' => PHP_VERSION,
        'gmp' => defined('GMP_VERSION') ? GMP_VERSION : 'unknown',
    ],
    'cases' => $inventory,
];

$manifestJson = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

if (file_put_contents(__DIR__ . '/manifest.json', $manifestJson) === false) {
    throw new RuntimeException('Unable to write fixture manifest.');
}

fwrite(STDOUT, sprintf("Generated %d persistence fixtures for Yumemi %s.\n", count($inventory), RELEASE));

function releaseFixtureVoucherName(): string
{
    return 'voucher';
}

function releaseFixturePointName(): string
{
    return 'credit_point';
}
