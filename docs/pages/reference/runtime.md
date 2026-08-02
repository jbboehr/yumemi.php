# Runtime Reference

Yumemi's runtime API provides exact unit conversion and quantity arithmetic. `Rational` is the authoritative magnitude
type; conversion to native integers, decimals, or floats is always explicit.

See the [unit syntax reference](unit-syntax.md) for accepted expressions and the [catalog reference](catalog.md) for
default units and custom registries.

Most applications can start with quantity construction, arithmetic, conversion, and native numeric output. The
expression, dimension, formatting, and string-form sections cover lower-level manipulation and presentation when those
needs arise.

## Common Tasks

| I need to...                                        | Use                                    |
| --------------------------------------------------- | -------------------------------------- |
| Construct an exact quantity                         | `Units::quantity()`                    |
| Construct an exact decimal magnitude                | `Rational::fromDecimalString()`        |
| Construct an exact coordinate point                 | `Units::point()`                       |
| Construct a difference for a coordinate scale       | `Units::deltaQuantity()`               |
| Parse a value and unit together                     | `Units::parseQuantity()`               |
| Convert a quantity                                  | `Quantity::to()`                       |
| Convert a coordinate point                          | `PointQuantity::to()`                  |
| Preserve the exact rational result after conversion | `Quantity::valueIn()`                  |
| Obtain a minimal exact terminating decimal          | `Quantity::exactDecimalValueIn()`      |
| Obtain a rounded decimal with a requested scale     | `Quantity::decimalValueIn()`           |
| Obtain a native binary floating-point result        | `Quantity::floatValueIn()`             |
| Convert a native scalar                             | `unit_to()`                            |
| Add compatible quantities                           | `Quantity::add()`                      |
| Reject implicit unit conversion                     | `Quantity::addWithSameUnit()`          |
| Select symbols or Unicode notation                  | `FormatOptions` and formatting methods |

`exactDecimalValueIn()` throws when the exact rational result has a non-terminating decimal expansion. Use
`decimalValueIn()` with an explicit scale and rounding mode in that case.

Every throwable explicitly created by Yumemi implements `jbboehr\Yumemi\Exception\ExceptionInterface`. Yumemi's wrappers
also extend their corresponding native PHP classes, so callers may catch the common interface, a specific Yumemi
exception, or a native parent such as `InvalidArgumentException`. Errors raised directly by PHP or a dependency are not
covered by this marker.

## Contexts And Construction

`Units::default()` returns one shared context backed by the generated UDUNITS2 catalog. Repeated calls return the same
instance, so quantities created by separate calls can be combined.

Use `new Units($registry)` for an isolated or customized catalog. Quantities can interact only when they belong to the
same `Units` instance. Combining quantities from different contexts throws `IncompatibleQuantityContextException`, even
when their unit strings happen to match.

Create quantities through the context:

```php
<?php

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;

$units = Units::default();
$distance = $units->quantity(new Rational(3, 2), 'kilometer');

assert($distance->valueToString() === '3/2');
assert($distance->unitToString() === 'kilometer');
assert($distance->valueIn('meter')->toString() === '1500');
assert($distance->dimension()->toString() === 'length');
```

Quantity strings use the same expression grammar:

```php
<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$speed = $units->parseQuantity('2 meter / (4 second)');

assert($speed->valueToString() === '1/2');
assert($speed->unitToString() === 'meter / second');
assert($units->parseQuantity('meter')->valueToString() === '1');
```

`parseQuantity()` combines every explicit numeric factor into the exact `Rational` magnitude. It does not extract scales
introduced by catalog resolution: `100 centimeter` retains value `100` and unit `centimeter`, while conversions still
account for the centi prefix. A constant-only expression is dimensionless.

The public `value()` and `unit()` accessors return the exact `Rational` magnitude and symbolic `Expr`. The corresponding
public readonly properties remain available.

### Exact Rational Values

Quantity and point factories accept `int|Rational`, not `float`. This prevents a binary floating-point approximation
from entering an exact calculation without an explicit conversion. Construct fractions directly, and construct decimal
or scientific values from their source strings:

```php
<?php

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;

$fraction = new Rational(3, 2);
$decimal = Rational::fromDecimalString('1.25');
$scientific = Rational::fromDecimalString('1e-3');
$length = Units::default()->quantity($decimal, 'meter');

assert($fraction->toString() === '3/2');
assert($decimal->toString() === '5/4');
assert($scientific->toString() === '1/1000');
assert($length->valueToString() === '5/4');
```

`Rational` provides exact `add()`, `sub()`, `mul()`, `div()`, and integer `pow()` operations, together with
`compareTo()` and `equals()`. `toString()` returns a fraction, `toDecimalExact()` requires a terminating decimal, and
`toDecimal()` uses an explicit scale and `RoundingMode`. Conversion to native values remains explicit through `toInt()`,
`toIntExact()`, and `toFloat()`. Integer powers and the effective decimal exponent accepted by
`Rational::fromDecimalString()` are limited to the inclusive range `-10000` through `10000`.

## Debugging, JSON, And Serialization

`Rational`, `Quantity`, `PointQuantity`, `Dimension`, and the catalog descriptor value objects implement
`JsonSerializable`. Exact rational components are decimal strings, so JSON encoding never loses precision:

```php
<?php

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;

$quantity = Units::default()->quantity(new Rational(1, 3), 'meter / second');
$json = json_encode($quantity, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

assert($json === '{"value":{"numerator":"1","denominator":"3"},"unit":"meter / second"}');

$restored = unserialize(serialize($quantity));

assert($restored->units() === Units::default());
assert($restored->valueToString() === '1/3');
assert($restored->unitToString() === 'meter / second');
```

`Rational` JSON contains `numerator` and `denominator` strings. Quantity JSON contains that exact `value` and a
formatted `unit` string. Dimension JSON names all seven axes. Descriptor JSON follows its public constructor state,
renders backed enums as strings, and nests dynamic prefix decomposition.

Compact `__debugInfo()` output follows the same representation. Quantities add only a short context identity; dumping a
quantity does not recursively print its `Units` registry and catalog.

Native serialization is versioned and verifies unit semantics when restoring a quantity. Values created through
`Units::default()` may use PHP's ordinary `serialize()` and `unserialize()` and always return to the shared default
context. A quantity from a custom registry must be restored through that context:

```php
<?php

use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;

$units = new Units(
    UnitRegistryBuilder::default()
        ->define('smoot = 1.7018 * meter')
        ->build(),
);
$serialized = serialize($units->quantity(2, 'smoot'));
$restored = $units->deserialize($serialized);

assert($restored->units() === $units);
assert($restored->valueIn('meter')->toString() === '8509/2500');
```

Raw `unserialize()` rejects a custom-context quantity with an exception directing the caller to `Units::deserialize()`.
The scoped method restores its previous context in `finally`, including across nested calls, and forwards PHP's native
`unserialize()` options. Pass `allowed_classes` to restrict which classes a known graph may instantiate and `max_depth`
to bound nesting. The allow-list must include every serialized object class in the graph; `allowed_classes: false`
produces `__PHP_Incomplete_Class` objects and cannot restore a quantity. Yumemi does not choose a default allow-list
because `deserialize()` may return any caller-defined graph. Serialized unit semantics are checked against the selected
registry, so a changed or incorrect registry is rejected rather than silently reinterpreting the value.

One serialized graph may contain default values and values from one custom context. Graphs containing values from
several distinct custom contexts require a future registry-identifier resolver. Never pass untrusted data to PHP
deserialization; `allowed_classes` and `max_depth` reduce exposure but do not make arbitrary payloads safe. Serialize
value objects directly: casting one to an array bypasses its controlled serialization representation.

## Expression Operations

The `Units` facade exposes expression-level operations:

- `parse()` resolves and reduces a unit expression.
- `parseUnit()` is an explicit alias of `parse()`.
- `parseQuantity()` separates explicit constants from a symbolic unit expression and returns a `Quantity`.
- `unit()` resolves one catalog unit name.
- `dimension()` returns the seven-axis SI dimension vector.
- `areCompatible()` checks dimensional compatibility.
- `conversionFactor()` returns an exact value-independent factor and throws `NonMultiplicativeConversionException` when
  the conversion includes an offset.
- `convert()` applies an exact scale-and-offset conversion to an `int` or `Rational`.
- `convertFloat()` applies the equivalent affine map in binary floating point.
- `deltaUnit()` returns the multiplicative unit used for differences on a named coordinate scale.
- `deltaQuantity()` constructs an exact difference using a coordinate scale's multiplicative unit.
- `point()` constructs an exact `PointQuantity` on a named coordinate scale.
- `normalize()` substitutes derived definitions and retains their scale in the expression.

Incompatible conversions throw `IncompatibleUnitException`; unknown names throw `UnitNotFoundException`. Native float
conversion rejects non-finite inputs, results that overflow to infinity, and nonzero exact results that underflow to
zero. Exact results should use `convert()` instead.

For native arithmetic, `unit_factor()` returns the conversion factor as a `float`. PHPStan brands that value as the
target unit divided by the source unit, so ordinary multiplication cancels the source brand:

```php
<?php

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_factor;

$meters = unit(3, 'meter');
$feet = $meters * unit_factor('meter', 'foot');

assert(abs($feet - 9.84251968503937) < 1e-12);
```

Use `conversionFactor()` when the exact `Rational` is required. Both factor APIs reject conversions involving an offset;
use `convert()`, `convertFloat()`, or `unit_to()` for affine conversion.

### Affine Conversion

Explicit conversion supports UDUNITS2 affine temperature units and custom `@` definitions. The exact conversion core
maps each coordinate into canonical base units as `scale * value + offset`; decimal catalog constants remain exact
`Rational` values. A custom `@` origin may be a signed integer or finite decimal literal:

```php
<?php

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit_to;

$units = Units::default();

assert($units->convert(0, 'celsius', 'kelvin')->toString() === '5463/20');
assert($units->convert(100, 'celsius', 'fahrenheit')->toString() === '212');
assert($units->convert(-40, 'celsius', 'fahrenheit')->toString() === '-40');
assert(abs($units->convertFloat(37.0, 'celsius', 'fahrenheit') - 98.6) < 1e-12);
assert(unit_to(32, 'fahrenheit', 'celsius') === 0.0);
```

`dimension()` and `areCompatible()` understand the affine unit's reference dimension. `conversionFactor()` succeeds for
an identity or another offset-free conversion, such as `celsius` to an equivalent alias, but it cannot represent
`celsius` to `kelvin` because that result depends on the input value.

Use `PointQuantity` when a program must retain an exact coordinate point and perform affine arithmetic:

```php
<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$freezing = $units->point(0, 'celsius');
$rise = $units->deltaQuantity(18, 'fahrenheit');
$warmer = $freezing->add($rise);
$interval = $units->point(100, 'celsius')->difference($freezing);

assert($freezing->valueIn('kelvin')->toString() === '5463/20');
assert($warmer->toString() === '10 * celsius');
assert($interval->toString() === '100 * delta_celsius');
assert($interval->valueIn('delta_fahrenheit')->toString() === '180');
```

A `PointQuantity` retains an exact `Rational` coordinate and a named scale. `to()`, `valueIn()`, comparisons, and native
numeric output apply full scale-and-offset conversion. `difference()` subtracts another compatible point and returns a
`Quantity` in the receiver's delta unit. `add()` and `sub()` translate the point by a compatible `Quantity` while
preserving the point's coordinate unit. All operands must belong to the same `Units` context.

The catalog provides explicit multiplicative difference units such as `delta_celsius`, `delta_fahrenheit`, `Δ°C`, and
`Δ°F`. They participate in ordinary quantity and expression algebra, so `delta_celsius / second` is valid. Formatter
symbol mode renders the named aliases as `Δ°C` and `Δ°F` with Unicode typography.

No affine unit is silently rewritten inside algebra. `celsius / second` and `Units::quantity(1, 'celsius')` remain
invalid; use `delta_celsius / second` for a rate or `Units::point(1, 'celsius')` for a coordinate. `parse()`,
`parseUnit()`, `unit()`, `parseQuantity()`, `quantity()`, normalization, multiplication, division, powers, and prefixes
continue to reject the affine unit itself. Logarithmic definitions remain recognized but unevaluable.

## Quantity Arithmetic

Multiplication and division combine magnitudes and reduce the chosen symbolic unit syntax. They do not substitute unit
definitions or automatically convert compatible units:

```php
<?php

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

`compareTo()`, `equals()`, `lessThan()`, `lessThanOrEqualTo()`, `greaterThan()`, and `greaterThanOrEqualTo()` convert a
compatible right operand exactly before comparing. Incompatible dimensions throw `IncompatibleUnitException`.

Do not use PHP's object comparison operators as unit-aware comparisons. They compare PHP object state rather than
Yumemi's conversion semantics.

```php
<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$meter = $units->quantity(1, 'meter');

assert($meter->equals($units->quantity(100, 'centimeter')));
assert($meter->greaterThan($units->quantity(3, 'foot')));
assert($meter->lessThan($units->quantity(4, 'foot')));
assert($meter->compareTo($units->quantity(1000, 'millimeter')) === 0);

$rate = $units->quantity(2, 'centimeter / second')->div($units->quantity(3, 'foot'));

assert($rate->toString() === '2/3 * centimeter / (foot * second)');
assert($rate->valueIn('1 / second')->toString() === '25/1143');
```

## Normalization And Simplification

`normalize()` and `simplify()` deliberately have different value behavior:

- `Quantity::normalize()` substitutes unit definitions but does not change the stored magnitude. Scale remains in the
  resulting unit expression.
- `Quantity::simplify()` substitutes unit definitions and moves the normalized scale into the stored magnitude.

```php
<?php

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

```php
<?php

use jbboehr\Yumemi\Units;

$length = Units::default()->quantity(1, 'foot');

assert($length->intValueIn('meter') === 0);
assert($length->exactDecimalValueIn('meter') === '0.3048');
assert($length->decimalValueIn('meter', 2, \RoundingMode::HalfEven) === '0.30');
assert($length->floatValueIn('meter') === 0.3048);
```

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

- `UnitNameStyle::Preserve`, `Canonical`, or `Symbol` unit names;
- `Typography::Ascii` or `Unicode` operators and powers;
- `DimensionlessStyle::One`, `Word`, or `Empty` output;
- `DivisionStyle::Fraction` or `NegativePowers` layout.

Formatting changes presentation only. It does not normalize derived definitions, convert magnitudes, or choose compact
units. `Units::formatter()` returns a reusable formatter for repeated calls with the same options.

The default format preserves supplied names, uses parser-compatible ASCII, renders dimensionless expressions as `1`, and
uses fraction layout. Unicode output with numeric dimensionless style is also parser-compatible.

Options may be constructed with named arguments or an immutable `create()->with...()` chain:

```php
<?php

use jbboehr\Yumemi\Formatter\DivisionStyle;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\Typography;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Units;

$units = Units::default();
$options = FormatOptions::create()
    ->withUnitNameStyle(UnitNameStyle::Symbol)
    ->withTypography(Typography::Unicode)
    ->withDivisionStyle(DivisionStyle::NegativePowers);

assert($units->format('kilometers / second^2', $options) === 'km · s⁻²');
assert($units->quantity(3, 'kilometers / second^2')->formatUnit($options) === 'km · s⁻²');
```

`Preserve` keeps the names supplied by the caller. `Canonical` resolves aliases, generated plurals, and one dynamic
prefix to canonical names. `Symbol` selects the shortest deterministic catalog symbol; ASCII falls back to a canonical
name when only Unicode symbols exist. Unknown expression leaves are preserved.

Unicode typography emits `·` and superscript integer powers. The parser accepts those forms, so Unicode output remains
round-trippable when the dimensionless style is `One`. `Word` and `Empty` are presentation-only.

`NegativePowers` moves denominator units to negative powers without rewriting exact rational coefficients. For example,
`1/2 * meter / second` becomes `1/2 * meter * second ^ -1` in ASCII. The default `Fraction` style retains the
denominator.

`Units::format()` parses string input symbolically before rendering, whereas an `Expr` returned by `Units::parse()` has
already been catalog-resolved. Formatting never recovers source spelling that has already been resolved away.

## String Forms

`Quantity::toString()` and `unitToString()` use the default display formatter. `valueToString()` returns the exact
rational magnitude. `Expr::toString()` is a structural/debug representation and is not the configurable display API.

Expression equality is structural. It does not compare either display strings or normalized physical dimensions.
