# Recipes

These short examples show common application tasks. They assume the Composer autoloader and PHPStan extension are
already configured as described in [Getting Started](getting-started.md). Follow the links after each recipe for the
complete semantics and limitations.

## Protect An Existing API

Brand incoming data where its unit becomes known, then convert explicitly before calling an API that expects another
unit:

```php
<?php

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;

/** @param unit_float<'meter'> $height */
function setRideHeight(float $height): void {}

$measuredHeight = unit(6.0, 'foot');

//! expects unit_float<'meter'>, unit_float<'international_foot'> given
setRideHeight($measuredHeight);

setRideHeight(unit_to($measuredHeight, 'foot', 'meter'));
```

See [Branded Native Types](reference/phpstan.md#branded-native-types) and
[Boundary Helpers](reference/phpstan.md#boundary-helpers).

## Preserve Exact Conversion

Use `Quantity` when conversion must retain an exact decimal or fraction rather than immediately becoming a float:

```php
<?php

use jbboehr\Yumemi\Units;

$length = Units::default()->quantity(1, 'foot')->to('meter');

assert($length->valueToString() === '381/1250');
assert($length->exactDecimalValueIn('meter') === '0.3048');
assert($length->unitToString() === 'meter');
```

See [Conversion and Comparison](reference/runtime.md#conversion-and-comparison) and
[Native Numeric Output](reference/runtime.md#native-numeric-output).

## Convert Temperatures

Temperature scales with different zero points require a full value conversion. A multiplicative factor is not enough:

```php
<?php

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit_to;

assert(abs(unit_to(98.6, 'fahrenheit', 'celsius') - 37.0) < 1e-12);
assert(Units::default()->convert(0, 'celsius', 'kelvin')->toString() === '5463/20');
```

Affine units are supported only at explicit conversion boundaries. See
[Affine Conversion](reference/runtime.md#affine-conversion).

## Define Application Units

Build an immutable registry snapshot containing project-specific definitions and use it to create a runtime context:

```php
<?php

use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;

$registry = UnitRegistryBuilder::default()
    ->define('shipping_pallet = 48 * inch')
    ->alias('shipping_pallets', 'shipping_pallet')
    ->build();

$units = new Units($registry);
$width = $units->quantity(2, 'shipping_pallets');

assert($width->exactDecimalValueIn('meter') === '2.4384');
```

See [Custom Registries](reference/catalog.md#custom-registries) for runtime overlays and matching PHPStan configuration.

## Format Units For Display

Formatting changes spelling and typography without converting or normalizing the underlying unit:

```php
<?php

use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\Typography;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Units;

$options = FormatOptions::create()
    ->withUnitNameStyle(UnitNameStyle::Symbol)
    ->withTypography(Typography::Unicode);

assert(Units::default()->format('kilogram * meter / second^2', $options) === 'kg · m / s²');
```

See [Formatting](reference/runtime.md#formatting) for division styles, dimensionless output, and reusable formatters.
