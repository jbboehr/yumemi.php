# Runtime Reference

<figure class="logion" data-logion="OSD 14:37">
<div class="logion-text">
<blockquote>
<p>At the longest night, kindle the amber horizon within the underground cloister, yet veil its eastern quarter with linen and leave the brothers one hour of darkness. Let the crafted radiance warm the sick, ripen the winter figs, and guide the late pilgrim, but suffer it not to counterfeit morning. When the linen brightens from the farther side, extinguish every lamp without lament, for the lesser glory hath completed its obedience.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 14:37</cite></p>
</div>
<img src="../images/logia/OSD-14_37.webp" alt="A linen veil dividing an amber horizon within a cobalt-lit underground cloister" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Yumemi's runtime API provides exact unit conversion and quantity arithmetic. `Rational` is the authoritative magnitude
type; conversion to native integers, decimals, or floats is always explicit.

See the [unit syntax reference](unit-syntax.md) for accepted expressions and the [catalog reference](catalog.md) for
default units and custom registries.

Most applications can start with quantity construction, arithmetic, conversion, and native numeric output. The
expression, dimension, formatting, and string-form sections cover lower-level manipulation and presentation when those
needs arise.

## Common Tasks

| I need to...                                        | Use                                     |
| --------------------------------------------------- | --------------------------------------- |
| Construct an exact quantity                         | `Units::quantity()`                     |
| Construct an exact decimal magnitude                | `Rational::fromDecimalString()`         |
| Construct an exact coordinate point                 | `Units::point()`                        |
| Construct a difference for a coordinate scale       | `Units::deltaQuantity()`                |
| Parse a value and unit together                     | `Units::parseQuantity()`                |
| Convert a quantity                                  | `Quantity::to()`                        |
| Apply application-preferred units                   | `Quantity::toPreferred()`               |
| Choose an engineering prefix in a named unit family | `Quantity::toCompact()`                 |
| Convert a coordinate point                          | `PointQuantity::to()`                   |
| Preserve the exact rational result after conversion | `Quantity::valueIn()`                   |
| Obtain a minimal exact terminating decimal          | `Quantity::exactDecimalValueIn()`       |
| Obtain a rounded decimal with a requested scale     | `Quantity::decimalValueIn()`            |
| Round to a requested significant-digit precision    | `Quantity::significantDecimalValueIn()` |
| Obtain a native binary floating-point result        | `Quantity::floatValueIn()`              |
| Convert a native scalar                             | `unit_to()`                             |
| Add compatible quantities                           | `Quantity::add()`                       |
| Divide a scalar numerator by a quantity             | `Quantity::rdiv()`                      |
| Reject implicit unit conversion                     | `Quantity::addWithSameUnit()`           |
| Take the exact absolute value of a quantity         | `Quantity::abs()`                       |
| Test whether an exact quantity is zero              | `Quantity::isZero()`                    |
| Check whether two quantities are compatible         | `Quantity::isCompatibleWith()`          |
| Check whether two coordinate points are compatible  | `PointQuantity::isCompatibleWith()`     |
| Select symbols or Unicode notation                  | `FormatOptions` and formatting methods  |

`exactDecimalValueIn()` throws when the exact rational result has a non-terminating decimal expansion. Use
`decimalValueIn()` or `significantDecimalValueIn()` with an explicit rounding mode in that case.

Every throwable explicitly created by Yumemi implements `jbboehr\Yumemi\Exception\ExceptionInterface`. Yumemi's wrappers
also extend their corresponding native PHP classes, so callers may catch the common interface, a specific Yumemi
exception, or a native parent such as `InvalidArgumentException`. Errors raised directly by PHP or a dependency are not
covered by this marker. Direct parser-backed APIs report the shared
[parser resource limits](unit-syntax.md#resource-limits) with `Parser\ExpressionLimitExceededException`, a
`LengthException` subtype. The native helpers retain their existing `InvalidArgumentException` boundary and chain the
limit exception as the cause.

### Native Parser Selection

When a compatible `ext-yumemi` parser ABI is loaded, Yumemi automatically uses it for syntax parsing while retaining the
generated PHP parser as its grammar authority and fallback. Both paths produce the same AST, source-span, exception, and
resource-limit contracts; unit resolution and arithmetic remain in PHP.

Set `YUMEMI_NATIVE_PARSER` to `0`, `false`, `off`, `no`, or an empty string in the process environment to force the PHP
parser even when the extension is loaded. Values are case-insensitive. Leave the variable unset, or set it to `1`,
`true`, `on`, or `yes`, for automatic native selection. Any other explicit value fails closed to the PHP fallback so a
misspelled setting cannot unexpectedly enable the optional native path. Configure the flag before application or worker
startup so every parse in that process follows one policy; already-cached successful ASTs are backend-neutral values.
This switch also affects unit expressions parsed during PHPStan analysis because the runtime and analyzer share the same
parser boundary.

## Contexts And Construction

`Units::default()` returns one shared context backed by the generated UDUNITS2 catalog. Repeated calls return the same
instance, so quantities created by separate calls can be combined.

Use `new Units($registry)` for an isolated or customized catalog. Quantities can interact only when they belong to the
same `Units` instance. Combining quantities from different contexts throws `IncompatibleQuantityContextException`, even
when their unit strings happen to match. For cross-context multiplication, the exception records the two process-local
context IDs in ascending order rather than method-receiver order. This keeps `left->mul(right)` and `right->mul(left)`
observably equivalent without permitting either operation. Other quantity operations retain receiver-then-argument
ordering in `leftContextId` and `rightContextId`.

Resolved expressions returned by `parse()`, `unit()`, `normalize()`, and quantity unit accessors retain the same context
boundary. Passing one to a different `Units` instance, combining expressions from different contexts, or semantically
using an expression after its context has been released throws `IncompatibleExpressionContextException`. Structural
equality and formatting remain context-independent. Low-level expression construction is internal. When such an
expression contains unbound unit leaves, a semantic operation stamps a context on an immutable copy for that operation;
the caller's expression remains unbound and can be admitted independently elsewhere. Context admission does not resolve
a definition-less unit name through the receiving catalog, so application code should obtain named expressions from
`parse()` or `unit()`.

Native helpers such as `unit()`, `unit_factor()`, and `unit_to()` use the process-wide default context. Applications
that configure the PHPStan extension with custom units should install the matching runtime context during synchronous
process bootstrap, before starting Fibers or other request scheduling:

<!-- akashi: separate-process -->

```php
<?php

use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit_to;

$units = new Units(
    UnitRegistryBuilder::default()
        ->define('widget = 2 * meter')
        ->build(),
);
Units::setDefault($units);

assert(unit_to(3, 'widget', 'meter') === 6.0);
```

`setDefault(null)` clears the shared context, causing the next `default()` call to create a fresh built-in context.
Already-created quantities retain their original context. A `setDefault()` call that would change the context from
inside a Fiber throws Yumemi's `Exception\LogicException`; idempotently setting the already-installed instance remains
permitted. Native helpers may read a bootstrap-configured default from a Fiber, but the main execution context can still
replace that global while a Fiber is suspended. Applications must therefore treat `setDefault()` as bootstrap or
synchronous test configuration, not as request-local scope. Pass a captured `Units` instance into concurrent work and
call its conversion methods when work needs an isolated registry.

`Units` objects are not cloneable. Construct another instance when an application needs a separate context; even an
equivalent registry snapshot does not make independently constructed contexts interchangeable.

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

`Rational` provides exact `abs()`, `add()`, `sub()`, `mul()`, `div()`, integer `pow()`, and integer-degree `root()`
operations, together with `compareTo()`, `equals()`, and `isZero()`. A root succeeds only when the numerator and
denominator both have exact integer roots; negative values therefore accept only odd degrees. `toString()` returns a
fraction, `toDecimalExact()` requires a terminating decimal, and `toDecimal()` uses an explicit scale and
`RoundingMode`. Conversion to native values remains explicit through `toInt()`, `toIntExact()`, and `toFloat()`. Integer
powers, positive root degrees, and the effective decimal exponent accepted by `Rational::fromDecimalString()` are
limited to `10000` in magnitude. Zero powers follow PHP's computing convention: every base, including zero, raised to
zero returns one.

## Quantity Arithmetic

Multiplication and division combine magnitudes and reduce the chosen symbolic unit syntax. They do not substitute unit
definitions or automatically convert compatible units:

```php
<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$distance = $units->quantity(2, 'meter / second')->mul($units->quantity(3, 'second'));
$ratio = $units->quantity(3, 'meter')->div($units->quantity(2, 'foot'));
$displacement = $units->quantity(-3, 'meter');

assert($distance->toString() === '6 * meter');
assert($ratio->toString() === '3/2 * meter / foot');
assert($displacement->abs()->toString() === '3 * meter');
assert(!$displacement->isZero());
assert($units->quantity(0, 'meter')->isZero());
```

`mul()` and `div()` also accept an `int` or `Rational` scalar. `rdiv()` divides an `int` or `Rational` numerator by the
receiver, producing the exact reciprocal magnitude and symbolic unit; a zero receiver magnitude throws
`DivisionByZeroError`. `abs()` and `neg()` change only the magnitude, while `isZero()` tests that exact magnitude
without converting or discarding the unit. `pow()` raises both the magnitude and unit expression to an integer power.
`pow(0)` returns dimensionless one, including when the original magnitude is zero.

`root()` is the exact inverse for a positive integer degree when both the rational magnitude and every reduced symbolic
unit power have exact roots:

```php
<?php

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;

$rootedArea = Units::default()
    ->quantity(new Rational(4, 9), 'centimeter^2 / second^4')
    ->root(2);

assert($rootedArea->toString() === '2/3 * centimeter / second ^ 2');
```

The degree must be between `1` and `10000`. A non-exact magnitude, an even root of a negative magnitude, or a symbolic
power not divisible by the degree throws `NonExactRootException`. Rooting preserves the units the caller wrote: for
example, `kilometer * millimeter` is not a symbolic square even though substitution reduces it to `meter^2`. Call
`simplify()` or `normalize()` first when that substitution is intentional, then call `root()` on the explicit result.

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

`isCompatibleWith()` checks whether two quantities belong to the same `Units` context and have compatible dimensions. It
returns `false` for a different context or dimension; it does not convert either magnitude or throw merely because the
quantities are incompatible.

Do not use PHP's object comparison operators as unit-aware comparisons. Loose equality and ordering inspect object
state, while strict identity compares object identity; neither uses Yumemi's conversion semantics. When PHPStan can see
a `Quantity` or `PointQuantity` operand, Yumemi reports `yumemi.nativeQuantityComparison` and directs the caller to the
named comparison methods. Equality and identity checks against a definitely `null` operand remain available for
nullable-value presence checks.

```php
<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$meter = $units->quantity(1, 'meter');

assert($meter->equals($units->quantity(100, 'centimeter')));
assert($meter->greaterThan($units->quantity(3, 'foot')));
assert($meter->lessThan($units->quantity(4, 'foot')));
assert($meter->compareTo($units->quantity(1000, 'millimeter')) === 0);
assert($meter->isCompatibleWith($units->quantity(1, 'foot')));
assert(!$meter->isCompatibleWith($units->quantity(1, 'second')));

$rate = $units->quantity(2, 'centimeter / second')->div($units->quantity(3, 'foot'));

assert($rate->toString() === '2/3 * centimeter / (foot * second)');
assert($rate->valueIn('1 / second')->toString() === '25/1143');
```

### Preferred Unit Profiles

Use a preferred-unit profile when one application boundary should consistently select explicit output units for several
dimensions:

```php
<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$displayUnits = $units->preferredUnitProfile([
    'kilometer / hour',
    'kilowatt * hour',
]);

$speed = $units->quantity(25, 'meter / second')->toPreferred($displayUnits);
$duration = $units->quantity(5, 'second')->toPreferred($displayUnits);

assert($speed->valueToString() === '90');
assert($speed->unitToString() === 'kilometer / hour');
assert($duration->toPreferred($displayUnits) === $duration);
```

Each target expression determines its own dimension, and a profile accepts at most one target per dimension. Conversion
is exact. When no target matches, `toPreferred()` returns the same immutable quantity unchanged. The profile and
quantity must belong to the same `Units` instance so custom definitions cannot be silently reinterpreted in another
context.

A dimension does not identify a quantity's purpose. Gray and sievert share dimensions, while information units are
dimensionless. Scope each profile to an application boundary where its dimension-to-target choices make sense; use
separate profiles or explicit `to()` calls when the same dimension has several meanings.

The profile is application configuration and is intentionally not serializable. Because its contents are runtime state,
PHPStan infers an unbranded `Quantity` from `toPreferred()`. Use explicit `to('target')` conversion when subsequent
statically checked arithmetic needs to retain one known unit brand.

### Compact Unit Selection

Use `toCompact()` near an output boundary when the unit family is known but its engineering prefix should follow the
exact magnitude:

```php
<?php

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;

$units = Units::default();
$distance = $units->quantity(12_500, 'meter')->toCompact('meter');
$mass = $units->quantity(new Rational(1, 100), 'kilogram')->toCompact('gram');

assert($distance->valueToString() === '25/2');
assert($distance->unitToString() === 'kilometer');
assert($mass->valueToString() === '10');
assert($mass->unitToString() === 'gram');
```

The argument names one multiplicative unit family. Yumemi considers the unprefixed unit and registry prefixes whose
exact scales are powers of 1000, then chooses a magnitude in the half-open interval `[1, 1000)` when an eligible family
member exists in that range. Selection and conversion use `Rational`; no floating-point logarithm or approximation is
involved. Negative values use their absolute magnitude for selection and retain their sign. Zero uses the unprefixed
base.

At the registry's prefix limits, the smallest or largest available candidate is used. A candidate name that already
exists in the catalog is accepted only when its exact conversion matches the expected prefix scale. This admits
`kilogram` into the `gram` family without mistaking an unrelated exact-name collision for a valid prefixed unit. Results
use canonical prefix and unit names; formatting may render symbols afterward. If an invalid collision leaves a gap, the
selector uses the greatest available scale not exceeding the magnitude; the result may therefore remain at or above
`1000` rather than crossing into a falsely prefixed unit.

The family root must reduce to one named unit. Compound expressions, powers, and explicit numeric multipliers throw
`UnsupportedUnitCompactionException`; use a preferred-unit profile for a compound target. The caller must choose the
family because dimensional compatibility cannot decide among meter, foot, nautical-mile, or application-specific
policies. PHPStan returns an unbranded `Quantity` because the result depends on the runtime magnitude.

## Native Numeric Output

Exact `Rational` values can be extracted after conversion:

- `intValueIn()` follows `intdiv()`-style truncation toward zero and throws if the result does not fit a PHP integer.
- `exactIntValueIn()` additionally requires an integral result.
- `decimalValueIn()` returns a fixed number of decimal places using an explicit `RoundingMode`.
- `significantDecimalValueIn()` returns a requested number of significant decimal digits in plain or scientific
  `DecimalNotation`.
- `exactDecimalValueIn()` returns a minimal terminating decimal or throws for non-terminating rational values.
- `floatValueIn()` rounds to binary64 with ties to even. Its default `FloatRangePolicy::Strict` throws if the result
  overflows to infinity or a nonzero value underflows to zero.

PHP 8.2 and 8.3 receive the PHP 8.4 `RoundingMode` enum through `symfony/polyfill-php84`.

```php
<?php

use jbboehr\Yumemi\Units;
use jbboehr\Yumemi\Number\DecimalNotation;
use jbboehr\Yumemi\Number\FloatRangePolicy;
use jbboehr\Yumemi\Number\Rational;

$length = Units::default()->quantity(1, 'foot');

assert($length->intValueIn('meter') === 0);
assert($length->exactDecimalValueIn('meter') === '0.3048');
assert($length->decimalValueIn('meter', 2, \RoundingMode::HalfEven) === '0.30');
assert($length->significantDecimalValueIn('meter', 3, \RoundingMode::HalfEven) === '0.305');
assert($length->significantDecimalValueIn(
    'meter',
    3,
    \RoundingMode::HalfEven,
    DecimalNotation::Scientific,
) === '3.05e-1');
assert($length->floatValueIn('meter') === 0.3048);

$large = Units::default()->quantity(new Rational(gmp_pow(2, 1024)), 'meter');

assert($large->floatValueIn('meter', FloatRangePolicy::Ieee754) === INF);
```

Scale and precision answer different questions. A scale of `2` requests two places after the decimal point, while a
precision of `3` requests three significant digits wherever the decimal point falls. Significant output retains
fractional trailing zeros, so zero at precision `3` is `0.00` in plain notation and `0.00e+0` in scientific notation.
Plain integral text cannot distinguish whether trailing zeros are significant: use scientific notation when that
distinction must remain visible. Precision is limited to `1` through `10000`, and scientific exponents use Yumemi's
existing `-10000` through `10000` bound.

Pass `FloatRangePolicy::Ieee754` to `Rational::toFloat()`, `Quantity::floatValueIn()`, or
`PointQuantity::floatValueIn()` when binary64's signed infinity and signed zero are preferable to range exceptions.
Finite values use the same ties-to-even rounding under either policy. This option applies only when extracting a native
float from an exact value; `convertFloat()`, `unit_to()`, and `unit_factor()` remain strict native-float boundaries.

## Affine Conversion

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
assert($freezing->isCompatibleWith($units->point(32, 'fahrenheit')));
assert(!$freezing->isCompatibleWith($units->point(1, 'meter')));
assert($warmer->toString() === '10 * celsius');
assert($interval->toString() === '100 * delta_celsius');
assert($interval->valueIn('delta_fahrenheit')->toString() === '180');
```

A `PointQuantity` retains an exact `Rational` coordinate and a named scale. `to()`, `valueIn()`, comparisons, and native
numeric output apply full scale-and-offset conversion. `difference()` subtracts another compatible point and returns a
`Quantity` in the receiver's delta unit. `add()` and `sub()` translate the point by a compatible `Quantity` while
preserving the point's coordinate unit. `isCompatibleWith()` checks for the same `Units` context and compatible
coordinate dimensions without converting either value; a different context or dimension returns `false`. Operations that
combine points still throw for context or dimension incompatibility.

The catalog provides explicit multiplicative difference units such as `delta_celsius`, `delta_fahrenheit`, `Δ°C`, and
`Δ°F`. They participate in ordinary quantity and expression algebra, so `delta_celsius / second` is valid. Formatter
symbol mode renders the named aliases as `Δ°C` and `Δ°F` with Unicode typography.

No affine unit is silently rewritten inside algebra. `celsius / second` and `Units::quantity(1, 'celsius')` remain
invalid; use `delta_celsius / second` for a rate or `Units::point(1, 'celsius')` for a coordinate. `parse()`,
`parseUnit()`, `unit()`, `parseQuantity()`, `quantity()`, normalization, multiplication, division, powers, and prefixes
continue to reject the affine unit itself. Logarithmic definitions remain recognized but unevaluable.

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

Neither operation chooses a preferred human-scale unit. Use `to()` for one explicit target, `toPreferred()` for an
application profile, or `toCompact()` for a caller-selected engineering-prefix family.

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

The `Expr` values returned by these APIs expose `mul()`, `div()`, integer `pow()`, exact positive integer-degree
`root()`, `reduce()`, and structural `equals()` operations. `Expr::root()` reduces the expression's current symbolic
factors but does not normalize definitions; it throws `NonExactRootException` when the constant or any symbolic power
has no exact root. Those current factors depend on how the expression was obtained: `parse('kilometer * millimeter')`
resolves the prefixes while parsing and produces `meter^2`, whose square root is `meter`. By contrast, a quantity's
`unit()` preserves the names `kilometer * millimeter`, so its expression has no exact symbolic square root unless the
caller explicitly simplifies or normalizes it first. `Expr::root()` itself performs neither substitution.

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

When applying the same conversion repeatedly, calculate `unit_factor()` once outside the loop. The resulting branded
float can be reused with ordinary native multiplication; see
[Keep Unit Setup Outside Hot Loops](../recipes.md#keep-unit-setup-outside-hot-loops).

Use `conversionFactor()` when the exact `Rational` is required. Both factor APIs reject conversions involving an offset;
use `convert()`, `convertFloat()`, or `unit_to()` for affine conversion.

## Dimensions

`Units::dimension()`, `Quantity::dimension()`, and resolved expressions expose a `Dimension`. Its ordinary fast path is
the seven fixed SI powers in this order:

```text
length, mass, time, electric current, temperature, amount of substance, luminous intensity
```

Application registries may add sparse named axes through `UnitRegistryBuilder::baseUnit()`. Physical and nonphysical
axes use the same dimension algebra; for example, an application-defined `currency / time` dimension combines one
extension axis with the fixed SI time axis. `Dimension::CURRENCY` provides the conventional `currency` extension name
without adding a bundled unit or exchange-rate policy.

Dimensions support multiplication, division, integer powers, exact positive integer-degree roots, equality,
`isDimensionless()`, and the existing SI axis accessors. `Dimension::root()` requires every power to be divisible by the
degree. `powers()` retains its seven-element SI view. `namedPowers()` returns every nonzero SI and extension power,
`powerOf()` reads either kind by name, and `fromNamedPowers()` constructs a mixed dimension directly. Extension names
use lower snake case and format after SI axes in deterministic bytewise order.

All axes participate equally in compatibility. Dimensional equality still cannot distinguish semantically different
quantities with the same dimension, such as gray and sievert; that distinction would require a separate quantity-kind
model rather than another dimension subclass.

## Formatting

`Units::format()`, `Quantity::format()`, and `Quantity::formatUnit()` accept immutable `FormatOptions`. Options control:

- `UnitNameStyle::Preserve`, `Canonical`, or `Symbol` unit names;
- `Typography::Ascii` or `Unicode` operators and powers;
- `DimensionlessStyle::One`, `Word`, or `Empty` output;
- `DivisionStyle::Fraction` or `NegativePowers` layout.

Formatting changes presentation only. It does not normalize derived definitions, convert magnitudes, or choose preferred
or compact units. `Units::formatter()` returns a reusable formatter for repeated calls with the same options.

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

The supported JSON keys are:

- `Rational`: `numerator` and `denominator`, both decimal strings.
- `Quantity` and `PointQuantity`: `value`, containing the nested `Rational` object, and `unit`, containing the formatted
  unit string.
- `Dimension`: `length`, `mass`, `time`, `electricCurrent`, `temperature`, `amountOfSubstance`, and `luminousIntensity`,
  all integers, plus an `additionalPowers` object mapping names to integers when application-defined axes are present.
- `PrefixDescriptor`: string values for `matchedName`, `canonicalName`, `matchedAs`, and `definitionExpression`.
- `PrefixDecomposition`: `prefix` and `unit`, containing nested descriptor objects.
- `UnitDescriptor`: `matchedName`, `canonicalName`, `matchedAs`, `kind`, `definitionExpression`, `documentation`,
  `comment`, `aliases`, `symbols`, `explicitPlurals`, `generatedPlurals`, `semantics`, and `prefixDecomposition`. Names
  and enum values are strings; definition and prose fields are strings or `null`; aliases, symbols, and plurals are
  arrays of strings; and prefix decomposition is a nested object or `null`.

Descriptor JSON follows these documented properties, renders backed enums as strings, and nests dynamic prefix
decomposition. JSON object key order and insignificant whitespace are not compatibility guarantees.

Compact `__debugInfo()` output follows the same representation. Quantities add only a short context identity; dumping a
quantity does not recursively print its `Units` registry and catalog.

Native serialization is versioned and verifies normalized unit semantics and resolved dimensions when restoring a
quantity or point. This detects a custom base-unit name being reassigned to another extension axis. Values created
through `Units::default()` may use PHP's ordinary `serialize()` and `unserialize()` and always return to the shared
default context. A quantity from a custom registry must be restored through that context:

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

Serialization compatibility applies to values emitted by PHP's `serialize()` in tagged Yumemi releases. Direct calls to
`__serialize()` and the returned PHP array layout are implementation details. Newly serialized bytes may change while
previously released values remain readable.

Raw `unserialize()` rejects a custom-context quantity with an exception directing the caller to `Units::deserialize()`.
The scoped method restores its previous context in `finally`, including across nested calls, and forwards PHP's native
`unserialize()` options. Pass `allowed_classes` to restrict which classes a known graph may instantiate and `max_depth`
to bound nesting. The allow-list must include every serialized object class in the graph, including `Dimension` for new
quantity and point payloads; `allowed_classes: false` produces `__PHP_Incomplete_Class` objects and cannot restore a
quantity. Yumemi does not choose a default allow-list because `deserialize()` may return any caller-defined graph.
Serialized unit semantics are checked against the selected registry, so a changed or incorrect registry is rejected
rather than silently reinterpreting the value.

One serialized graph may contain default values and values from one custom context. Graphs containing values from
several distinct custom contexts require a future registry-identifier resolver. Never pass untrusted data to PHP
deserialization; `allowed_classes` and `max_depth` reduce exposure but do not make arbitrary payloads safe. Serialize
value objects directly: casting one to an array bypasses its controlled serialization representation.
