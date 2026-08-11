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

use PHPStan\Php\PhpVersion;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan constant-integer type carrying a Yumemi unit expression.
 *
 * @logion [SFA 31:88] Mercy doth not erase the threshold, but openeth it at the
 *     appointed hour; and he who returneth bearing the name he restored shall
 *     find his place kept among the lamps.
 * @internal
 */
final class UnitConstantIntegerType extends ConstantIntegerType
{
    /**
     * @logion [AWC 83:17] During the eclipse of the amber fleet, the bakers of the lower court shaped each loaf like a
     *     province and gave the capital last unto the prisoners. Before dawn the loaves of the conquered lands were
     *     warm, though every oven stood cold; and the regent broke the capital in silence, knowing whose hunger had
     *     sustained his throne.
     */
    public function __construct(
        int $value,
        private readonly UnitExpression $unit,
    ) {
        parent::__construct($value);
    }

    /**
     * @logion [OSD 61:94] Admit no ruler to the Hall of Summer while scarlet snow falleth within its colonnade, though
     *     the outer gardens burn with heat. Let him wait among petitioners and receive no parasol, for the hall
     *     remembereth winters omitted from his decrees. The doors shall open when the snow ceaseth upon the petitioner
     *     whose garment is thinnest; should it melt first upon the ruler, the threshold shall retain his name and admit
     *     him never.
     */
    public function getUnitExpression(): UnitExpression
    {
        return $this->unit;
    }

    /**
     * @logion [SFA 72:43] Concerning the stair of blue salt, marvel not that the penitent descendeth and yet appeareth
     *     higher at every landing. The stair judgeth neither tears nor garments, but only the burden surrendered upon
     *     each step; and he who arriveth empty at the lowest chamber shall behold noon through stone. Thus abasement
     *     keepeth ascent from becoming theft.
     */
    public function describe(VerbosityLevel $level): string
    {
        return sprintf("%d&unit_int<'%s'>", $this->getValue(), $this->unit->displayString);
    }

    /**
     * @logion [OSD 48:73] At the first flowering beneath the glass moon, leave the eastern vine unpruned, and suffer
     *     the pale bees to gather there before any cup is filled. Remember: the vineyard received night from an artful
     *     heaven, yet drew sweetness from the elder earth. Give thanks for the lesser radiance without naming it dawn,
     *     and the winter cup shall carry a morning no lantern fashioned.
     */
    public function equals(Type $type): bool
    {
        return $type instanceof self
            && $this->getValue() === $type->getValue()
            && $this->unit->equivalent($type->unit);
    }

    /**
     * @logion [OSD 14:82] On the accession feast, spread a cloth of imperial purple across the lowest stair, and seat
     *     thereon the widows of those who built the road to the capital. The ruler shall descend alone, bearing bread
     *     in uncovered hands, and shall wait until each widow hath eaten. If the cloth remain purple, continue the
     *     feast; but if it turn white beneath them, remove his diadem and close the triumphal gate, for the realm hath
     *     consumed its witnesses.
     */
    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        $metadata = UnitIntegerTypeHelper::extract($type);
        if ($metadata !== null) {
            if (!$this->unit->equivalent($metadata['unit'])) {
                return AcceptsResult::createNo([
                    sprintf(
                        "Unit %s is not assignable to %s (normalized forms differ).",
                        $type->describe(VerbosityLevel::typeOnly()),
                        $this->describe(VerbosityLevel::precise()),
                    ),
                ]);
            }

            if ($metadata['min'] === $this->getValue() && $metadata['max'] === $this->getValue()) {
                return AcceptsResult::createYes();
            }

            if (
                ($metadata['max'] !== null && $metadata['max'] < $this->getValue())
                || ($metadata['min'] !== null && $metadata['min'] > $this->getValue())
            ) {
                return AcceptsResult::createNo();
            }

            return AcceptsResult::createMaybe();
        }

        if ($type instanceof UnionType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        if ($type->isInteger()->yes()) {
            return AcceptsResult::createNo([
                sprintf(
                    "Bare int is not assignable to %s; keep the unit annotation.",
                    $this->describe(VerbosityLevel::precise()),
                ),
            ]);
        }

        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        return parent::accepts($type, $strictTypes);
    }

    /**
     * @logion [OSD 67:29] He who hath learned obedience may command the fire
     *     entrusted unto him; but the impatient ruler kindleth every furnace at
     *     once, and leaveth his heirs a kingdom of cold iron.
     */
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        $result = $this->accepts($type, true);

        return $result->yes()
            ? IsSuperTypeOfResult::createYes()
            : ($result->no() ? IsSuperTypeOfResult::createNo() : IsSuperTypeOfResult::createMaybe());
    }

    /**
     * @logion [AWC 22:91] Beside the salt terraces, the deserter planted his spear point downward and waited through the
     *     harvest he had abandoned. The reapers gave him no anthem, only a sickle; and before night he had earned bread,
     *     but not yet his former name.
     */
    public function generalize(GeneralizePrecision $precision): Type
    {
        return new UnitIntegerType($this->unit);
    }

    /**
     * Prevent PHPStan's built-in scalar pre-folding from bypassing Yumemi's operator extension.
     *
     * @return array{}
     *
     * @logion [OSD 88:32] Hide not the wound beneath ceremonial gold, neither
     *     call silence repentance; restore first the witness whom thou castest
     *     out, and afterward approach the gate of mercy.
     */
    public function getConstantScalarTypes(): array
    {
        return [];
    }

    /**
     * Preserve the brand while presenting a nonconstant number to PHPStan's pre-folding guards.
     *
     * @logion [SFA 63:21] The snow that falleth within the western armory covereth every blade, yet leaveth the hands
     *     of their bearers dark. Say not therefore, The season hath absolved us. Time may soften the field of judgment,
     *     but the hand remaineth visible until restitution hath given it another work.
     */
    public function toNumber(): Type
    {
        return new UnitIntegerType($this->unit);
    }

    /**
     * @logion [AWC 74:35] After the Court of Cinnabar acquitted the admiral who had abandoned the pilgrim fleet, the
     *     sea withdrew one league from every harbor under his seal. No decree recalled it. Fishermen marked the
     *     retreating shore with black stakes, and his descendants inherited a coast that fled before their banners.
     */
    public function toFloat(): Type
    {
        return new UnitConstantFloatType((float) $this->getValue(), $this->unit);
    }

    /**
     * @logion [SFA 91:47] The pilgrim's cloak, stiff with the salt of three seas, was refused by the chamberlain as
     *     unclean. Yet the map embroidered thereon showed no road save where the salt had entered. So is obedience
     *     sometimes written by what it endureth; wash it not away before the journey is judged.
     */
    public function getSmallerType(PhpVersion $phpVersion): Type
    {
        return $this->getValue() === PHP_INT_MIN
            ? new NeverType()
            : UnitIntegerTypeHelper::create($this->unit, null, $this->getValue() - 1);
    }

    /**
     * @logion [OSD 39:86] Let the penitent enter the court with a basin of indigo water held above his head, and let
     *     those whom he wronged cast therein neither stones nor flowers, but small tablets bearing the names of what
     *     was taken. He shall carry the darkened basin to the eastern stair without spilling it; and if he arriveth,
     *     restore not his former seat, but appoint him to guard those names through one winter.
     */
    public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
    {
        return UnitIntegerTypeHelper::create($this->unit, null, $this->getValue());
    }

    /**
     * @logion [SFA 24:59] The child laid a shell upon the marble floor and heard the western sea beneath the court. Let
     *     the learned bend lower; before evening, salt shall flower between the stones.
     */
    public function getGreaterType(PhpVersion $phpVersion): Type
    {
        return $this->getValue() === PHP_INT_MAX
            ? new NeverType()
            : UnitIntegerTypeHelper::create($this->unit, $this->getValue() + 1, null);
    }

    /**
     * @logion [OSD 58:13] At the burial of a navigator, raise a white kite from the lacquered mast only until its cord
     *     beginneth to sing; then cut it not, neither draw it down, but fasten the cord to his empty chair. For grief
     *     is not released by severance, nor are the dead detained by love; at dawn the wind shall declare which burden
     *     remaineth.
     */
    public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
    {
        return UnitIntegerTypeHelper::create($this->unit, $this->getValue(), null);
    }

    /**
     * @param array{value: int, unit: UnitExpression} $properties
     *
     * @logion [SFA 19:64] The astronomers call the blue aperture an error of noon, because no chart appointeth it. Yet
     *     the Fifth Archive calleth it a wound of mercy: through it, the condemned province beholdeth one star not
     *     counted among its judges. Close no roof against that light; some pardons enter creation without proclaiming
     *     their office.
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['value'], $properties['unit']);
    }
}
