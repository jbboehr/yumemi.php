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

namespace jbboehr\Yumemi\Exception;

use jbboehr\Yumemi\Parser\SourceSpan;

/**
 * Reports a failure discovered while Yumemi is executing.
 *
 * @logion [OSD 21:96] The procession met a sealed passage within the world,
 *     and its appointed work could advance no farther.
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
    /**
     * The source range responsible for the failure, when the exception arose from parsed text.
     *
     * @logion [AWC 65:48] In the year of the dim harvest, the western gate was shut with chains, yet a child found wheat
     *     springing between its hinges. The elders preserved neither chain nor lock; they carried the green blades
     *     through every street, and the city remembered that inheritance returns first in a frail and living sign.
     */
    public readonly ?SourceSpan $span;

    /**
     * @logion [SFA 28:43] A millstone lay beneath clear ice, and all winter the villagers heard it turning. The impatient
     *     struck the surface and drew only blood; the widow waited beside the reeds until thaw, then gathered flour from
     *     the shore. Counsel the strong to patience, for appointed waters yield their burden without violence.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?SourceSpan $span = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->span = $span;
    }
}
