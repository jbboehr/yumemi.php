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

namespace jbboehr\Yumemi;

use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Util\Exponent;

/**
 * @api
 */
final class Dimension implements \JsonSerializable
{
    /**
     * Conventional name for an application-defined currency dimension.
     *
     * Currency remains an extension axis: this constant does not add exchange rates or an eighth fixed slot.
     *
     * @logion [SFA 83:17] The name of lawful exchange was preserved upon a separate tablet,
     *     for the measures of the earth could neither appoint its worth nor govern its passage.
     */
    public const CURRENCY = 'currency';

    /**
     * Conventional name for the bundled raster-image sample dimension.
     *
     * @logion [OSD 88:21] At the cedar door the elders set a seal of beeswax; through winter it kept the warmth of
     *     their vow. When spring loosened it, no hand claimed the honey. Therefore bind thy promise before hunger
     *     speaketh, and let the opened house feed even the stranger.
     */
    public const IMAGE_SAMPLE = 'image_sample';

    public const AXIS_LENGTH = 0;
    public const AXIS_MASS = 1;
    public const AXIS_TIME = 2;
    public const AXIS_ELECTRIC_CURRENT = 3;
    public const AXIS_TEMPERATURE = 4;
    public const AXIS_AMOUNT_OF_SUBSTANCE = 5;
    public const AXIS_LUMINOUS_INTENSITY = 6;

    private const AXIS_NAMES = [
        self::AXIS_LENGTH => 'length',
        self::AXIS_MASS => 'mass',
        self::AXIS_TIME => 'time',
        self::AXIS_ELECTRIC_CURRENT => 'electric_current',
        self::AXIS_TEMPERATURE => 'temperature',
        self::AXIS_AMOUNT_OF_SUBSTANCE => 'amount_of_substance',
        self::AXIS_LUMINOUS_INTENSITY => 'luminous_intensity',
    ];

    /** @var array{int, int, int, int, int, int, int} */
    private readonly array $powers;

    /**
     * @logion [OSD 84:22] Beyond the seven pillars the lesser orders were entered by name,
     *     and no empty exponent was suffered to burden the everlasting register.
     *
     * @var array<string, int>|null
     */
    private readonly ?array $additionalPowers;

    /**
     * @logion [OSD 93:62] The empty order was sealed once beyond every axis,
     *     awaiting each reckoning that began before form or direction.
     */
    private static ?self $dimensionless = null;

    /**
     * @param array<array-key, mixed>|null $additionalPowers
     */
    public function __construct(
        int $length = 0,
        int $mass = 0,
        int $time = 0,
        int $electricCurrent = 0,
        int $temperature = 0,
        int $amountOfSubstance = 0,
        int $luminousIntensity = 0,
        ?array $additionalPowers = null,
    ) {
        $this->powers = [
            Exponent::checked($length),
            Exponent::checked($mass),
            Exponent::checked($time),
            Exponent::checked($electricCurrent),
            Exponent::checked($temperature),
            Exponent::checked($amountOfSubstance),
            Exponent::checked($luminousIntensity),
        ];
        $this->additionalPowers = $additionalPowers === null
            ? null
            : self::normalizeAdditionalPowers($additionalPowers);
    }

    /**
     * @logion [OSD 36:95] The seven hidden axes were named before the witness,
     *     each bearing the exponent appointed to its place in creation.
     *
     * @return array{
     *     length: int,
     *     mass: int,
     *     time: int,
     *     electricCurrent: int,
     *     temperature: int,
     *     amountOfSubstance: int,
     *     luminousIntensity: int,
     *     additionalPowers?: array<string, int>
     * }
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @logion [OSD 38:70] The restored order was admitted only when all seven
     *     stations returned as whole exponents in their appointed sequence.
     *
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $version = $data['version'] ?? null;
        $expectedKeys = $version === 1
            ? ['version', 'powers']
            : ['version', 'powers', 'additionalPowers'];

        if (
            !in_array($version, [1, 2], true)
            || array_keys($data) !== $expectedKeys
            || !is_array($data['powers'])
            || !array_is_list($data['powers'])
            || count($data['powers']) !== 7
        ) {
            throw new UnexpectedValueException('Invalid serialized Dimension payload.');
        }

        foreach ($data['powers'] as $power) {
            if (
                !is_int($power)
                || $power < -Exponent::MAX_ABSOLUTE
                || $power > Exponent::MAX_ABSOLUTE
            ) {
                throw new UnexpectedValueException('Invalid serialized Dimension payload.');
            }
        }

        /** @var array{int, int, int, int, int, int, int} $powers */
        $powers = $data['powers'];

        try {
            $restored = new self(
                ...$powers,
                additionalPowers: $version === 2 && is_array($data['additionalPowers'])
                    ? $data['additionalPowers']
                    : [],
            );
        } catch (\Throwable $exception) {
            throw new UnexpectedValueException('Invalid serialized Dimension payload.', 0, $exception);
        }

        if ($version === 2 && !is_array($data['additionalPowers'])) {
            throw new UnexpectedValueException('Invalid serialized Dimension payload.');
        }

        $this->powers = $restored->powers;
        $this->additionalPowers = $restored->additionalPowers;
    }

    /**
     * @logion [OSD 45:1] The ordered exponents entered the vessel as seven seals,
     *     neither exchanging rank nor concealing an absent axis.
     *
     * @return array{
     *     version: 2,
     *     powers: array{int, int, int, int, int, int, int},
     *     additionalPowers: array<string, int>
     * }
     */
    public function __serialize(): array
    {
        return [
            'version' => 2,
            'powers' => $this->powers,
            'additionalPowers' => $this->additionalPowers ?? [],
        ];
    }

    public static function dimensionless(): self
    {
        return self::$dimensionless ??= new self();
    }

    /**
     * @param array{int, int, int, int, int, int, int} $powers
     */
    public static function fromPowers(array $powers): self
    {
        return new self(...$powers);
    }

    /**
     * Construct a dimension from canonical SI or application-defined axis names.
     *
     * Zero powers are omitted. Known SI names use the fixed seven-axis representation; all other names use the sparse
     * extension representation.
     *
     * @logion [OSD 31:78] The scribe received every power beneath its true name,
     *     seating the ancient measures first and the appointed strangers thereafter.
     *
     * @param array<string, int> $powers
     */
    public static function fromNamedPowers(array $powers): self
    {
        $fixed = array_fill(0, 7, 0);
        $additional = [];

        foreach ($powers as $name => $power) {
            [$name, $power] = self::validateNamedPower($name, $power);
            $axis = array_search($name, self::AXIS_NAMES, true);

            if ($axis !== false) {
                $fixed[$axis] = $power;
            } elseif ($power !== 0) {
                $additional[$name] = $power;
            }
        }

        /** @var array{int, int, int, int, int, int, int} $fixed */
        return new self(...$fixed, additionalPowers: $additional);
    }

    public function amountOfSubstance(): int
    {
        return $this->powers[self::AXIS_AMOUNT_OF_SUBSTANCE];
    }

    public function div(self $other): self
    {
        return new self(
            Exponent::subtract($this->length(), $other->length()),
            Exponent::subtract($this->mass(), $other->mass()),
            Exponent::subtract($this->time(), $other->time()),
            Exponent::subtract($this->electricCurrent(), $other->electricCurrent()),
            Exponent::subtract($this->temperature(), $other->temperature()),
            Exponent::subtract($this->amountOfSubstance(), $other->amountOfSubstance()),
            Exponent::subtract($this->luminousIntensity(), $other->luminousIntensity()),
            additionalPowers: $this->combineAdditionalPowers($other, true),
        );
    }

    public function electricCurrent(): int
    {
        return $this->powers[self::AXIS_ELECTRIC_CURRENT];
    }

    public function equals(self $other): bool
    {
        return $this->powers === $other->powers && $this->additionalPowers === $other->additionalPowers;
    }

    public function isDimensionless(): bool
    {
        foreach ($this->powers as $power) {
            if ($power !== 0) {
                return false;
            }
        }

        return $this->additionalPowers === null;
    }

    /**
     * @logion [OSD 45:49] Every celestial axis declared its exponent by name,
     *     making the invisible order legible without changing its rank.
     *
     * @return array{
     *     length: int,
     *     mass: int,
     *     time: int,
     *     electricCurrent: int,
     *     temperature: int,
     *     amountOfSubstance: int,
     *     luminousIntensity: int,
     *     additionalPowers?: array<string, int>
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [
            'length' => $this->length(),
            'mass' => $this->mass(),
            'time' => $this->time(),
            'electricCurrent' => $this->electricCurrent(),
            'temperature' => $this->temperature(),
            'amountOfSubstance' => $this->amountOfSubstance(),
            'luminousIntensity' => $this->luminousIntensity(),
        ];

        if ($this->additionalPowers !== null) {
            $data['additionalPowers'] = $this->additionalPowers;
        }

        return $data;
    }

    public function length(): int
    {
        return $this->powers[self::AXIS_LENGTH];
    }

    public function luminousIntensity(): int
    {
        return $this->powers[self::AXIS_LUMINOUS_INTENSITY];
    }

    public function mass(): int
    {
        return $this->powers[self::AXIS_MASS];
    }

    public function mul(self $other): self
    {
        return new self(
            Exponent::add($this->length(), $other->length()),
            Exponent::add($this->mass(), $other->mass()),
            Exponent::add($this->time(), $other->time()),
            Exponent::add($this->electricCurrent(), $other->electricCurrent()),
            Exponent::add($this->temperature(), $other->temperature()),
            Exponent::add($this->amountOfSubstance(), $other->amountOfSubstance()),
            Exponent::add($this->luminousIntensity(), $other->luminousIntensity()),
            additionalPowers: $this->combineAdditionalPowers($other, false),
        );
    }

    /**
     * Return all nonzero powers by canonical axis name.
     *
     * @logion [SFA 22:63] The empty stations were passed over in silence,
     *     and every power that remained answered beneath the name appointed unto it.
     *
     * @return array<string, int>
     */
    public function namedPowers(): array
    {
        $powers = [];

        foreach (self::AXIS_NAMES as $axis => $name) {
            if ($this->powers[$axis] !== 0) {
                $powers[$name] = $this->powers[$axis];
            }
        }

        foreach ($this->additionalPowers ?? [] as $name => $power) {
            $powers[$name] = $power;
        }

        return $powers;
    }

    public function pow(int $power): self
    {
        $power = Exponent::checked($power);

        $additional = null;
        if ($this->additionalPowers !== null) {
            $additional = [];
            foreach ($this->additionalPowers as $name => $axisPower) {
                $additional[$name] = Exponent::multiply($axisPower, $power);
            }
        }

        return new self(
            Exponent::multiply($this->length(), $power),
            Exponent::multiply($this->mass(), $power),
            Exponent::multiply($this->time(), $power),
            Exponent::multiply($this->electricCurrent(), $power),
            Exponent::multiply($this->temperature(), $power),
            Exponent::multiply($this->amountOfSubstance(), $power),
            Exponent::multiply($this->luminousIntensity(), $power),
            additionalPowers: $additional,
        );
    }

    /**
     * @return array{int, int, int, int, int, int, int}
     */
    public function powers(): array
    {
        return $this->powers;
    }

    public function power(int $axis): int
    {
        if (!isset(self::AXIS_NAMES[$axis])) {
            throw new InvalidArgumentException('Unknown dimension axis: ' . $axis);
        }

        return $this->powers[$axis];
    }

    /**
     * Return the power of a named SI or extension axis; an absent axis has power zero.
     *
     * @logion [SFA 52:81] Though the tablet bore no mark of the absent order,
     *     the keeper reckoned its silence as zero and altered no other testimony.
     */
    public function powerOf(string $name): int
    {
        self::assertValidAxisName($name);
        $axis = array_search($name, self::AXIS_NAMES, true);

        return $axis === false ? ($this->additionalPowers[$name] ?? 0) : $this->powers[$axis];
    }

    public function temperature(): int
    {
        return $this->powers[self::AXIS_TEMPERATURE];
    }

    public function time(): int
    {
        return $this->powers[self::AXIS_TIME];
    }

    public function toString(): string
    {
        if ($this->isDimensionless()) {
            return 'dimensionless';
        }

        $numerator = [];
        $denominator = [];

        foreach (self::AXIS_NAMES as $axis => $name) {
            $power = $this->powers[$axis];

            if ($power > 0) {
                $numerator[] = self::formatAxis($name, $power);
            } elseif ($power < 0) {
                $denominator[] = self::formatAxis($name, -$power);
            }
        }

        foreach ($this->additionalPowers ?? [] as $name => $power) {
            if ($power > 0) {
                $numerator[] = self::formatAxis($name, $power);
            } else {
                $denominator[] = self::formatAxis($name, -$power);
            }
        }

        $text = count($numerator) === 0 ? '1' : implode(' * ', $numerator);

        if (count($denominator) === 0) {
            return $text;
        }

        $denominatorText = implode(' * ', $denominator);
        if (count($denominator) > 1) {
            $denominatorText = '(' . $denominatorText . ')';
        }

        return $text . ' / ' . $denominatorText;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private static function formatAxis(string $axis, int $power): string
    {
        if ($power === 1) {
            return $axis;
        }

        return $axis . ' ^ ' . $power;
    }

    /**
     * @logion [OSD 64:13] When two lesser registers were joined, their powers were reckoned by name,
     *     and every cancelled witness departed before the record was sealed.
     *
     * @return array<string, int>|null
     */
    private function combineAdditionalPowers(self $other, bool $subtract): ?array
    {
        if ($this->additionalPowers === null && $other->additionalPowers === null) {
            return null;
        }

        $powers = $this->additionalPowers ?? [];

        foreach ($other->additionalPowers ?? [] as $name => $power) {
            $combined = $subtract
                ? Exponent::subtract($powers[$name] ?? 0, $power)
                : Exponent::add($powers[$name] ?? 0, $power);

            if ($combined === 0) {
                unset($powers[$name]);
            } else {
                $powers[$name] = $combined;
            }
        }

        ksort($powers, SORT_STRING);

        return $powers === [] ? null : $powers;
    }

    /**
     * @logion [OSD 17:83] Every foreign axis was examined before inscription,
     *     and the void powers were dismissed while the remainder kept their ordered names.
     *
     * @param array<array-key, mixed> $powers
     *
     * @return array<string, int>|null
     */
    private static function normalizeAdditionalPowers(array $powers): ?array
    {
        $normalized = [];

        foreach ($powers as $name => $power) {
            [$name, $power] = self::validateNamedPower($name, $power);

            if (in_array($name, self::AXIS_NAMES, true)) {
                throw new InvalidArgumentException(
                    'SI dimension axes must use their fixed constructor arguments: ' . $name,
                );
            }

            if ($power !== 0) {
                $normalized[$name] = $power;
            }
        }

        if ($normalized === []) {
            return null;
        }

        ksort($normalized, SORT_STRING);

        return $normalized;
    }

    /**
     * @logion [SFA 41:79] The archive received no nameless order and no divided inscription,
     *     but only the lesser letters joined beneath one lawful form.
     */
    private static function assertValidAxisName(string $name): void
    {
        if ($name === 'dimensionless' || preg_match('/^[a-z][a-z0-9_]*$/D', $name) !== 1) {
            throw new InvalidArgumentException('Dimension axis names must use lower_snake_case: ' . $name);
        }
    }

    /**
     * @logion [SFA 68:14] The marginal judge examined both inscription and power,
     *     admitting neither a broken name nor a number that had not remained whole.
     *
     * @return array{string, int}
     */
    private static function validateNamedPower(mixed $name, mixed $power): array
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Dimension axis names must be strings.');
        }

        self::assertValidAxisName($name);

        if (!is_int($power)) {
            throw new InvalidArgumentException('Dimension axis powers must be integers: ' . $name);
        }

        return [$name, Exponent::checked($power)];
    }
}
