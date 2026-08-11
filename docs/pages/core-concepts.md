# Core Concepts

<figure class="logion" data-logion="RAS 3:52">
<div class="logion-text">
<blockquote>
<p>In the chamber above the polar night, the Angel of Boundaries unfolded a black fan, and from each rib issued a road of fire. The roads crossed without mingling, and upon them nations unlike in tongue carried one white stone toward a city not yet built. When the first foundation shone below, a voice forbade the roads to surrender their names.</p>
</blockquote>
<p class="logion-citation">— <cite>Revelation of the Artificial Sun 3:52</cite></p>
</div>
<img src="images/logia/RAS-3_52.webp" alt="An angel unfolding a black fan above separate luminous roads leading toward an unbuilt city" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Yumemi exposes native values and exact runtime objects over the same unit engine. Use native values when existing PHP
numbers primarily need PHPStan protection. Use `Quantity` when the program must retain a multiplicative unit, perform
conversions, or preserve exact fractions. Use `PointQuantity` for a position on a coordinate scale, such as a
temperature in Celsius.

The examples below assume the Composer autoloader has already been loaded as shown in
[Getting Started](getting-started.md).

## Choose An API

| Need                                                          | Use                                     |
| ------------------------------------------------------------- | --------------------------------------- |
| Add unit checking to existing PHP numbers and operators       | `unit_int<'...'>` / `unit_float<'...'>` |
| Perform exact conversion or unit-aware runtime arithmetic     | `Quantity<'...'>`                       |
| Represent an exact temperature or other coordinate point      | `PointQuantity<'...'>`                  |
| Convert at an application boundary and return a native number | `unit_to()`                             |

A branded native value is an ordinary PHP `int` or `float`; the unit exists only in PHPStan. Branded values therefore
fit naturally into existing signatures, arrays, frameworks, serialization, and numerical code, with normal scalar
precision. Conversion is explicit through `unit_to()`.

`Quantity` is the ergonomic precision path. Use it when results such as `1/3` must remain exact, rounding must be
deferred, or compatible-unit operations should be expressed through one runtime object.

`PointQuantity` separates coordinate points from multiplicative differences. For example, `celsius` identifies an
absolute temperature while `delta_celsius` identifies a temperature interval. Points can be converted and compared;
subtracting two points returns a `Quantity`, and adding or subtracting a compatible `Quantity` translates a point.
Points themselves do not support multiplication, division, powers, or point-plus-point arithmetic.

All three surfaces use the same parser, catalog, dimensions, and conversion semantics, but they are not equivalent
interfaces. PHP cannot make native `1 meter + 1 foot` produce a correct number without converting one operand. Yumemi
therefore rejects that branded-native addition. `Quantity::add()` performs the conversion at runtime and accepts the
same pair.

```php
<?php

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;

/** @param unit_float<'kilometer / hour'> $speed */
function sendVehicleSpeed(float $speed): void {}

$nativeSpeed = unit(100.0, 'meter') / unit(10.0, 'second');
$nativeKilometersPerHour = unit_to($nativeSpeed, 'meter / second', 'kilometer / hour');

sendVehicleSpeed($nativeKilometersPerHour);
assert(abs($nativeKilometersPerHour - 36.0) < 1e-12);

$units = Units::default();
/** @var Quantity<'meter / second'> $exactSpeed */
$exactSpeed = $units->quantity(100, 'meter')->div($units->quantity(10, 'second'));

assert($exactSpeed->valueIn('kilometer / hour')->toString() === '36');
```

PHPStan knows that `$nativeSpeed` is branded as `unit_float<'meter / second'>`, but that brand does not exist at
runtime: PHP receives only an ordinary `float`. Runtime code therefore cannot recover the source unit, so `unit_to()`
requires both `'meter / second'` and `'kilometer / hour'` explicitly.

See the [PHPStan reference](reference/phpstan.md) for branded-native behavior and the
[runtime reference](reference/runtime.md) for exact quantities.

## Native Values At Trusted Boundaries

Once a native value has a unit brand, PHP still executes ordinary `int` or `float` arithmetic. The brand adds no runtime
wrapper, method dispatch, `Rational` allocation, or unit metadata to those calculations. This makes branded values the
natural path when a unit contract can be established at an application boundary and the remaining work should use normal
scalar operations.

Use `unit($value, 'meter')` when runtime validation of the unit expression against the active catalog is useful. The
function returns `$value` unchanged after parsing the expression; it does not prove that the incoming magnitude was
physically measured in meters. A trusted parameter, property, return type, third-party stub, or local `@var` declaration
can establish the same static brand without runtime parsing:

```php
<?php

/** @var unit_float<'meter'> $warehouseAisleLength */
$warehouseAisleLength = (float) 18;

/** @param unit_float<'meter'> $length */
function storeWarehouseAisleLength(float $length): void {}

storeWarehouseAisleLength($warehouseAisleLength);
```

Use declarations or stubs where the unit is already guaranteed, use `unit()` when catalog validation is desired, and
keep repeated arithmetic on branded native values. When exact fractions or runtime unit identity matter more, use
`Quantity` instead.

A trusted string-oriented contract may use `unit_numeric_string<'...'>` when it exposes a numeric magnitude as text. The
value remains a string until an explicit integer or float cast moves it into numerical code. See
[Numeric Strings](reference/phpstan.md#numeric-strings) for the assignment, cast, and coercion rules.

## Choose An Operation

| Goal                                                       | Operation                                            |
| ---------------------------------------------------------- | ---------------------------------------------------- |
| Brand an existing native magnitude                         | `unit()` or a `unit_int` / `unit_float` PHPDoc type  |
| Label an exact magnitude without converting it             | `Units::quantity()`                                  |
| Parse one string containing a magnitude and unit           | `Units::parseQuantity()`                             |
| Construct an exact coordinate point                        | `Units::point()`                                     |
| Construct a multiplicative coordinate difference           | `Units::deltaQuantity()`                             |
| Check whether two units share a dimension                  | `Units::areCompatible()`                             |
| Check whether two quantities share a context and dimension | `Quantity::isCompatibleWith()`                       |
| Check whether two points share a context and dimension     | `PointQuantity::isCompatibleWith()`                  |
| Convert an exact magnitude                                 | `Units::convert()` or `Quantity::to()` / `valueIn()` |
| Convert a native scalar                                    | `unit_to()` or `Units::convertFloat()`               |
| Obtain an exact multiplicative factor                      | `Units::conversionFactor()`                          |
| Obtain a native factor whose static units cancel           | `unit_factor()`                                      |
| Take an exact root without implicit unit substitution      | `Quantity::root()`                                   |
| Take an exact absolute value                               | `Quantity::abs()`                                    |
| Test an exact magnitude for zero                           | `Quantity::isZero()`                                 |
| Substitute definitions without changing stored magnitude   | `Quantity::normalize()`                              |
| Substitute definitions and fold scale into magnitude       | `Quantity::simplify()`                               |
| Request a particular target unit                           | `Quantity::to()`                                     |
| Change names, symbols, typography, or division layout      | Formatting APIs                                      |

The important semantic boundaries are documented once in the references:

- [Definitional equivalence and compatibility](reference/phpstan.md#definitional-equivalence-and-compatibility) explains
  why native addition cannot convert its operands.
- [Quantity arithmetic](reference/runtime.md#quantity-arithmetic) defines symbolic reduction and compatible-unit
  addition.
- [Affine conversion](reference/runtime.md#affine-conversion) distinguishes coordinate points from multiplicative
  differences.
- [Normalization and simplification](reference/runtime.md#normalization-and-simplification) distinguishes definition
  substitution from magnitude changes.
- [Native numeric output](reference/runtime.md#native-numeric-output) defines exact and approximate extraction.
- [Custom registries](reference/catalog.md#custom-registries) defines catalog and context ownership.

When in doubt, keep conversion explicit. Symbolic algebra preserves the units chosen by the caller; catalog substitution
and magnitude changes occur only through operations whose names communicate that intent.
