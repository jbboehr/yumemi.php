# Core Concepts

Yumemi exposes two representations over the same unit engine. Use native values when existing PHP numbers primarily need
PHPStan protection. Use `Quantity` when the program must retain a unit, perform conversions, or preserve exact
fractions.

The examples below assume the Composer autoloader has already been loaded as shown in
[Getting Started](getting-started.md).

## Choose An API

| Need                                                          | Use                                     |
| ------------------------------------------------------------- | --------------------------------------- |
| Add unit checking to existing PHP numbers and operators       | `unit_int<'...'>` / `unit_float<'...'>` |
| Perform exact conversion or unit-aware runtime arithmetic     | `Quantity<'...'>`                       |
| Convert at an application boundary and return a native number | `unit_to()`                             |

A branded native value is an ordinary PHP `int` or `float`; the unit exists only in PHPStan. Branded values therefore
fit naturally into existing signatures, arrays, frameworks, serialization, and numerical code, with normal scalar
precision. Conversion is explicit through `unit_to()`.

`Quantity` is the ergonomic precision path. Use it when results such as `1/3` must remain exact, rounding must be
deferred, or compatible-unit operations should be expressed through one runtime object.

Both paths use the same parser, catalog, dimensions, and conversion semantics, but they are not equivalent interfaces.
PHP cannot make native `1 meter + 1 foot` produce a correct number without converting one operand. Yumemi therefore
rejects that branded-native addition. `Quantity::add()` performs the conversion at runtime and accepts the same pair.

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

## Choose An Operation

| Goal                                                     | Operation                                            |
| -------------------------------------------------------- | ---------------------------------------------------- |
| Brand an existing native magnitude                       | `unit()` or a `unit_int` / `unit_float` PHPDoc type  |
| Label an exact magnitude without converting it           | `Units::quantity()`                                  |
| Parse one string containing a magnitude and unit         | `Units::parseQuantity()`                             |
| Check whether two units share a dimension                | `Units::areCompatible()`                             |
| Convert an exact magnitude                               | `Units::convert()` or `Quantity::to()` / `valueIn()` |
| Convert a native scalar                                  | `unit_to()` or `Units::convertFloat()`               |
| Obtain an exact multiplicative factor                    | `Units::conversionFactor()`                          |
| Obtain a native factor whose static units cancel         | `unit_factor()`                                      |
| Substitute definitions without changing stored magnitude | `Quantity::normalize()`                              |
| Substitute definitions and fold scale into magnitude     | `Quantity::simplify()`                               |
| Request a particular target unit                         | `Quantity::to()`                                     |
| Change names, symbols, typography, or division layout    | Formatting APIs                                      |

The important semantic boundaries are documented once in the references:

- [Definitional equivalence and compatibility](reference/phpstan.md#definitional-equivalence-and-compatibility) explains
  why native addition cannot convert its operands.
- [Quantity arithmetic](reference/runtime.md#quantity-arithmetic) defines symbolic reduction and compatible-unit
  addition.
- [Normalization and simplification](reference/runtime.md#normalization-and-simplification) distinguishes definition
  substitution from magnitude changes.
- [Native numeric output](reference/runtime.md#native-numeric-output) defines exact and approximate extraction.
- [Custom registries](reference/catalog.md#custom-registries) defines catalog and context ownership.

When in doubt, keep conversion explicit. Symbolic algebra preserves the units chosen by the caller; catalog substitution
and magnitude changes occur only through operations whose names communicate that intent.
