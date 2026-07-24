<?php

/**
 * TypeInferenceTestCase fixture for unit() return types.
 */

use function jbboehr\IudexMensurarumMysteriorum\unit;
use function PHPStan\Testing\assertType;

assertType("unit_float<'meter'>", unit(1.5, 'meter'));
assertType("unit_int<'second'>", unit(3, 'second'));
assertType("unit_float<'kilogram * meter / second ^ 2'>", unit(1500.0, 'kilogram') * unit(3.0, 'meter / second^2'));
assertType('*ERROR*', unit(1.0, 'not_a_real_unit_xyz'));
