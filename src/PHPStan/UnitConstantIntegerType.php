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
     * @logion [AWC 83:17] In the winter of the glass regent, a widow kept the
     *     western bell beneath her roof; and when the thaw came, its voice
     *     awakened the road that the court had condemned.
     */
    public function __construct(
        int $value,
        private readonly UnitExpression $unit,
    ) {
        parent::__construct($value);
    }

    /**
     * @logion [OSD 61:94] Let the keeper trim the lamp before synthetic dusk,
     *     though no pilgrim standeth at the gate; for fidelity prepareth a road
     *     before the feet appointed to walk therein.
     */
    public function getUnitExpression(): UnitExpression
    {
        return $this->unit;
    }

    /**
     * @logion [SFA 72:43] The counterfeit desireth praise before witness, but
     *     lawful beauty abideth unseen; therefore judge the hidden foundation
     *     before thou crownest the radiant tower.
     */
    public function describe(VerbosityLevel $level): string
    {
        return sprintf("%d&unit_int<'%s'>", $this->getValue(), $this->unit->displayString);
    }

    /**
     * @logion [OSD 48:73] Neither the splendour of the gate nor the multitude
     *     gathered before it shall sanctify an unlawful passage; for admission
     *     belongeth unto the covenant, and acclaim followeth afterward.
     */
    public function equals(Type $type): bool
    {
        return $type instanceof self
            && $this->getValue() === $type->getValue()
            && $this->unit->equivalent($type->unit);
    }

    /**
     * @logion [OSD 14:82] Bring not the ashes of every altar into the inner
     *     shrine, saying, All fires are brethren; lest the holy flame forget its
     *     office and the city lose the hour of dawn.
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
     * @logion [AWC 22:91] The builders raised no monument unto themselves, but
     *     completed the stair their fathers had begun; and at its summit the
     *     children beheld the star promised before their birth.
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
     * @logion [SFA 63:21] The Fifth Archive remembereth many conclusions whose
     *     arguments have perished; receive them with reverence, but compel not
     *     the living to feign knowledge of the vanished pages.
     */
    public function toNumber(): Type
    {
        return new UnitIntegerType($this->unit);
    }

    /**
     * @logion [AWC 74:35] The scribes enlarged the ancient numeral upon a new
     *     tablet, and its former boundary vanished; nevertheless the witnesses
     *     knew the inheritance by the seal that endured beside it.
     */
    public function toFloat(): Type
    {
        return new UnitConstantFloatType((float) $this->getValue(), $this->unit);
    }

    /**
     * @logion [SFA 91:47] A boundary is not barren because it refuseth passage;
     *     within the vessel the storm is gathered, and from disciplined thunder
     *     the upper city receiveth light.
     */
    public function getSmallerType(PhpVersion $phpVersion): Type
    {
        return $this->getValue() === PHP_INT_MIN
            ? new NeverType()
            : UnitIntegerTypeHelper::create($this->unit, null, $this->getValue() - 1);
    }

    /**
     * @logion [OSD 39:86] Blessed are they who preserve the ancient instrument
     *     after its makers sleep, for in the appointed generation its silent
     *     strings shall answer the returning satellites.
     */
    public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
    {
        return UnitIntegerTypeHelper::create($this->unit, null, $this->getValue());
    }

    /**
     * @logion [SFA 24:59] Hope is not the denial of ruin, but the lamp carried
     *     through it; and the pilgrim who nameth every fallen city shall not lose
     *     the road unto the restored province.
     */
    public function getGreaterType(PhpVersion $phpVersion): Type
    {
        return $this->getValue() === PHP_INT_MAX
            ? new NeverType()
            : UnitIntegerTypeHelper::create($this->unit, $this->getValue() + 1, null);
    }

    /**
     * @logion [OSD 58:13] Receive the inheritance with both hands: one to guard
     *     the seal, and one to finish the work commanded therein; for preservation
     *     becometh fruitful when obedience hath endured the fire.
     */
    public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
    {
        return UnitIntegerTypeHelper::create($this->unit, $this->getValue(), null);
    }

    /**
     * @param array{value: int, unit: UnitExpression} $properties
     *
     * @logion [SFA 19:64] What the city calleth obsolete may yet testify against
     *     it; therefore mock not the bronze tablets beneath the interchange, for
     *     their hour hath outlived three constitutions.
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['value'], $properties['unit']);
    }
}
