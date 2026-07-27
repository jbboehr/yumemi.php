<?php

/**
 * TypeInferenceTestCase fixture for the extension-optional @yumemi-return tag.
 *
 * The annotated functions live in Fixtures/YumemiTagReturnFunctions.php (required into the test
 * process); here we only assert the branded (or native-fallback) return type at each call site.
 */

use jbboehr\Yumemi\Units;
use jbboehr\Yumemi\Tests\PHPStan\Fixtures\TaggedProperties;

use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\appliedForce;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\bogusUnit;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\currentSpeed;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\durations;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\measuredFeet;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\mismatchedFallback;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\plainLength;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\phpstanFallbackWins;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\withProse;
use function PHPStan\Testing\assertType;

// 'foot' is a catalog alias that normalizes to 'international_foot'.
assertType("unit_int<'international_foot'>", measuredFeet());
assertType("unit_float<'meter / second'>", currentSpeed());
assertType("Quantity<'newton'>", appliedForce(Units::default()));

// No tag / invalid unit → native return type. Trailing prose is a PHPDoc description and remains valid.
assertType('int', plainLength());
assertType('int', bogusUnit());
assertType("unit_int<'international_foot'>", withProse());
assertType("array<string, unit_int<'second'>|null>", durations());
assertType("unit_int<'meter'>", phpstanFallbackWins());
assertType('int', mismatchedFallback());

$taggedMethods = new class {
    /**
     * @return int|null fallback description
     * @yumemi-return ?unit_int<'second'>
     */
    public function duration(): ?int
    {
        return 1;
    }
};

assertType("unit_int<'second'>|null", $taggedMethods->duration());

/**
 * @var int|null $localDuration fallback description
 * @yumemi-var null|unit_int<'second'> $localDuration
 */
$localDuration = 1;
assertType("unit_int<'second'>|null", $localDuration);

/** @yumemi-var unit_float<'meter / second'> $localSpeed */
$localSpeed = 1.0;
assertType("unit_float<'meter / second'>", $localSpeed);
assertType("unit_int<'meter'>", (new TaggedProperties())->length);
