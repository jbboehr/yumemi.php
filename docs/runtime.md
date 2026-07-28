# Runtime Reference

Yumemi's runtime API provides exact unit conversion and quantity arithmetic. `Rational` is the authoritative magnitude
type; conversion to native integers, decimals, or floats is always explicit.

See the [unit syntax reference](unit-syntax.md) for accepted expressions and the [catalog reference](catalog.md) for
default units, custom registries, and catalog regeneration.

## Contexts And Construction

`Units::default()` returns one shared context backed by the generated UDUNITS2 catalog. Repeated calls return the same
instance, so quantities created by separate calls can be combined.

Use `new Units($registry)` for an isolated or customized catalog. Quantities can interact only when they belong to the
same `Units` instance. Combining quantities from different contexts throws `IncompatibleQuantityContextException`, even
when their unit strings happen to match.

Create quantities through the context:

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;

$units = Units::default();
$distance = $units->quantity(new Rational(3, 2), 'kilometer');

assert($distance->valueToString() === '3/2');
assert($distance->unitToString() === 'kilometer');
assert($distance->valueIn('meter')->toString() === '1500');
assert($distance->dimension()->toString() === 'length');
```

The public `value()` and `unit()` accessors return the exact `Rational` magnitude and symbolic `Expr`. The corresponding
public readonly properties remain available.

## Expression Operations

The `Units` facade exposes expression-level operations:

- `parse()` resolves and reduces a unit expression.
- `unit()` resolves one catalog unit name.
- `dimension()` returns the seven-axis SI dimension vector.
- `compatible()` checks dimensional compatibility.
- `conversionFactor()` returns the exact factor between compatible expressions.
- `convert()` applies that factor to an `int` or `Rational`.
- `normalize()` substitutes derived definitions and retains their scale in the expression.

Incompatible conversions throw `IncompatibleUnitException`; unknown names throw `UnitNotFoundException`.

## Quantity Arithmetic

Multiplication and division combine magnitudes and reduce the chosen symbolic unit syntax. They do not substitute unit
definitions or automatically convert compatible units:

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();
$distance = $units->quantity(2, 'meter / second')->mul($units->quantity(3, 'second'));
$ratio = $units->quantity(3, 'meter')->div($units->quantity(2, 'foot'));

assert($distance->toString() === '6 * meter');
assert($ratio->toString() === '3/2 * meter / foot');
```

`mul()` and `div()` also accept an `int` or `Rational` scalar. `neg()` changes only the magnitude. `pow()` raises both
the magnitude and unit expression to an integer power.

Addition and subtraction require compatible dimensions. The right operand is converted exactly into the left operand's
unit, and the result preserves the left symbolic unit:

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();
$total = $units->quantity(1, 'meter')->add($units->quantity(100, 'centimeter'));

assert($total->toString() === '2 * meter');
assert($total->sub($units->quantity(1, 'foot'))->unitToString() === 'meter');
```

`addWithSameUnit()` and `subWithSameUnit()` reject conversions. They require definitionally equivalent normalized units
with the same scale. Thus `meter` and `100 * centimeter` may be equivalent, but `meter` and `centimeter` are not.

## Conversion And Comparison

`to()` returns a new quantity whose magnitude has been converted to the requested symbolic unit. `valueIn()` returns
only the exact converted magnitude and leaves the quantity unchanged.

`compareTo()`, `equals()`, `lessThan()`, `lessThanOrEqual()`, `greaterThan()`, and `greaterThanOrEqual()` convert a
compatible right operand exactly before comparing. Incompatible dimensions throw `IncompatibleUnitException`.

Do not use PHP's object comparison operators as unit-aware comparisons. They compare PHP object state rather than
Yumemi's conversion semantics.

## Normalization And Simplification

`normalize()` and `simplify()` deliberately have different value behavior:

- `Quantity::normalize()` substitutes unit definitions but does not change the stored magnitude. Scale remains in the
  resulting unit expression.
- `Quantity::simplify()` substitutes unit definitions and moves the normalized scale into the stored magnitude.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$quantity = Units::default()->quantity(2, 'centimeter');
$normalized = $quantity->normalize();
$simplified = $quantity->simplify();

assert($normalized->valueToString() === '2');
assert($normalized->unitToString() === '1/100 * meter');
assert($simplified->valueToString() === '1/50');
assert($simplified->unitToString() === 'meter');
```

Neither operation chooses a preferred human-scale unit. Explicit conversion through `to()` is the operation for
requesting a particular display unit.

## Native Numeric Output

Exact `Rational` values can be extracted after conversion:

- `intValueIn()` follows `intdiv()`-style truncation toward zero and throws if the result does not fit a PHP integer.
- `exactIntValueIn()` additionally requires an integral result.
- `decimalValueIn()` returns a fixed number of decimal places using an explicit `RoundingMode`.
- `exactDecimalValueIn()` returns a minimal terminating decimal or throws for non-terminating rational values.
- `floatValueIn()` rounds to binary64 with ties to even and throws on infinity or nonzero underflow to zero.

PHP 8.2 and 8.3 receive the PHP 8.4 `RoundingMode` enum through `symfony/polyfill-php84`.

## Dimensions

`Units::dimension()`, `Quantity::dimension()`, and resolved expressions expose a `Dimension` containing seven integer
powers in this order:

```text
length, mass, time, electric current, temperature, amount of substance, luminous intensity
```

Dimensions support multiplication, division, integer powers, equality, axis accessors, `powers()`, and
`isDimensionless()`. Dimensional equality cannot distinguish semantically different quantities with the same physical
dimension, such as gray and sievert.

## Formatting

`Units::format()`, `Quantity::format()`, and `Quantity::formatUnit()` accept immutable `FormatOptions`. Options control:

- preserved, canonical, or symbol unit names;
- ASCII or Unicode typography;
- numeric, word, or empty dimensionless output;
- fraction or negative-power division layout.

Formatting changes presentation only. It does not normalize derived definitions, convert magnitudes, or choose compact
units. `Units::formatter()` returns a reusable formatter for repeated calls with the same options.

The default format preserves supplied names, uses parser-compatible ASCII, renders dimensionless expressions as `1`, and
uses fraction layout. Unicode output with numeric dimensionless style is also parser-compatible.

## String Forms

`Quantity::toString()` and `unitToString()` use the default display formatter. `valueToString()` returns the exact
rational magnitude. `Expr::toString()` is a structural/debug representation and is not the configurable display API.

Expression equality is structural. It does not compare either display strings or normalized physical dimensions.
