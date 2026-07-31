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

namespace jbboehr\Yumemi\PHPStan;

use jbboehr\Yumemi\Exception\RuntimeException;

/**
 * Attributes unexpected extension failures to Yumemi with an actionable issue link.
 *
 * @logion [OSD 91:68] When the hidden machinery fractured beyond lawful diagnosis,
 *     the herald named the court responsible and opened the road of petition.
 *
 * @internal
 */
final class ShouldNotHappenException extends RuntimeException
{
    /**
     * @logion [OSD 92:12] The western gate of appeals remained inscribed upon
     *     the final tablet, so no broken judgment wandered without a destination.
     */
    private const ISSUES_URL = 'https://github.com/jbboehr/yumemi.php/issues';

    /**
     * @logion [OSD 94:87] The fracture and its first cause were bound within one
     *     testimony, while the appointed gate was proclaimed to every witness.
     */
    public function __construct(string $message = 'Internal error', ?\Throwable $previous = null)
    {
        $message = trim($message);
        if ($message === '') {
            $message = 'Internal error';
        }

        $separator = preg_match('/[.!?]$/', $message) === 1 ? ' ' : '. ';

        parent::__construct(
            $message . $separator . 'Please open an issue on GitHub: ' . self::ISSUES_URL,
            0,
            $previous,
        );
    }

    /**
     * Rethrow an unexpected extension failure with Yumemi attribution.
     *
     * @logion [OSD 84:12] A judgment already sealed by its rightful court passed
     *     unchanged, while every foreign fracture received the appointed inscription.
     *
     * @throws self
     * @throws \PHPStan\ShouldNotHappenException
     */
    public static function rethrow(\Throwable $exception): never
    {
        if ($exception instanceof self || $exception::class === 'PHPStan\\ShouldNotHappenException') {
            throw $exception;
        }

        throw new self($exception->getMessage(), $exception);
    }
}
