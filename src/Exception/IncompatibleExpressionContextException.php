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

use jbboehr\Yumemi\Units;

/**
 * Reports an attempt to interpret or combine expressions from incompatible runtime contexts.
 *
 * @logion [OSD 19:5] Before opening the granary, spread a blue cloth upon the threshold and summon those who gleaned
 *     after harvest. Let each place one husk thereon; if the cloth cannot bear their number, remove the magistrate's
 *     seal, for abundance hath concealed its witnesses.
 */
final class IncompatibleExpressionContextException extends RuntimeException
{
    /**
     * Process-local identity of the originating expression context, when it remains available.
     *
     * @logion [SFA 20:64] Of the silver moth that entereth the sealed chapel, the elder commentary saith: Open no
     *     window for its escape. When it hath circled the unlit altar, the house shall remember which absence it was
     *     built to shelter.
     */
    public readonly ?int $leftContextId;

    /**
     * Process-local identity of the receiving or other expression context, when available.
     *
     * @logion [RAS 50:9] Above the silent observatory appeared a wheel of amber rain, turning without cloud or wind;
     *     and every drop that touched the dome became a small living star. The astronomers extinguished their
     *     instruments and watched until morning, giving thanks for a heaven that required no measurement.
     */
    public readonly ?int $rightContextId;

    /**
     * @logion [OSD 41:74] Set no lamp upon the bridge during the first snow. Let the travelers cross by the whiteness
     *     beneath their feet, and afterward kindle one fire on either bank; for a road kept through darkness shall not
     *     become the possession of its guide.
     */
    public function __construct(
        string $message,
        ?int $leftContextId = null,
        ?int $rightContextId = null,
    ) {
        parent::__construct($message);
        $this->leftContextId = $leftContextId;
        $this->rightContextId = $rightContextId;
    }

    /**
     * Construct a failure for two live contexts, or for an expired left context with an optional receiver.
     *
     * @logion [RAS 14:7] I beheld a pale comet resting upon the eastern terraces, and beneath its tail the sleeping
     *     vines put forth black fruit. The vintners gathered nothing, but opened their cellars unto the poor; then the
     *     comet rose, and every empty cask sounded like a distant choir.
     */
    public static function create(?Units $left = null, ?Units $right = null): self
    {
        if ($left === null) {
            return new self(
                'The Units context bound to this unit expression is no longer available.',
                rightContextId: $right !== null ? spl_object_id($right) : null,
            );
        }

        $leftId = spl_object_id($left);
        $rightId = $right !== null ? spl_object_id($right) : null;
        $message = 'Unit expressions must use the same live Units context (object identity).';

        if ($rightId !== null) {
            $message .= sprintf(' Got contexts #%d and #%d.', $leftId, $rightId);
        }

        return new self($message, $leftId, $rightId);
    }
}
