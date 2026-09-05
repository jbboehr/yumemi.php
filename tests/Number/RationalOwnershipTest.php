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

namespace jbboehr\Yumemi\Tests\Number;

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RationalOwnershipTest extends TestCase
{
    /** @param 'numerator'|'denominator' $component */
    #[DataProvider('componentProvider')]
    public function testConstructionSnapshotsGmpInputs(int $n, int $d, string $component, string $expected): void
    {
        $inputs = ['numerator' => gmp_init($n), 'denominator' => gmp_init($d)];
        $value = new Rational($inputs['numerator'], $inputs['denominator']);

        gmp_setbit($inputs[$component], 8);

        $this->assertSame($expected, $value->toString());
    }

    /** @param 'numerator'|'denominator' $component */
    #[DataProvider('componentProvider')]
    public function testComponentReadsReturnDetachedGmpValues(int $n, int $d, string $component, string $expected): void
    {
        $value = new Rational($n, $d);

        $handle = $component === 'numerator' ? $value->numerator() : $value->denominator();
        if (gmp_sign($handle) < 0) {
            gmp_clrbit($handle, 2);
        } else {
            gmp_setbit($handle, 8);
        }

        $this->assertSame($expected, $value->toString());
    }

    /** @param 'numerator'|'denominator' $component */
    #[DataProvider('componentProvider')]
    public function testSerializationDoesNotExposeStoredGmpValues(int $n, int $d, string $component, string $expected): void
    {
        $value = new Rational($n, $d);
        $payload = $value->__serialize();

        if (gmp_sign($payload[$component]) < 0) {
            gmp_clrbit($payload[$component], 2);
        } else {
            gmp_setbit($payload[$component], 8);
        }

        $this->assertSame($expected, $value->toString());
    }

    /** @return iterable<string, array{int, int, 'numerator'|'denominator', string}> */
    public static function componentProvider(): iterable
    {
        foreach (['numerator', 'denominator'] as $component) {
            yield 'integer ' . $component => [3, 1, $component, '3'];
            yield 'reduced fraction ' . $component => [6, 4, $component, '3/2'];
            yield 'negative denominator ' . $component => [3, -1, $component, '-3'];
            yield 'zero ' . $component => [0, 7, $component, '0'];
        }
    }

    public function testIntegerFactorySnapshotsItsGmpInput(): void
    {
        $input = gmp_init('123456789012345678901234567890');
        $value = Rational::fromInteger($input);

        gmp_setbit($input, 256);

        $this->assertSame('123456789012345678901234567890', $value->toString());
    }

    public function testSharedMagnitudeRemainsStableAcrossQuantitiesAndPoints(): void
    {
        $input = gmp_init(1);
        $value = new Rational($input);
        $units = Units::default();
        $distance = $units->quantity($value, 'meter');
        $duration = $units->quantity($value, 'second');
        $temperature = $units->point($value, 'celsius');

        gmp_setbit($input, 2);
        gmp_setbit($distance->value()->numerator(), 3);
        gmp_clrbit($temperature->value()->denominator(), 0);

        $this->assertSame('1', $distance->valueToString());
        $this->assertSame('100', $distance->valueIn('centimeter')->toString());
        $this->assertSame('1', $duration->valueToString());
        $this->assertSame('5483/20', $temperature->valueIn('kelvin')->toString());
    }

    public function testRestoringANativeGraphDoesNotRetainSiblingGmpAliases(): void
    {
        $numerator = gmp_init(3);
        $denominator = gmp_init(1);
        $serialized = serialize([
            $numerator,
            $denominator,
            (object) ['version' => 1, 'numerator' => $numerator, 'denominator' => $denominator],
        ]);
        $aliasedGraph = unserialize($serialized);
        $this->assertIsArray($aliasedGraph);
        $this->assertInstanceOf(\stdClass::class, $aliasedGraph[2]);
        $this->assertSame($aliasedGraph[0], $aliasedGraph[2]->numerator);
        $this->assertSame($aliasedGraph[1], $aliasedGraph[2]->denominator);

        $rationalHeader = sprintf('O:%d:"%s":3', strlen(Rational::class), Rational::class);
        $serialized = str_replace('O:8:"stdClass":3', $rationalHeader, $serialized, $replacements);
        $this->assertSame(1, $replacements);

        $graph = unserialize($serialized);
        $this->assertIsArray($graph);
        $this->assertInstanceOf(\GMP::class, $graph[0]);
        $this->assertInstanceOf(\GMP::class, $graph[1]);
        $this->assertInstanceOf(Rational::class, $graph[2]);

        gmp_setbit($graph[0], 2);
        gmp_clrbit($graph[1], 0);

        $this->assertSame('3', $graph[2]->toString());
    }

    public function testRestorationSnapshotsPayloadComponents(): void
    {
        $payload = ['version' => 1, 'numerator' => gmp_init(3), 'denominator' => gmp_init(1)];
        $value = (new \ReflectionClass(Rational::class))->newInstanceWithoutConstructor();
        $value->__unserialize($payload);

        gmp_setbit($payload['numerator'], 2);
        gmp_clrbit($payload['denominator'], 0);

        $this->assertSame('3', $value->toString());
    }

    public function testClonedValuesRetainTheirOwnMagnitude(): void
    {
        $original = new Rational(1, 2);
        $copy = clone $original;

        gmp_setbit($copy->numerator(), 2);
        gmp_clrbit($copy->denominator(), 1);

        $this->assertSame('1/2', $original->toString());
        $this->assertSame('1/2', $copy->toString());
        $this->assertSame('1', $copy->add($original)->toString());
    }

    public function testComponentAccessorsReturnNormalizedGmpValues(): void
    {
        $value = new Rational(6, -4);

        $this->assertSame('-3', gmp_strval($value->numerator()));
        $this->assertSame('2', gmp_strval($value->denominator()));
    }

    public function testEachComponentAccessorReturnsAFreshCopy(): void
    {
        $value = new Rational(6, -4);
        $firstNumerator = $value->numerator();
        $firstDenominator = $value->denominator();

        gmp_clrbit($firstNumerator, 2);
        gmp_setbit($firstDenominator, 8);

        $secondNumerator = $value->numerator();
        $secondDenominator = $value->denominator();

        $this->assertNotSame($firstNumerator, $secondNumerator);
        $this->assertSame('-3', gmp_strval($secondNumerator));
        $this->assertNotSame($firstDenominator, $secondDenominator);
        $this->assertSame('2', gmp_strval($secondDenominator));
    }

    public function testMagicPropertyHooksAreRemoved(): void
    {
        $class = new \ReflectionClass(Rational::class);

        $this->assertFalse($class->hasMethod('__get'));
        $this->assertFalse($class->hasMethod('__isset'));
    }

    public function testDirectNumeratorReadIsForbidden(): void
    {
        $value = new Rational(1, 2);

        $this->expectException(\Error::class);

        // @phpstan-ignore property.private (Exercise forbidden direct access.)
        $component = $value->numerator;
        self::fail('Direct numerator read unexpectedly returned ' . gmp_strval($component) . '.');
    }

    public function testDirectDenominatorReadIsForbidden(): void
    {
        $value = new Rational(1, 2);

        $this->expectException(\Error::class);

        // @phpstan-ignore property.private (Exercise forbidden direct access.)
        $component = $value->denominator;
        self::fail('Direct denominator read unexpectedly returned ' . gmp_strval($component) . '.');
    }

    public function testComponentReplacementRemainsForbidden(): void
    {
        $value = new Rational(1, 2);
        $this->expectException(\Error::class);

        // @phpstan-ignore property.private (Exercise forbidden assignment.)
        $value->numerator = gmp_init(9);
    }
}
