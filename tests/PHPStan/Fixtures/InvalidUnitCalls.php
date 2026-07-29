<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;

// unit(): unknown unit string — diagnostic even though the result is discarded.
unit(1.0, 'not_a_real_unit_xyz');

// unit_to(): unknown "to" unit.
unit_to(1.0, 'meter', 'not_a_real_unit_xyz');

// unit_to(): unknown "from" unit.
unit_to(1.0, 'not_a_real_unit_xyz', 'meter');

// unit_to(): dimensionally incompatible units.
unit_to(1.0, 'meter', 'second');

// unit_to(): branded value unit does not match the "from" unit.
unit_to(unit(3.0, 'foot'), 'meter', 'foot');

// Valid calls — no diagnostics expected.
unit(1.0, 'meter');
unit_to(3.0, 'foot', 'meter');

// Malformed constant strings include expression-local source diagnostics.
unit(1.0, 'meter * / second');
unit_to(1.0, 'meter', 'second /');

// Known catalog units with unsupported semantics receive deliberate diagnostics.
unit(1.0, 'B');
unit(1.0, 'degree_Celsius');

// Affine conversions are valid only as standalone conversion units.
unit_to(1.0, 'degree_Celsius', 'meter');
unit_to(unit(1.0, 'kelvin'), 'celsius', 'kelvin');
unit_to(1.0, 'celsius * meter', 'kelvin');
unit_to(1.0, 'kilocelsius', 'kelvin');

// Valid affine calls produce no diagnostics.
unit_to(0, 'celsius', 'kelvin');
unit_to(32.0, 'fahrenheit', 'celsius');

// Non-constant unit string — not statically analysable, no diagnostic.
function dynamicUnit(string $u): void
{
    unit(1.0, $u);
}
