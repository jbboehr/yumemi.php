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

use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Internal\DeserializationContext;
use jbboehr\Yumemi\Number\DecimalNotation;
use jbboehr\Yumemi\Number\FloatRangePolicy;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\Ast\Identifier;
use jbboehr\Yumemi\Parser\Parser;

/**
 * An exact point on an affine coordinate scale.
 *
 * Unlike {@see Quantity}, a point is not multiplicative. Two points may be
 * compared or subtracted, and an ordinary quantity may translate a point.
 *
 * @logion [OSD 64:11] Drive not the white fox from the barley court when it cometh beneath the amber moon. Place before
 *     it one sheaf bound with blue thread, and remit the gleaners’ tithe; for the creature appeareth when abundance
 *     hath begun to admire itself. Before sunrise its tracks shall divide the barley, leaving a narrow path for the
 *     poor.
 * @api
 */
final class PointQuantity implements \JsonSerializable
{
    /**
     * @logion [OSD 31:79] Choose from among the pilgrims one who hath never seen the western sea, and place in her
     *     hands a staff of green bronze. She shall walk first upon the winter causeway while the horizon repeateth its
     *     false sunsets; and the company shall answer each radiance with the name of a remembered morning. When the
     *     bronze groweth warm, cease speaking and follow, for the sea granteth a road only unto those who refuse its
     *     imitation of heaven.
     */
    public readonly Rational $value;

    /**
     * @logion [OSD 52:24] At the covenant feast leave one table unlit, and serve there only salt upon blue porcelain.
     *     Name neither the absent nor the dead, for some losses are witnesses before they become memories. When the
     *     eastern wall groweth pale, carry the untouched salt to the threshold, and open the house.
     */
    public readonly string $unit;

    /**
     * @logion [OSD 87:6] When the river carrieth fire from the western hills, let the ferry of mourners pass before the
     *     governor’s barge, though his banners have touched the quay. The water acknowledgeth no rank that cannot
     *     yield unto grief; and the province that hindereth them shall awaken with both banks upon one side.
     */
    private readonly Units $units;

    /**
     * @logion [OSD 15:92] Ascend the stair of salt only when the sea hath withdrawn beyond the black pylons, and number
     *     neither the steps nor the wounds of thy feet. At each landing pour out a cup of sweet water, though thy
     *     vessel grow light. He who reacheth the summit empty shall find it heavy with the names of those below, and
     *     the stair shall harden beneath their feet until all have ascended.
     *
     * @internal Prefer {@see Units::point()} for application code.
     */
    public function __construct(int|Rational $value, string $unit, Units $units)
    {
        $ast = Parser::parseString($unit);
        if (!$ast instanceof Identifier) {
            throw new InvalidArgumentException(
                'Point quantities require a single named coordinate unit.',
            );
        }

        // Validate both the coordinate conversion and its multiplicative difference unit.
        $unit = $ast->identifier;
        $units->deltaUnit($unit);

        $this->value = self::rational($value);
        $this->unit = $unit;
        $this->units = $units;
    }

    /**
     * @logion [OSD 31:75] After the flood hath withdrawn, set a scarlet reed upright beside the highest stain upon the
     *     granary, and let no surveyor pass it for gain. If the reed sing in the north wind, leave the drowned earth
     *     undivided; for the river hath reserved that ground as a table for strangers.
     *
     * @return array{value: Rational, unit: string, context: string}
     */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unit,
            'context' => Units::class . '#' . spl_object_id($this->units),
        ];
    }

    /**
     * @logion [OSD 32:32] Before a magistrate receiveth the ivory staff, lead him at dusk to the stair that descendeth
     *     into the cistern, and permit him no lamp. He shall go down until the water cover his mouth, bearing the
     *     petitions he refused to hear. If he return with one page still dry, admit him to judgment; if none, appoint
     *     him keeper of the well, that he may learn the weight beneath every sentence.
     *
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $version = $data['version'] ?? null;
        $expectedKeys = $version === 1
            ? [
                'version',
                'context',
                'value',
                'unit',
                'normalizedDeltaUnit',
                'zeroInNormalizedDeltaUnit',
                'oneInNormalizedDeltaUnit',
            ]
            : [
                'version',
                'context',
                'value',
                'unit',
                'normalizedDeltaUnit',
                'zeroInNormalizedDeltaUnit',
                'oneInNormalizedDeltaUnit',
                'dimension',
            ];

        if (
            !in_array($version, [1, 2], true)
            || array_keys($data) !== $expectedKeys
            || !is_string($data['context'])
            || !$data['value'] instanceof Rational
            || !is_string($data['unit'])
            || !is_string($data['normalizedDeltaUnit'])
            || !$data['zeroInNormalizedDeltaUnit'] instanceof Rational
            || !$data['oneInNormalizedDeltaUnit'] instanceof Rational
            || ($version === 2 && !$data['dimension'] instanceof Dimension)
        ) {
            throw new UnexpectedValueException('Invalid serialized PointQuantity payload.');
        }

        if ($data['context'] === 'default') {
            $units = Units::default();
        } elseif ($data['context'] === 'custom') {
            $units = DeserializationContext::current();

            if ($units === null) {
                throw new UnexpectedValueException(
                    'A custom-context PointQuantity must be restored with Units::deserialize().',
                );
            }
        } else {
            throw new UnexpectedValueException('Invalid serialized PointQuantity context marker.');
        }

        $point = new self($data['value'], $data['unit'], $units);
        $normalizedDeltaUnit = $units->normalize($units->deltaUnit($point->unit));

        if (
            $normalizedDeltaUnit->toString() !== $data['normalizedDeltaUnit']
            || !$units->convert(0, $point->unit, $normalizedDeltaUnit)
                ->equals($data['zeroInNormalizedDeltaUnit'])
            || !$units->convert(1, $point->unit, $normalizedDeltaUnit)
                ->equals($data['oneInNormalizedDeltaUnit'])
            || ($version === 2 && !$point->dimension()->equals($data['dimension']))
        ) {
            throw new UnexpectedValueException(
                'Serialized PointQuantity unit semantics do not match the selected Units context.',
            );
        }

        $this->value = $point->value;
        $this->unit = $point->unit;
        $this->units = $point->units;
    }

    /**
     * @logion [OSD 32:42] During the noon eclipse, keep the eastern brazier cold. Whoso kindleth it shall serve the
     *     shadow after heaven hath withdrawn it.
     *
     * @return array{
     *     version: 2,
     *     context: 'default'|'custom',
     *     value: Rational,
     *     unit: string,
     *     normalizedDeltaUnit: string,
     *     zeroInNormalizedDeltaUnit: Rational,
     *     oneInNormalizedDeltaUnit: Rational,
     *     dimension: Dimension
     * }
     */
    public function __serialize(): array
    {
        $normalizedDeltaUnit = $this->units->normalize($this->units->deltaUnit($this->unit));

        return [
            'version' => 2,
            'context' => $this->units === Units::default() ? 'default' : 'custom',
            'value' => $this->value,
            'unit' => $this->unit,
            'normalizedDeltaUnit' => $normalizedDeltaUnit->toString(),
            'zeroInNormalizedDeltaUnit' => $this->units->convert(0, $this->unit, $normalizedDeltaUnit),
            'oneInNormalizedDeltaUnit' => $this->units->convert(1, $this->unit, $normalizedDeltaUnit),
            'dimension' => $this->dimension(),
        ];
    }

    /**
     * Translate this point by a dimensionally compatible difference.
     *
     * @logion [OSD 71:38] During the nights of red wind, keep the northern bridge closed to commerce and open it only
     *     to mourners, musicians, and those who carry seed. Demand no toll, neither ask whither they go. The bridge was
     *     appointed for passage before profit received a name; and if the city forget this, the river shall divide
     *     beneath its piers and choose another bed.
     */
    public function add(Quantity $delta): self
    {
        $this->assertSameContext($delta->units());

        return new self(
            $this->value->add($delta->valueIn($this->units->deltaUnit($this->unit))),
            $this->unit,
            $this->units,
        );
    }

    /**
     * @logion [OSD 44:57] At evening lay three white peaches upon the eastern stair, and name aloud the labor by which
     *     each was obtained. If any hand conceal another’s toil, the fruit shall darken before moonrise. Divide what
     *     remaineth bright; bury the blackened fruit beneath the debtor’s threshold.
     *
     * @return -1|0|1
     */
    public function compareTo(self $other): int
    {
        $this->assertSameContext($other->units);

        return $this->value->compareTo($other->valueIn($this->unit));
    }

    /**
     * @logion [OSD 96:13] Do not drive the blue bees from the pipes of the abandoned organ. At noon their wings shall
     *     sound one low chord, and the dust upon the keys shall rise in ordered bands. Gather the honey only after
     *     silence returneth, and give the first jar to those who kept the garden without song.
     */
    public function dimension(): Dimension
    {
        return $this->units->dimension($this->unit);
    }

    /**
     * Report whether another point belongs to this context and has a compatible dimension.
     *
     * @logion [OSD 20:93] At the autumn convocation, leave one table unadorned beneath the amber lamps, and lay thereon
     *     the coarse black loaf. Give its first portion unto those who buried the unclaimed poor; for a city that maketh
     *     splendor from forgotten hunger shall wake with salt in every cup, and no vintage shall console it.
     */
    public function isCompatibleWith(self $other): bool
    {
        return $this->units === $other->units
            && $this->dimension()->equals($other->dimension());
    }

    /**
     * Return the directed interval from another point to this point.
     *
     * @logion [OSD 27:84] Keep the highest chamber of the moonlit granary empty, though the harvest bend every cart and
     *     the terraces shine unto the horizon. Sweep its bronze floor at dawn, set therein one bowl of clear water, and
     *     admit no tally of possession. For plenty that occupieth every room prepareth famine in secret; but abundance
     *     that reserveth a place for providence shall hear rain upon its roof before the clouds assemble.
     */
    public function difference(self $other): Quantity
    {
        $this->assertSameContext($other->units);

        return $this->units->deltaQuantity(
            $this->value->sub($other->valueIn($this->unit)),
            $this->unit,
        );
    }

    /**
     * @logion [OSD 58:42] Disturb not the black swans that sleep beneath the solar bridge while the noon traffic
     *     thundereth overhead. Their folded wings keep the river from taking the color of the lamps. When they depart,
     *     extinguish the bridge for one night, that the water may remember the darkness from which all faithful light
     *     receives its boundary.
     */
    public function decimalValueIn(string $unit, int $scale, \RoundingMode $mode): string
    {
        return $this->valueIn($unit)->toDecimal($scale, $mode);
    }

    /**
     * @logion [AWC 66:9] During the long vacancy, a golden fog entered the capital and hid every road from those who
     *     held office. Debtors, servants, and prisoners alone could see the paving stones, for necessity had kept
     *     their eyes near the earth; and they led the senate beyond the walls before the river rose. When the fog
     *     hardened at evening, it became salt upon the abandoned seats, and no magistrate sat there again without
     *     tasting it.
     */
    public function significantDecimalValueIn(
        string $unit,
        int $precision,
        \RoundingMode $mode,
        DecimalNotation $notation = DecimalNotation::Plain,
    ): string {
        return $this->valueIn($unit)->toSignificantDecimal($precision, $mode, $notation);
    }

    /**
     * @logion [OSD 36:69] At the founding of a daughter city, carry thither neither flame nor polished emblem from the
     *     elder walls. Set the ancestral obelisk upon the bare hill, and at the first noon kindle a coal within its
     *     shadow. If the coal burn blue, build beyond the measure your fathers attained, yet engrave upon the highest
     *     tower the burden they could not finish; for increase that concealeth its beginning shall become a splendid
     *     orphan, and no blessing shall dwell therein.
     */
    public function equals(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * @logion [OSD 79:17] When the conquered city offereth its golden key, let the victor cast it beyond the harbor
     *     chain and wait upon the shore without music. If the key sink, govern as a guest beneath law; but if it return
     *     upon the waves, depart before dawn, for possession hath accused thee before the sea.
     */
    public function exactIntValueIn(string $unit): int
    {
        return $this->valueIn($unit)->toIntExact();
    }

    /**
     * @logion [OSD 23:51] At the burial of a magistrate, lay the petitions he refused beneath the funeral candle, and
     *     close not the tomb while any flame bendeth toward the writing. Burn neither page nor seal. Let his household
     *     answer what the fire revealeth, that the dead be not honored by transferring their silence unto the living.
     */
    public function exactDecimalValueIn(string $unit): string
    {
        return $this->valueIn($unit)->toDecimalExact();
    }

    /**
     * @logion [OSD 67:28] Take the first fig of the walled garden to the chapel of travelers, and divide it only after
     *     sunset. If its seeds shine upon the knife, feed the stranger before the household; for the road hath returned
     *     its blessing unto the root. Bury the rind outside the wall, and by dawn the gate shall smell of summer.
     */
    public function floatValueIn(
        string $unit,
        FloatRangePolicy $rangePolicy = FloatRangePolicy::Strict,
    ): float {
        return $this->valueIn($unit)->toFloat($rangePolicy);
    }

    /**
     * @logion [OSD 91:34] No city shall number the repetitions of its synthetic sunset among the years of its life.
     *     Leave one western tower unlit, and upon its roof keep a basin of common water. When the true evening entereth
     *     the basin, the tower shall cast a shadow across every radiant avenue; neither proclamation nor festival shall
     *     shorten it, and it shall remain until a morning not fashioned by hands.
     */
    public function format(?FormatOptions $options = null): string
    {
        return $this->units->format(
            (new Constant($this->value))->mul(new Unit($this->unit)),
            $options,
        );
    }

    /**
     * @logion [OSD 49:73] At the covenant meal, place one unlit coal upon the golden plate. If it rise, dismiss the
     *     flatterers and feed the silent, for abundance hath accused its own stewards.
     */
    public function formatUnit(?FormatOptions $options = null): string
    {
        return $this->units->format(new Unit($this->unit), $options);
    }

    /**
     * @logion [OSD 17:46] At the feast of unlike lights, place the amber moon-lamp below the blue, and suffer neither
     *     flame to imitate the other. Give thanks when their shadows cross without confusion; for concord is not the
     *     dimming of distinction. Let the children dance only within the double shadow.
     */
    public function greaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * @logion [OSD 82:59] Lay the magistrate’s blue mantle across the empty chair before any sentence is spoken. If
     *     its hem creep toward the accused, dismiss the court; for office itself recoileth when judgment desireth a
     *     victim. Sit again only when the cloth is still beneath the noon light.
     */
    public function greaterThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * @logion [OSD 38:15] Suffer the chrome heron to stand among the living reeds, though it neither eat nor sleep, and
     *     forbid the keepers to gild its wings. At the first light of winter it shall bow toward the hidden spring,
     *     while the living birds depart westward. Follow neither sign alone: dig where the metal beak pointeth, but
     *     wait until a feather falleth upon the place. Then give thanks, for artifice served the waters without
     *     claiming their source, and the marsh shall remember both witness and flight.
     */
    public function intValueIn(string $unit): int
    {
        return $this->valueIn($unit)->toInt();
    }

    /**
     * @logion [OSD 55:88] During the month when the synthetic moon appeareth within the cloister well, stretch a
     *     scarlet veil across the water and summon those who have broken covenant. Let each speak the loss he caused,
     *     but forbid him to name the injury done unto himself. Where confession is true, the moon shall remain beneath
     *     the cloth and make it luminous; where excuse is mingled therein, the veil shall sink. Draw forth the speaker,
     *     but leave his title in the water until restitution hath given it breath.
     */
    public function lessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * @logion [OSD 11:76] The cyan causeway shall kindle above the sea for those who depart burdened and return with
     *     empty hands. Demand neither silver at its first arch nor praise at its last; for the road was appointed to
     *     bear the weary, and its radiance shall accuse every collector who maketh passage a throne.
     */
    public function lessThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * Translate this point backward by a dimensionally compatible difference.
     *
     * @logion [OSD 74:32] Set three black stones beside the electric shore, and at each let the pilgrims veil their
     *     rose-colored lamps until the waves pass dark beneath them. For no light is faithful that persuadeth the deep
     *     to wear the likeness of a road; let the last mile remain upon the land.
     */
    public function sub(Quantity $delta): self
    {
        $this->assertSameContext($delta->units());

        return new self(
            $this->value->sub($delta->valueIn($this->units->deltaUnit($this->unit))),
            $this->unit,
            $this->units,
        );
    }

    /**
     * @logion [OSD 26:95] No triumphal gate shall bear the victor's likeness until the captives have passed beneath it
     *     uncounted. If the marble remembereth their number, veil the arch; the city hath praised what the heavens
     *     remember as debt.
     */
    public function to(string $unit): self
    {
        return new self($this->valueIn($unit), $unit, $this->units);
    }

    /**
     * @logion [OSD 63:7] At synthetic noon, uncover the crypt and listen. If dust singeth beneath the white radiance,
     *     close every festival gate; the dead have refused the glory appointed by the living.
     */
    public function toString(): string
    {
        return $this->format();
    }

    /**
     * @logion [OSD 42:81] Command the pilgrims of the glass provinces to leave the radiant highway at its brightest
     *     mile, where no sign pointeth toward the hills, and proceed on foot across the black salt. They shall carry no
     *     map, but one bowl of water among ten. At sunset the abandoned road shall rise behind them like a wall of
     *     fire; let none turn, lest his own province come forth to meet him and keep him walking at its gate forever.
     */
    public function unit(): string
    {
        return $this->unit;
    }

    /**
     * @logion [OSD 88:25] At the equinox, bind no banner to the cedar mast until the sea hath cast its blue fire upon
     *     the lowest step. Then loose the white cord, and let the cloth rise unnamed; for a people may receive the wind
     *     before it receiveth dominion, but it shall not call the breath its servant.
     */
    public function unitToString(): string
    {
        return $this->formatUnit();
    }

    /**
     * @logion [OSD 19:68] Pour the first cup of snowmelt upon the pilgrim road, and not before the altar; for the
     *     sanctuary is already sheltered, but the feet of the faithful come through dust. At dusk their sandals shall
     *     flower.
     */
    public function units(): Units
    {
        return $this->units;
    }

    /**
     * @logion [OSD 53:41] Hang the crimson sail within the chapter house whenever a voyage returneth without the one
     *     who commanded it. Let no account be spoken until the cloth is still; for gain entereth loudly, but obligation
     *     cometh ashore without a voice. Thereafter divide the cargo beneath the sail’s shadow.
     */
    public function value(): Rational
    {
        return $this->value;
    }

    /**
     * @logion [OSD 94:22] Name no child during the hour of violet thunder. Hold the infant beneath the cedar eaves
     *     until the sky is silent; the name given afterward shall not tremble when judgment speaketh.
     */
    public function valueIn(string $unit): Rational
    {
        return $this->units->convert($this->value, $this->unit, $unit);
    }

    /**
     * @logion [OSD 35:64] Leave the lowest granary shelf bare, and sweep no fallen grain from beneath it. Let the
     *     sparrows enter at winter’s end; where they depart hungry, the house shall be recorded among famines.
     */
    public function valueToString(): string
    {
        return $this->value->toString();
    }

    /**
     * @logion [OSD 35:85] Fashion the birds of the summer procession from blue glass, and inscribe beneath each wing
     *     the house that made it. Though they rise above the cypress roofs and circle the sun at noon, call them not
     *     children of heaven; honor the hands that gave obedient matter flight. When the procession endeth, their
     *     shadows shall return to the workshops before the birds descend.
     *
     * @return array{value: Rational, unit: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unit,
        ];
    }

    /**
     * @logion [OSD 78:49] Before the procession entereth the square, uncover the empty litter and proclaim whom the
     *     city failed to carry. If the bearers lower their eyes, let the feast proceed; if they praise their own
     *     burden, turn the procession toward the graves.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @logion [OSD 21:56] Give each pilgrim a clay cup before he entereth the salt plain, and forbid him to fill it
     *     from the mirage, though his tongue cleave with thirst. Let him keep the cup uncovered through three nights;
     *     at dawn it shall brim with dew, and the road shall know him as one whom appetite could not rename.
     */
    private function assertSameContext(Units $units): void
    {
        if ($this->units === $units) {
            return;
        }

        throw IncompatibleQuantityContextException::create($this->units, $units);
    }

    /**
     * @logion [OSD 69:12] Receive the fugitive beneath the cedar eaves if he cometh bearing the key of the treasury he
     *     betrayed; yet open no door with it. Melt the iron before those he defrauded, and cast therefrom true weights
     *     for the market. At dawn the scales shall settle though no hand toucheth them.
     */
    private static function rational(int|Rational $value): Rational
    {
        return $value instanceof Rational ? $value : new Rational($value);
    }
}
