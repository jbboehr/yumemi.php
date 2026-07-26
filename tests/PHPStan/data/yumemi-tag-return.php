<?php

/**
 * TypeInferenceTestCase fixture for the extension-optional @yumemi-return tag.
 *
 * The annotated functions live in Fixtures/YumemiTagReturnFunctions.php (required into the test
 * process); here we only assert the branded (or native-fallback) return type at each call site.
 */

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\appliedForce;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\bogusUnit;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\currentSpeed;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\measuredFeet;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\plainLength;
use function jbboehr\Yumemi\Tests\PHPStan\Fixtures\withProse;
use function PHPStan\Testing\assertType;

// 'foot' is a catalog alias that normalizes to 'international_foot'.
assertType("unit_int<'international_foot'>", measuredFeet());
assertType("unit_float<'meter / second'>", currentSpeed());
assertType("Quantity<'newton'>", appliedForce(Units::default()));

// No tag / invalid unit / trailing prose → native return type, never poisoned.
assertType('int', plainLength());
assertType('int', bogusUnit());
assertType('int', withProse());
