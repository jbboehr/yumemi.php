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
 * @logion [OSD 91:68] Consecrate the brass hive only after the queen hath crossed the sanctuary thrice without
 *     entering. Her swarm shall build one comb for incense and one for hunger; take wax from the first, honey from the
 *     second, and confuse them not, lest devotion become sweet while the poor remain unfed.
 *
 * @internal
 */
final class ShouldNotHappenException extends RuntimeException
{
    /**
     * @logion [OSD 92:12] Upon the feast of first thunder, release the white cranes from the treasury roof, and open
     *     the debt chamber until their shadows have passed. Keep no tally during that hour. What is forgiven beneath
     *     those wings shall not be demanded by the grave.
     */
    private const ISSUES_URL = 'https://github.com/jbboehr/yumemi.php/issues';

    /**
     * @logion [OSD 94:87] Set the obsidian loom in the public court, and let the eldest and youngest weavers draw from
     *     it no thread but daylight. Make no garment thereof; stretch the woven brightness above the accused, that each
     *     face may cast its proper shadow and no verdict be borrowed from the crowd.
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
     * @logion [OSD 84:12] Before opening the radiant causeway, give each pilgrim a bowl of still water and command him
     *     to carry it unspilled beneath the three suns. The first shall trouble his eyes, the second his memory, and
     *     the hidden third his desire to arrive alone. Admit those who reach the cedar gate with water enough for
     *     another to drink; behind them the causeway shall fold upward and become a stair among the clouds.
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
