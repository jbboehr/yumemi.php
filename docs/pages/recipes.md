# Recipes

<figure class="logion" data-logion="OSD 12:44">
<div class="logion-text">
<blockquote>
<p>Write the covenant upon fresh clay and place it in the public kiln beside the vessels of common use. If the words blister while the cups endure, summon the oath-givers again; for no promise is strengthened by the fire it was fashioned to escape. But if the tablet darken without division, carry it warm between the households, and let neither claim the colder edge.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 12:44</cite></p>
</div>
<img src="images/logia/OSD-12_44.webp" alt="A clay covenant tablet enduring a public kiln among household vessels beneath cyan stormlight" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

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

// @akashi-phpstan-error argument.type: unit_float<'meter'>, 6.0&unit_float<'international_foot'> given
setRideHeight($measuredHeight);

setRideHeight(unit_to($measuredHeight, 'foot', 'meter'));
```

See [Branded Native Types](reference/phpstan.md#branded-native-types) and
[Boundary Helpers](reference/phpstan.md#boundary-helpers).

## Keep Unit Setup Outside Hot Loops

When an external invariant already guarantees the input unit, declare that contract once and let repeated work remain
ordinary native arithmetic. Compute a conversion factor before the loop so the loop itself performs only float
multiplication:

```php
<?php

use function jbboehr\Yumemi\unit_factor;

/** @var list<unit_float<'international_foot'>> $surveyLengths */
$surveyLengths = [1.0, 5.0, 10.0];

$footToMeter = unit_factor('international_foot', 'meter');
$metricLengths = [];

foreach ($surveyLengths as $surveyLength) {
    $metricLengths[] = $surveyLength * $footToMeter;
}

/** @param list<unit_float<'meter'>> $lengths */
function saveMetricSurveyLengths(array $lengths): void {}

saveMetricSurveyLengths($metricLengths);
assert(abs($metricLengths[2] - 3.048) < 1e-12);
```

The `@var` declaration asserts that the source data is measured in feet; it does not validate its provenance. Use
`unit()` instead when parsing the unit expression against the runtime catalog is valuable. See
[Native Values At Trusted Boundaries](core-concepts.md#native-values-at-trusted-boundaries) and
[Constant Unit Expressions](reference/phpstan.md#constant-unit-expressions).

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

Temperature scales with different zero points require a full value conversion. Use `PointQuantity` when the coordinate
must remain attached to the value, and use a generated delta unit for temperature differences:

```php
<?php

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit_to;

$units = Units::default();
$freezing = $units->point(0, 'celsius');
$rise = $units->deltaQuantity(18, 'fahrenheit');

assert(abs(unit_to(98.6, 'fahrenheit', 'celsius') - 37.0) < 1e-12);
assert($freezing->valueIn('kelvin')->toString() === '5463/20');
assert($freezing->add($rise)->valueToString() === '10');
assert($units->point(100, 'celsius')->difference($freezing)->toString() === '100 * delta_celsius');
```

Do not use `celsius` itself in products or quotients. `delta_celsius` is multiplicative, and symbol formatting renders
it as `Δ°C`. See [Affine Conversion](reference/runtime.md#affine-conversion).

## Define Application Units

Put project-specific definitions in one factory, then use that factory for both PHPStan and the runtime context. This
prevents one layer from accepting a unit that the other cannot resolve:

<!-- akashi: separate-process -->

```php
<?php

namespace App\Units;

use jbboehr\Yumemi\PHPStan\UnitRegistryFactory;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;

final class ApplicationUnitRegistryFactory implements UnitRegistryFactory
{
    public static function create(): UnitRegistry
    {
        return UnitRegistryBuilder::default()
            ->define('shipping_pallet = 48 * inch')
            ->alias('shipping_pallets', 'shipping_pallet')
            ->build();
    }
}

$units = new Units(ApplicationUnitRegistryFactory::create());
$width = $units->quantity(2, 'shipping_pallets');

assert($width->exactDecimalValueIn('meter') === '2.4384');

Units::setDefault($units);

$nativeWidth = unit(2, 'shipping_pallets');

assert(abs(unit_to($nativeWidth, 'shipping_pallets', 'meter') - 2.4384) < 1e-12);
```

Select the same factory for PHPStan:

```neon
parameters:
    yumemi:
        registryFactory: App\Units\ApplicationUnitRegistryFactory
```

When an application unit is not derived from the seven SI axes, declare one canonical base with `baseUnit()` and derive
the remaining units through exact definitions. The [custom-registry reference](reference/catalog.md#custom-registries)
shows this pattern for an application-owned currency-rate snapshot.

Instance methods use the registry attached to their `Units` context. Native helpers use the process-wide default
instead; install that context once during synchronous process bootstrap, before starting Fibers or other request
scheduling. Concurrent work that needs an isolated registry should retain a `Units` instance and use its methods. See
[Registry Configuration](reference/phpstan.md#registry-configuration),
[Custom Registries](reference/catalog.md#custom-registries), and
[Contexts And Construction](reference/runtime.md#contexts-and-construction) for the complete lifecycle and overlay
rules.

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
