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

namespace jbboehr\Yumemi\Probator;

use jbboehr\Yumemi\Exception\DivisionByZeroError;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Units;
use PhpFuzzer\Config as ProbatorConfig;

/** @var ProbatorConfig $config */

require __DIR__ . '/../vendor/autoload.php';

$units = Units::default();

$config->setAllowedExceptions([]);
$config->setMaxLen(256);
$config->addDictionary(__DIR__ . '/unit-expression.dict');
$config->setTarget(static function (string $input) use ($units): void {
    try {
        $ast = Parser::parseString($input);
    } catch (ParseException) {
        return;
    }

    $canonical = $ast->toString();

    try {
        $reparsedAst = Parser::parseString($canonical);
    } catch (\Throwable $exception) {
        throw new \Error('The parser rejected its canonical AST representation.', 0, $exception);
    }

    if ($canonical !== $reparsedAst->toString()) {
        throw new \Error(sprintf(
            'AST round trip changed from %s to %s.',
            $canonical,
            $reparsedAst->toString(),
        ));
    }

    try {
        $parsed = $units->parse($input);
    } catch (
        DivisionByZeroError
        | OverflowException
        | ParseException
        | UnitNotFoundException
        | UnsupportedSyntaxException
        | UnsupportedUnitAlgebraException
    ) {
        return;
    }

    $formatted = $units->format($parsed);

    try {
        $reparsed = $units->parse($formatted);
    } catch (\Throwable $exception) {
        throw new \Error('The runtime parser rejected its formatted expression.', 0, $exception);
    }

    if (!$parsed->equals($reparsed)) {
        throw new \Error(sprintf(
            'Runtime round trip changed from %s to %s via %s.',
            $parsed->toString(),
            $reparsed->toString(),
            $formatted,
        ));
    }
});
