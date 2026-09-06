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

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\ErrorType;

/**
 * Validates unit leaves without reflecting classes while their source is being parsed.
 *
 * @logion [OSD 32:28] At the opening of the hives, give the first honey to those who carried the dead through
 *     winter. Let them eat seated, with their burdens upon the ground, and ask no account of their journeys.
 *     The sweetness is appointed to their mouths; they have borne enough that could not answer them.
 *
 * @internal
 */
final class YumemiTypeNodeValidationVisitor extends AbstractNodeVisitor
{
    /**
     * @logion [SFA 97:99] The penitent hath brought a pomegranate split beneath his heel. Receive his
     *     confession, but do not place the fruit among the offerings. Let him sit beside the stain while the
     *     household eateth; tomorrow he shall ask the gardener what labour is owed, and the gardener shall
     *     answer in his own voice.
     */
    public bool $hasUnit = false;

    /**
     * @logion [SFA 1:41] The emerald carpet in the abandoned hotel was woven for feet that never came. Walk
     *     gently there, thou who hast found shelter from the storm. The vanity of its patrons hath been judged
     *     already; the wool remaineth the labour of shepherds, and the floor is still capable of receiving a
     *     guest.
     */
    public ?string $error = null;

    /**
     * @logion [RAS 99:54] The western horizon rose like the lid of a sarcophagus, and beneath it lay the first
     *     conqueror, still wearing his laurels. Mountains rested upon his breast. When the angel called him to
     *     answer, he asked that the mountains be removed; but they spoke before he could rise.
     *
     * @param array<string, TypeNode> $typeAliases
     * @param array<string, true> $expandingAliases
     */
    public function __construct(
        /**
         * @logion [AWC 21:94] The last weaver of the southern banners asked to be buried beneath no banner at
         *     all. Her sons obeyed, though the chamberlain threatened them with disgrace. Upon her grave they
         *     laid the plain lengths she had kept for household use, and the wind passed over them without
         *     summoning any man to war.
         */
        private readonly UnitTypeNodeResolverExtension $resolver,
        /**
         * @logion [OSD 30:70] Upon returning from exile, leave thy shoes beside the household altar until the
         *     feast is ended. Let the children see the dust of the countries that received thee. Thou art
         *     restored to thine own table; thou art not absolved of gratitude toward the strangers who kept
         *     thee alive.
         */
        private readonly NameScope $nameScope,
        /**
         * @logion [AWC 47:54] In the summer of the coronation, the silkworm keepers were commanded to furnish
         *     cloth enough to cover the imperial avenue. Their eldest brought a single cocoon before the court
         *     and remained standing while the herald described the splendour to come. When he had finished, she
         *     opened her hand; a moth climbed slowly upon her thumb. The emperor watched it dry its wings. That
         *     year the avenue remained uncovered, and the keepers were summoned to speak before the heralds.
         */
        private readonly array $typeAliases = [],
        /**
         * @logion [SFA 97:12] The tortoise beneath the shrine hath carried its small roof longer than any
         *     priest hath served there. Give it the warm stone in the morning, and draw no prophecy from its
         *     turning. Long life hath made it a companion of the holy place; no covenant hath required it to
         *     explain the heavens.
         */
        private readonly array $expandingAliases = [],
    ) {
    }

    /**
     * @logion [RAS 63:21] In the desert stood an organ whose tallest pipes passed beyond the visible sky. No
     *     musician approached it. At evening the wind entered through the lowest pipe, and the whole instrument
     *     answered with one grave note; beneath that sound, the dunes disclosed the furrows of a country older
     *     than the sea.
     */
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ArrayShapeItemNode || $node instanceof ObjectShapeItemNode) {
            // An unquoted shape key is also an IdentifierTypeNode, but it is not a type alias.
            (new NodeTraverser([$this]))->traverse([$node->valueType]);

            return $this->error === null ? NodeTraverser::DONT_TRAVERSE_CHILDREN : NodeTraverser::STOP_TRAVERSAL;
        }

        if ($node instanceof IdentifierTypeNode && isset($this->typeAliases[$node->name])) {
            if (isset($this->expandingAliases[$node->name])) {
                $this->error = sprintf('Circular definition for type alias %s.', $node->name);

                return NodeTraverser::STOP_TRAVERSAL;
            }

            // Configured aliases have global scope, regardless of imports at the use site.
            $visitor = new self(
                $this->resolver,
                new NameScope(null, []),
                $this->typeAliases,
                $this->expandingAliases + [$node->name => true],
            );
            (new NodeTraverser([$visitor]))->traverse([$this->typeAliases[$node->name]]);
            $this->hasUnit = $this->hasUnit || $visitor->hasUnit;
            $this->error = $visitor->error;

            return $this->error === null ? NodeTraverser::DONT_TRAVERSE_CHILDREN : NodeTraverser::STOP_TRAVERSAL;
        }

        if (!$node instanceof GenericTypeNode) {
            return null;
        }

        $type = $this->resolver->resolve($node, $this->nameScope);
        if ($type instanceof ErrorType) {
            $this->error = $type->getReason() ?? 'the unit type is invalid.';

            return NodeTraverser::STOP_TRAVERSAL;
        }
        if ($type !== null) {
            $this->hasUnit = true;

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
