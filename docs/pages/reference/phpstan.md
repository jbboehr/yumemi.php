# PHPStan Reference

Yumemi's PHPStan extension attaches units to ordinary PHP `int` and `float` values and propagates them through supported
operations. The runtime values remain native numbers; the additional unit identity exists only during static analysis.

The extension uses the same parser, catalog, reduction, normalization, and conversion semantics as the
[runtime API](runtime.md). See the [unit syntax reference](unit-syntax.md) for accepted expressions and name resolution.

Most applications primarily need branded native types, operator inference, and boundary helpers. `Quantity` and
`PointQuantity` type inference becomes relevant when exact runtime objects cross analyzed code; registry configuration
and optional annotation integration are advanced topics for projects extending the catalog or integrating third-party
libraries.

| I need to...                            | Start with                                          |
| --------------------------------------- | --------------------------------------------------- |
| Add a unit to an existing native number | [`unit()` and branded types](#branded-native-types) |
| Infer units through PHP operators       | [Native Operators](#native-operators)               |
| Convert a native magnitude              | [Boundary Helpers](#boundary-helpers)               |
| Track an exact runtime quantity         | [Quantity Types](#quantity-types)                   |
| Track an exact coordinate point         | [Quantity Types](#quantity-types)                   |
| Add project-specific units              | [Registry Configuration](#registry-configuration)   |
| Suppress or baseline an error           | [Diagnostics](#diagnostics)                         |

> **Current boundaries:** Genuinely dynamic unit strings cannot be validated, casts and unsupported built-ins may erase
> a brand, and dimensional analysis cannot distinguish concepts with identical physical dimensions. See
> [Limitations](#limitations) for the complete list.

## Branded Native Types

`unit_int<'unit'>` and `unit_float<'unit'>` are PHPDoc types for native integers and floats with a statically known
unit. A branded value is not a wrapper or subclass: it remains an ordinary PHP number at runtime. The types work in
ordinary `@param`, `@return`, `@var`, generic, union, intersection, and nullable positions.

Feet are therefore distinct from meters even though both values are native floats:

```php
<?php

/** @param unit_float<'meter'> $length */
function setPlatformHeight(float $length): void {}

/** @var unit_float<'foot'> $height */
$height = 6.0;

//! expects unit_float<'meter'>, unit_float<'international_foot'> given
setPlatformHeight($height);
```

In tested examples, a `//!` comment records part of the PHPStan diagnostic expected on the following line. It is an
ordinary comment used by the documentation tests, not a Yumemi annotation.

The catalog canonicalizes aliases when it constructs a brand, which is why the diagnostic names `international_foot`.
The [catalog reference](catalog.md) describes canonical names, aliases, symbols, plurals, and prefixes.

### Definitional Equivalence And Compatibility

Native arithmetic cannot change either operand's magnitude to a different scale. Addition, subtraction, and assignment
therefore require **definitionally equivalent** units: their normalized expressions, including scale, must match.

Units may instead be merely **dimensionally compatible**. `meter` and `foot` both describe length, but assigning or
adding their native magnitudes would be incorrect without conversion. Use `unit_factor()` or `unit_to()` at that
boundary. Runtime `Quantity` objects can perform the conversion themselves and consequently use dimensional
compatibility for `add()`, `sub()`, and comparisons.

## Native Operators

Yumemi infers native unit types for unary `+` and `-` and for these binary operators:

| Operator    | Static behavior                                                           |
| ----------- | ------------------------------------------------------------------------- |
| `+`, `-`    | Require two definitionally equivalent unit values and preserve their unit |
| `*`, `/`    | Multiply or divide unit expressions and reduce the result                 |
| `**`        | Raise the unit to a constant integer power                                |
| `%`         | Require two `unit_int` values with definitionally equivalent units        |
| Comparisons | Require definitionally equivalent units and retain PHP's native result    |

Multiplication and division may combine a unit value with a bare numeric scalar. Division always produces a
`unit_float`; other operations produce a float brand when either magnitude is float-like. Unary signs preserve the
original magnitude kind and unit. Finite operand unions are evaluated arm by arm; Yumemi returns the union of valid
results and rejects the whole operation if any possible pairing is invalid.

For example, distance divided by time is inferred as speed, while distance multiplied by time is rejected at a speed
boundary:

```php
<?php

/** @param unit_float<'meter / second'> $speed */
function saveSprintSpeed(float $speed): void {}

/** @var unit_float<'meter'> $distance */
$distance = 100.0;
/** @var unit_float<'second'> $elapsed */
$elapsed = 9.58;

saveSprintSpeed($distance / $elapsed);

//! expects unit_float<'meter / second'>, unit_float<'meter * second'> given
saveSprintSpeed($distance * $elapsed);
```

Definitional equivalence understands catalog definitions such as `newton = kilogram * meter / second^2`. It does not
make compatible scales interchangeable: `meter + foot` remains an error because no runtime conversion occurs.

Equality, identity, ordering, and spaceship comparisons follow the same rule: native PHP compares the stored magnitudes
without converting either operand, so dimensionally compatible but differently scaled units remain invalid.

Exponentiation requires a statically known integer exponent. Rational roots and approximate real powers are not part of
the current expression model.

## Boundary Helpers

Yumemi provides three functions for introducing and converting native unit values:

- `unit($value, $unit)` validates a multiplicative unit and returns the unchanged native magnitude branded as `unit_int`
  or `unit_float`.
- `unit_factor($from, $to)` returns a native conversion ratio branded as `to / from`. Multiplying it by a source value
  cancels the source unit and produces the target unit.
- `unit_to($value, $from, $to)` performs the conversion directly and returns a float. Multiplicative targets retain a
  `unit_float` brand.

```php
<?php

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_factor;
use function jbboehr\Yumemi\unit_to;

/** @param unit_float<'meter'> $meters */
function acceptHeightInMeters(float $meters): void {}

$height = unit(6, 'foot');
$byFactor = $height * unit_factor('foot', 'meter');
$converted = unit_to($height, 'foot', 'meter');

acceptHeightInMeters($byFactor);
acceptHeightInMeters($converted);

assert($height === 6);
assert(abs($byFactor - $converted) < 1e-12);
```

`unit_factor()` supports multiplicative units only. `Units::conversionFactor()` is the corresponding exact runtime API
and returns a `Rational`; the native helper returns a float so converting a branded integer promotes it to a branded
float.

`unit_to()` also performs affine conversions. A multiplicative target such as `kelvin` remains branded. An affine target
such as `celsius` is plain `float` because the current native type model cannot distinguish absolute coordinates from
delta temperatures.

If a branded value is passed to `unit_to()`, its brand must match the declared source unit. `unit()` and known helper
arguments are also validated against the configured catalog. Unknown constant strings fail analysis; genuinely dynamic
strings cannot be proven and fall back to the functions' native return types.

Finite literal-string unions passed to `unit()`, `unit_factor()`, and `unit_to()` preserve the corresponding union of
unit brands. The conversion helpers validate every source/target combination and reject the call if any pairing is
invalid. Conversion targets on the `Quantity` boundaries described below likewise preserve finite unions.

## Quantity Types

Runtime quantities have the generic PHPStan forms `Quantity<'unit'>` and `PointQuantity<'coordinate'>`.
`Units::quantity()`, `parseQuantity()`, `deltaQuantity()`, and `point()` infer the corresponding type when their
relevant string is constant or a finite literal-string union. Fluent methods preserve or transform the generic brand
while performing the real exact operation at runtime. Finite unions of branded quantity or point receivers and operands
are evaluated arm by arm; an operation is rejected when any possible pairing is incompatible.

```php
<?php

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

/** @param Quantity<'meter / second'> $speed */
function storeAverageSpeed(Quantity $speed): void {}

$units = Units::default();
$distance = $units->quantity(100, 'meter');
$duration = $units->quantity(10, 'second');
$speed = $distance->div($duration);

storeAverageSpeed($speed);
assert($speed->toString() === '10 * meter / second');
```

The extension models current unit-sensitive methods, including:

- arithmetic through `add()`, `sub()`, `addWithSameUnit()`, `subWithSameUnit()`, `mul()`, `div()`, `neg()`, and `pow()`;
- conversion through `to()` and `valueIn()`;
- native extraction through `intValueIn()`, `exactIntValueIn()`, `decimalValueIn()`, `exactDecimalValueIn()`, and
  `floatValueIn()`;
- unit transformation through `normalize()` and `simplify()`;
- comparisons through `compareTo()`, `equals()`, `lessThan()`, `lessThanOrEqualTo()`, `greaterThan()`, and
  `greaterThanOrEqualTo()`.

Known invalid arithmetic, construction, conversion, and comparison calls produce standalone diagnostics even when the
method result is unused. A branded magnitude supplied to `Units::quantity()` must match the unit being assigned:
`quantity()` labels an existing magnitude and does not implicitly convert it.

Integer and float extraction methods return a native brand when their target unit is known. For example,
`floatValueIn('foot')` returns `unit_float<'international_foot'>`, bridging an exact quantity back to a statically
branded native value. Decimal extraction returns a string while retaining static validation of the target unit.

An explicit target can also brand conversion and extraction results from an unbranded `Quantity`. PHPStan cannot prove
the unknown source dimension in that case, but it can represent the requested result. A genuinely dynamic target falls
back to an unbranded return type.

`PointQuantity<'celsius'>` carries both the coordinate origin and its difference scale. Coordinate aliases are
definitionally equivalent, but different scales such as Celsius, Fahrenheit, and Kelvin remain distinct generic types
even though their points can be converted and compared. PHPStan models the affine operation rules:

- `PointQuantity::add()` and `sub()` accept a dimensionally compatible `Quantity` and preserve the point type;
- `difference()` accepts a compatible point and returns `Quantity<'delta-unit'>` in the receiver's scale;
- `to()` returns a point branded with the target coordinate scale;
- point comparisons and numeric extraction validate constant targets and preserve their native return types.

Direct PHPDoc may use forms such as `PointQuantity<'celsius'>`. Dynamic coordinate strings fall back to unbranded
`PointQuantity`, following the same policy as ordinary quantities.

## Registry Configuration

PHPStan uses the default UDUNITS2 catalog unless `parameters.yumemi.registryFactory` names an autoloadable class
implementing `UnitRegistryFactory`. Its static `create()` method returns the complete immutable registry used by every
Yumemi extension path:

```php
<?php

namespace App\PHPStan;

use jbboehr\Yumemi\PHPStan\UnitRegistryFactory;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;

final class DocumentationRegistryFactory implements UnitRegistryFactory
{
    public static function create(): UnitRegistry
    {
        return UnitRegistryBuilder::default()
            ->define('widget = 12 * meter')
            ->alias('widgets', 'widget')
            ->build();
    }
}
```

Configure the factory in `phpstan.neon`:

```neon
parameters:
    yumemi:
        registryFactory: App\PHPStan\DocumentationRegistryFactory
```

Use `UnitRegistryBuilder::default()` to extend or override UDUNITS2, or `UnitRegistryBuilder::empty()` for an isolated
catalog. Registry contents contribute to PHPStan's result-cache fingerprint.

The configured registry controls static analysis only. Applications using custom units in both layers should construct
their runtime `Units` context from the same factory. PHPStan assumes one authoritative registry for an analysis run and
does not track a separate catalog identity on each value. See [Custom Registries](catalog.md#custom-registries) for the
runtime builder and overlay semantics.

## Extension-Optional Annotations

Libraries that cannot require Yumemi from every consumer can pair ordinary fallback PHPDoc with `@yumemi-param`,
`@yumemi-return`, or `@yumemi-var`. Enable promotion explicitly after the primary extension:

```neon
includes:
    - vendor/jbboehr/yumemi/extension.neon
    - vendor/jbboehr/yumemi/yumemi-tags.neon
```

Without `yumemi-tags.neon`, these are unknown tags and the ordinary PHPDoc or native types remain effective. With it,
Yumemi promotes them onto PHPStan's normal type surface for parameters, returns, properties, and local variables.

A Yumemi tag may replace a fallback only when erasing its units produces the same PHPDoc structure. Every
`unit_int<'...'>` must erase to `int`, every `unit_float<'...'>` to `float`, and every `Quantity<'...'>` to `Quantity`,
including within nullable, union, intersection, and generic types. Parameter references and variadic markers must also
match. Union and intersection order and nullable spelling do not matter. `@phpstan-*` takes priority over the ordinary
tag. An already promoted `@phpstan-*` tag with exactly the same unit-bearing structure is accepted idempotently. Any
other mismatch leaves the fallback unchanged and reports a diagnostic.

```php
<?php

use function jbboehr\Yumemi\unit;

/**
 * @param int $length
 *
 * @yumemi-param unit_int<'meter'> $length
 */
function storeWarehouseLength(int $length): void {}

//! expects unit_int<'meter'>, int given
storeWarehouseLength(5);

//! expects unit_int<'meter'>, unit_int<'international_foot'> given
storeWarehouseLength(unit(3, 'foot'));
```

Without tag promotion, both calls are checked against the ordinary `int` fallback and are valid.

The integration is opt-in because it replaces internal PHPStan parser services for analyzed source and stubs. It may
conflict with another extension replacing the same services and remains an upgrade risk. Application code should
normally use direct Yumemi types; integrations for third-party libraries can use ordinary PHPStan stubs or the bundled
package stubs described below.

### Package Stubs

`yumemi-tags.neon` can conditionally load bundled unit-aware stubs for selected Composer packages. Package selection is
explicit; Yumemi detects the installed version through Composer and refuses unknown, missing, or unsupported packages
rather than silently applying a potentially incompatible signature.

```neon
includes:
    - vendor/jbboehr/yumemi/extension.neon
    - vendor/jbboehr/yumemi/yumemi-tags.neon

parameters:
    yumemi:
        stubs:
            - illuminate/cache
```

The `illuminate/cache` integration supports major versions 11 through 13. As of 2026-07-31, the isolated consumer suite
has verified the stubs against `v11.51.0`, `v12.64.0`, and `v13.23.0`. CI resolves the latest compatible release of each
supported major, so these versions are verification snapshots rather than pins.

The integration brands integer duration boundaries in the cache contracts and concrete repository, cache stores and
locks, `RateLimiter`, and `RateLimiting\Limit`. Seconds, milliseconds, minutes, hours, and days remain distinct exact
units. Representative examples include:

<!-- yumemi-example: illuminate-cache-invalid -->

```php
<?php

use Illuminate\Cache\Lock;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Store;

use function jbboehr\Yumemi\unit;

function configureCacheBoundaries(Store $store, RateLimiter $limiter, Lock $lock): void
{
    $store->put('report', 'ready', unit(30, 'second'));
    $limiter->hit('report', unit(2, 'minute')); // PHPStan rejects this seconds boundary.
    $lock->betweenBlockedAttemptsSleepFor(unit(250, 'millisecond'));
    Limit::perHour(100, unit(1, 'hour'));
}
```

Plain integers are rejected at annotated duration boundaries, and dimensionally compatible but differently scaled units
are not converted implicitly. Existing `DateTimeInterface` and `DateInterval` alternatives remain valid, as do calls
that omit an optional duration and use Laravel's default. Branded return and property types cover
`Repository::getDefaultCacheTime()`, `RateLimiter::availableIn()`, and `Limit::$decaySeconds`.

The stub is deliberately limited to signatures shared by all supported majors. Version-specific APIs can be added when
their compatibility policy and test matrix are clear. Laravel itself remains optional and is not a Yumemi development
dependency; isolated consumer tests install each supported major separately.

## Diagnostics

Yumemi emits stable rule identifiers so errors can be suppressed or included in a PHPStan baseline at the appropriate
scope:

| Identifier                             | Reported condition                                                                                   |
| -------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `yumemi.invalidUnitCall`               | An invalid constant `unit()`, `unit_factor()`, or `unit_to()` call                                   |
| `yumemi.invalidUnitComparison`         | A native equality, identity, ordering, or spaceship comparison with incompatible unit operands       |
| `yumemi.invalidQuantityConstruction`   | Invalid `Units::quantity()`, `parseQuantity()`, `deltaQuantity()`, or `point()` construction         |
| `yumemi.invalidQuantityArithmetic`     | Invalid `add()`, `sub()`, `addWithSameUnit()`, or `subWithSameUnit()` operands                       |
| `yumemi.invalidQuantityConversion`     | An invalid or incompatible `Quantity` conversion or native-extraction target                         |
| `yumemi.invalidQuantityComparison`     | A `Quantity` comparison whose statically known units are incompatible                                |
| `yumemi.invalidPointQuantityOperation` | An invalid point translation, difference, conversion, extraction, or comparison                      |
| `yumemi.docTagSyntax`                  | Invalid `@yumemi-param`, `@yumemi-return`, or `@yumemi-var` syntax                                   |
| `yumemi.docTagDuplicate`               | More than one Yumemi tag targets the same fallback position                                          |
| `yumemi.docTagUnsupported`             | A Yumemi tag appears on a declaration that does not support that tag kind                            |
| `yumemi.docTagParameter`               | A parameter name is unknown or an unnamed `@yumemi-var` fallback is ambiguous                        |
| `yumemi.docTagType`                    | A Yumemi tag contains an invalid unit-bearing type                                                   |
| `yumemi.docTagTransform`               | Erasing the units does not reproduce the fallback PHPDoc structure                                   |
| `binaryOp.invalid`                     | Invalid native unit arithmetic; this is PHPStan's standard binary-operation identifier, not Yumemi's |

The first six identifiers apply even when an invalid call's result is unused. Syntax diagnostics preserve the runtime
parser's bounded caret excerpt while PHPStan anchors the error to the containing PHP or PHPDoc line.

## Limitations

Important limits of the current static model are:

- Dynamic unit strings cannot be validated and intentionally fall back to native or unbranded return types.
- PHPStan supports one configured registry and does not track runtime registry identity per value.
- Casts and unsupported PHP built-ins can erase native unit brands.
- Native `+` and `-` cannot convert dimensionally compatible magnitudes; use an explicit conversion or `Quantity`.
- Native affine targets remain unbranded because native scalars do not retain point-versus-difference identity. Use
  `PointQuantity<'...'>` when that identity must remain statically visible.
- `unit_to()` and `unit_factor()` do not preserve correlation across independent source and target unions. They validate
  the Cartesian product and therefore reject a correlated union call if any cross-pairing would be invalid.
- Unit exponentiation supports constant integers only.
- Dimensional analysis cannot distinguish different physical meanings with the same dimension, such as gray and sievert.

Add targeted PHPStan integrations for demonstrated application workflows rather than assuming every cast, built-in, or
third-party API preserves a unit brand.
