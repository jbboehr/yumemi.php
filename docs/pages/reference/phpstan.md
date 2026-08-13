# PHPStan Reference

<figure class="logion" data-logion="OSD 57:34">
<div class="logion-text">
<blockquote>
<p>Before judgment, suspend the bronze plummet above the council mosaic, and let neither advocate nor prince touch its cord. If it hangeth toward the floor, hear the cause; but if its weight rise toward the painted heavens, dismiss the court and uncover the dais, for authority hath seated itself where only witness was appointed.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 57:34</cite></p>
</div>
<img src="../images/logia/OSD-57_34.webp" alt="A bronze plummet suspended above a wet council mosaic before an empty dais" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Yumemi's PHPStan extension attaches units to ordinary PHP `int` and `float` values and propagates them through supported
operations. It can also brand a `numeric-string` at a string-oriented boundary. The runtime values remain native
scalars; the additional unit identity exists only during static analysis.

The extension uses the same parser, catalog, reduction, normalization, and conversion semantics as the
[runtime API](runtime.md). See the [unit syntax reference](unit-syntax.md) for accepted expressions and name resolution.

Most applications primarily need branded native types, operator inference, and boundary helpers. `Quantity` and
`PointQuantity` type inference becomes relevant when exact runtime objects cross analyzed code; registry configuration
and optional annotation integration are advanced topics for projects extending the catalog or integrating third-party
libraries.

| I need to...                            | Start with                                          |
| --------------------------------------- | --------------------------------------------------- |
| Add a unit to an existing native number | [`unit()` and branded types](#branded-native-types) |
| Brand numeric text from a trusted API   | [Numeric Strings](#numeric-strings)                 |
| Infer units through PHP operators       | [Native Operators](#native-operators)               |
| Convert a native magnitude              | [Boundary Helpers](#boundary-helpers)               |
| Track an exact runtime quantity         | [Quantity Types](#quantity-types)                   |
| Track an exact coordinate point         | [Quantity Types](#quantity-types)                   |
| Add project-specific units              | [Registry Configuration](#registry-configuration)   |
| Suppress or baseline an error           | [Diagnostics](#diagnostics)                         |

> **Current boundaries:** Genuinely dynamic unit strings cannot receive a precise static unit; native helpers diagnose
> them by default while runtime object APIs may parse them dynamically. Casts other than explicit integer/float casts
> and unsupported built-ins may erase a brand, and dimensional analysis cannot distinguish concepts with identical
> physical dimensions. See [Limitations](#limitations) for the complete list.

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

### Integer Constants And Ranges

Integer precision composes with a unit brand through PHPStan's ordinary intersection syntax:

```php
<?php

use function jbboehr\Yumemi\unit;

/** @param unit_int<'second'>&int<0, max> $delay */
function scheduleBoundedRetry(int $delay): void {}

scheduleBoundedRetry(unit(30, 'second'));
```

`unit(30, 'second')` is inferred as the branded constant `30&unit_int<'second'>`. A native `int<0, 100>` passed to
`unit($value, 'second')` becomes `unit_int<'second'>&int<0, 100>`. Standard refinements such as `positive-int` and
`non-negative-int` may be intersected with `unit_int` in the same way. There is no separate `unit_const_int` syntax:
literal and range precision remain ordinary PHPStan types, while `unit_int` contributes only the unit identity.

Bounded targets enforce both parts. A bare `int<0, 100>` lacks the required unit, a branded value outside the range
violates the bound, and a value with another unit violates the brand. An unbounded `unit_int<'second'>` accepts bounded
and constant seconds.

Known float values use the same idea. `unit(1.5, 'meter')` is inferred as `1.5&unit_float<'meter'>`: PHPStan's ordinary
constant-float type supplies `1.5`, while `unit_float` supplies the unit. There is no separate `unit_const_float`
syntax, and the intersection shown in a diagnostic is not a runtime wrapper.

Here, “known” means that PHPStan knows the actual PHP binary floating-point value. It does not make `1.5`, a conversion
ratio, or a calculated result into an exact rational quantity. Use `Rational` or `Quantity` when the program must retain
exact decimal or fractional semantics at runtime.

### Numeric Strings

`unit_numeric_string<'unit'>` brands a PHPStan `numeric-string` whose magnitude has a statically known unit. It is
useful when a trusted configuration or framework API represents a number as text while its contract defines the unit. At
runtime the value remains an ordinary string: Yumemi does not attach metadata, parse a combined value such as
`"30 second"`, or validate where the magnitude came from.

A bare `numeric-string` does not satisfy a unit-bearing parameter, and strings branded with different units are not
interchangeable. An explicit integer or float cast preserves the brand on the resulting native number. Convert that
number through the normal unit boundary when a different unit is required. Implicit arithmetic, weak parameter coercion,
and other string-to-number conversions do not preserve the brand; cast first when entering numerical code:

```php
<?php

interface RetryConfiguration
{
    /** @return unit_numeric_string<'second'> */
    public function retryDelay(): string;
}

/** @param unit_int<'second'> $delay */
function scheduleConfiguredRetry(int $delay): void {}

function applyRetryConfiguration(RetryConfiguration $configuration): void
{
    scheduleConfiguredRetry((int) $configuration->retryDelay());
}
```

Use this type only when the external contract already guarantees both numeric syntax and the unit. It is a static
declaration, not a runtime validation helper; use ordinary validation before branding data whose contents are not yet
trusted.

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
`unit_float`; operations involving a float-like magnitude also produce a float brand. Yumemi preserves known integer
constants and signed ranges through addition, subtraction, multiplication, unary signs, and nonnegative powers. It also
preserves known float values through supported arithmetic when every required operand value is known. Exact integer
endpoint arithmetic determines the result kind:

| Mathematical result relative to PHP's integer range | Inferred type                                           |
| --------------------------------------------------- | ------------------------------------------------------- |
| Entirely inside                                     | Branded constant or bounded `unit_int`                  |
| Entirely outside                                    | `unit_float`                                            |
| Partly inside                                       | Benevolent union of bounded `unit_int` and `unit_float` |

For a mixed result, the integer branch is clipped to values PHP can actually retain as integers. Unary negation
therefore isolates the `PHP_INT_MIN` case rather than treating every integer as equally likely to overflow. Modulo
preserves a branded constant when both operands are known and the divisor is nonzero; other modulo results remain an
unbounded `unit_int`. Finite operand unions are evaluated arm by arm, and Yumemi rejects the whole operation if any
possible pairing is invalid.

Applications that intentionally prefer PHPStan's integer-preserving approximation for potentially overflowing arithmetic
can disable float promotion:

```neon
parameters:
    yumemi:
        integerOverflowToFloat: false
```

This setting changes static inference only; it cannot alter PHP's runtime overflow behavior. Proven-safe constants and
ranges remain precise; a potentially overflowing result widens to an unbounded `unit_int` because PHPStan cannot
represent an integer endpoint outside PHP's platform range.

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
without converting either operand, so dimensionally compatible but differently scaled units remain invalid. Strict
identity may still test a nullable or other nonnumeric sentinel arm, as in `$duration !== null`; a bare numeric arm
remains invalid because it can participate in the magnitude comparison.

Native exponentiation requires a statically known integer exponent. For exact runtime quantities,
`Quantity::root($degree)` infers the rooted unit when the degree is one statically known positive integer and every
symbolic unit power is divisible by it. PHPStan cannot prove that the runtime rational magnitude has an exact root, so a
statically valid call may still throw `NonExactRootException`. A dynamic degree falls back to the nongeneric `Quantity`
return type. Native `sqrt()` support is described below. Rational exponents, approximate real powers, and other
unit-transforming native functions are not part of the current model.

### Casts And Scalar Functions

Explicit integer and float casts preserve the unit while changing the native numeric kind. Yumemi also tracks brands
through a small set of built-in scalar functions. Most retain the input unit; `sqrt()` transforms it when the symbolic
square root is exact:

| Expression                   | Inferred result                                          |
| ---------------------------- | -------------------------------------------------------- |
| `(float) $unitInteger`       | Same unit, retaining a known constant                    |
| `(int) $unitFloat`           | Same unit, retaining a known constant                    |
| `(int) $unitNumericString`   | `unit_int<'same unit'>`                                  |
| `(float) $unitNumericString` | `unit_float<'same unit'>`                                |
| `abs($unitFloat)`            | Same unit, retaining a known float constant              |
| `abs($unitInteger)`          | Branded integer bounds, with possible overflow promotion |
| `ceil()` and `floor()`       | Same unit, retaining a known numeric constant            |
| `round()`                    | `unit_float<'same unit'>`                                |
| `min()` and `max()`          | Common brand, retaining known extrema or integer bounds  |
| `sqrt($unitNumber)`          | Rooted unit, retaining a finite nonnegative constant     |

For example, these transformations remain ordinary native PHP operations at runtime:

```php
<?php

/**
 * @param unit_float<'meter'> $offset
 * @return unit_float<'meter'>
 */
function absolutePlatformOffset(float $offset): float
{
    return abs($offset);
}

/** @var unit_int<'meter'> $measuredHeight */
$measuredHeight = 12;
/** @var unit_float<'meter'> $displayHeight */
$displayHeight = round((float) $measuredHeight, 1);

/** @var unit_float<'meter^2'> $platformArea */
$platformArea = 144.0;
$platformWidth = sqrt($platformArea);

assert($displayHeight === 12.0);
assert($platformWidth === 12.0);
```

Crossing a known integer constant to a float retains both its value and unit. Integer ranges still generalize because
PHPStan has no corresponding public float-range type. `abs()`, `ceil()`, and `floor()` retain a constant value when the
input and result are known. `sqrt()` does so for finite nonnegative inputs. `round()` currently preserves the unit but
generalizes the magnitude because its optional precision and rounding-mode arguments are not part of this resolver.

`min()` and `max()` preserve a unit when every value they can return is branded with one definitionally equivalent unit.
This works with direct arguments, arrays, and unpacked arrays. When every candidate is required and is a known finite
constant, the selected integer or float value is retained. Known integer ranges are narrowed when every candidate is
required; a general array keeps its declared branded range because its runtime members are not known individually. If a
possible nonempty input contains an unbranded value or a different unit, Yumemi does not infer one brand for the result
and reports `yumemi.invalidUnitSelection`. A possible empty-array input does not contribute a result because native
`min()` and `max()` throw on that path.

Unlike those preserving operations, `sqrt()` transforms the unit. It infers `unit_float<'meter'>` from either an integer
or float branded as `meter^2`, because native `sqrt()` always returns a `float`. Every symbolic unit power must be
divisible by two. A non-rootable brand such as `meter` produces `yumemi.invalidUnitRoot` instead of silently losing its
unit. A known negative or non-finite magnitude keeps the rooted unit but generalizes to `unit_float` rather than
creating a branded `NAN` or infinite constant.

The check uses the symbolic expression as written; it does not substitute catalog definitions before taking the root.
For example, `kilometer * millimeter` is dimensionally an area but lacks an exact symbolic square root. Express the
native brand with square powers, or use `Quantity::simplify()->root(2)` when runtime definition substitution and exact
magnitude checking are required.

For a union containing only branded numeric alternatives, Yumemi roots every alternative. Any non-rootable branded arm
produces the diagnostic. If an otherwise valid union also contains an unbranded numeric arm, PHPStan keeps its ordinary
native `sqrt()` result because one precise unit cannot describe every runtime path.

For branded integers, `abs()` retains exact constants and computes the hull of known ranges. The `PHP_INT_MIN` case can
produce a float at runtime, so unbounded or partially exposed ranges follow the same `integerOverflowToFloat` policy as
native arithmetic. With promotion enabled, their result may be a benevolent union of a nonnegative branded integer and a
branded float; disabling promotion widens the result to an unbounded `unit_int`.

## Boundary Helpers

Yumemi provides three functions for introducing and converting native unit values:

- `unit($value, $unit)` validates a multiplicative unit and returns the unchanged native magnitude branded as `unit_int`
  or `unit_float`, retaining a known scalar value.
- `unit_factor($from, $to)` returns a native conversion ratio branded as `to / from`. Multiplying it by a source value
  cancels the source unit and produces the target unit; known factors remain float constants during analysis.
- `unit_to($value, $from, $to)` performs the conversion directly and returns a float. Multiplicative targets retain a
  `unit_float` brand, and a statically known input and conversion retain the resulting float value.

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
delta temperatures. A known affine conversion may still produce an unbranded PHPStan float constant.

Constant helper results describe the PHP float that the corresponding runtime call will return. They do not promise
decimal exactness: for example, a converted binary float may be displayed as `0.9144000000000001` rather than `0.9144`.
Use the exact runtime object APIs when that distinction matters.

If a branded value is passed to `unit_to()`, its brand must match the declared source unit. `unit()` and known helper
arguments are also validated against the configured catalog. Unknown expressions and incompatible conversions fail
analysis before the result type is used.

### Constant Unit Expressions

By default, every unit argument to `unit()`, `unit_factor()`, and `unit_to()` must resolve during analysis to one exact
constant string or a finite set of exact alternatives. Class constants and expressions that PHPStan constant-folds are
accepted. A broad `string` or `literal-string` is not enough because Yumemi cannot recover the expression text to parse
and validate it.

Finite alternatives are accepted only when every valid path gives the operation one semantic result. Aliases such as
`'meter'|'metre'` are therefore valid for `unit()`. Several source units are valid for `unit_to()` when one target is
fixed, because the target determines the returned brand. Alternatives such as `'meter'|'foot'` passed to `unit()`, or as
the target of `unit_to()`, are ambiguous even though PHP can represent a union of their brands.

```php
<?php

use function jbboehr\Yumemi\unit;

/** @return unit_float<'meter'> */
function brandKnownDistanceAlias(float $value, bool $useAlias): float
{
    return unit($value, $useAlias ? 'meter' : 'metre');
}
```

```text
function brandDynamicDistance(float $value, string $unitExpression): float
{
    // yumemi.dynamicUnitExpression
    return unit($value, $unitExpression);
}

function brandAmbiguousDistance(float $value, bool $useMetric): float
{
    $unitExpression = $useMetric ? 'meter' : 'foot';

    // yumemi.ambiguousUnitExpression
    return unit($value, $unitExpression);
}
```

The first rejected call does not expose its expression text. The second exposes two valid units, but they do not
normalize to one semantic result.

The two diagnostics preserve the functions' ordinary native fallback types, so an intentional dynamic boundary can use
an identifier-specific local suppression:

```php
<?php

use function jbboehr\Yumemi\unit_to;

function convertUncheckedUnitExpression(float $value, string $sourceUnit, string $targetUnit): float
{
    // @phpstan-ignore yumemi.dynamicUnitExpression
    return unit_to($value, $sourceUnit, $targetUnit);
}
```

Projects whose native-helper calls fundamentally depend on runtime strings may disable only the dynamic-expression
diagnostic:

```neon
parameters:
    yumemi:
        requireConstantNativeUnitExpressions: false
```

Ambiguous finite alternatives remain errors because no one output unit applies; suppress
`yumemi.ambiguousUnitExpression` locally when that loss of precision is deliberate. Runtime parsing APIs such as
`Units::parse()` and the `Quantity` methods remain the intentional dynamic path and are not affected by this option.
Their constant and finite-union inference remains described below.

Statically known expressions are also subject to the shared [parser resource limits](unit-syntax.md#resource-limits). An
oversized constant helper argument reports `yumemi.invalidUnitCall`. If the native helper executes, it throws its usual
`InvalidArgumentException` and retains `Parser\ExpressionLimitExceededException` as the previous exception. Suppressing
the PHPStan diagnostic does not relax the runtime budget.

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

- arithmetic through `abs()`, `add()`, `sub()`, `addWithSameUnit()`, `subWithSameUnit()`, `mul()`, `div()`, `neg()`,
  `pow()`, and exact `root()`;
- conversion through `to()`, `toPreferred()`, and `valueIn()`;
- native extraction through `intValueIn()`, `exactIntValueIn()`, `decimalValueIn()`, `significantDecimalValueIn()`,
  `exactDecimalValueIn()`, and `floatValueIn()`;
- unit transformation through `normalize()` and `simplify()`;
- comparisons through `compareTo()`, `equals()`, `lessThan()`, `lessThanOrEqualTo()`, `greaterThan()`, and
  `greaterThanOrEqualTo()`.

`Quantity::isZero()`, `Quantity::isCompatibleWith()`, and `PointQuantity::isCompatibleWith()` return ordinary native
`bool` values from their declared signatures and require no unit-specific return-type inference. A compatibility check
remains valid when PHPStan knows the dimensions differ: its result is `false`, not a diagnostic.

Known invalid arithmetic, construction, conversion, and comparison calls produce standalone diagnostics even when the
method result is unused. A branded magnitude supplied to `Units::quantity()` must match the unit being assigned:
`quantity()` labels an existing magnitude and does not implicitly convert it.

Integer and float extraction methods return a native brand when their target unit is known. For example,
`floatValueIn('foot')` returns `unit_float<'international_foot'>`, bridging an exact quantity back to a statically
branded native value. Decimal extraction returns a string while retaining static validation of the target unit.

An explicit target can also brand conversion and extraction results from an unbranded `Quantity`. PHPStan cannot prove
the unknown source dimension in that case, but it can represent the requested result. A genuinely dynamic target falls
back to an unbranded return type.

`toPreferred()` also returns an unbranded `Quantity` because the selected target depends on the runtime contents of a
`PreferredUnitProfile`. Use `to('target')` when subsequent static analysis needs one exact quantity brand.

`PointQuantity<'celsius'>` carries both the coordinate origin and its difference scale. Coordinate aliases are
definitionally equivalent, but different scales such as Celsius, Fahrenheit, and Kelvin remain distinct generic types
even though their points can be converted and compared. PHPStan models the affine operation rules:

- `PointQuantity::add()` and `sub()` accept a dimensionally compatible `Quantity` and preserve the point type;
- `difference()` accepts a compatible point and returns `Quantity<'delta-unit'>` in the receiver's scale;
- `to()` returns a point branded with the target coordinate scale;
- point comparisons and numeric extraction validate constant targets and preserve their native return types.

`PointQuantity::isCompatibleWith()` remains an ordinary `bool` predicate. Unlike an operation that combines points, it
is valid to call with known incompatible point dimensions and returns `false` at runtime.

Direct PHPDoc may use forms such as `PointQuantity<'celsius'>`. Dynamic coordinate strings fall back to unbranded
`PointQuantity`, following the same policy as ordinary quantities.

## Registry Configuration

PHPStan uses the default UDUNITS2 catalog unless `parameters.yumemi.registryFactory` names an autoloadable class
implementing `UnitRegistryFactory`. Its static `create()` method returns the complete immutable registry used by every
Yumemi extension path:

<!-- akashi: separate-process -->

```php
<?php

namespace App\PHPStan;

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\PHPStan\UnitRegistryFactory;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;

final class DocumentationRegistryFactory implements UnitRegistryFactory
{
    public static function create(): UnitRegistry
    {
        return UnitRegistryBuilder::default()
            ->baseUnit('USD', Dimension::CURRENCY)
            ->define('EUR = 100 / 107 * USD')
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
catalog. `baseUnit()` introduces a named primitive dimension; subsequent `define()` calls derive related units through
ordinary expressions. Unit definitions and primitive-dimension metadata both contribute to PHPStan's result-cache
fingerprint.

The configured registry controls static analysis only. Applications using custom units in both layers should construct
their runtime `Units` context from the same factory. Instance APIs use that context directly; applications using
`unit()`, `unit_factor()`, or `unit_to()` should install it with `Units::setDefault()` and restore the previous context
in `finally`. PHPStan assumes one authoritative registry for an analysis run and does not track a separate catalog
identity on each value. See [Contexts And Construction](runtime.md#contexts-and-construction) for runtime installation
and [Custom Registries](catalog.md#custom-registries) for builder and overlay semantics.

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
`unit_int<'...'>` must erase to `int`, every `unit_float<'...'>` to `float`, every `unit_numeric_string<'...'>` to
`numeric-string`, every `Quantity<'...'>` to `Quantity`, and every `PointQuantity<'...'>` to `PointQuantity`, including
within nullable, union, intersection, and generic types. For example, `unit_int<'second'>&int<0, max>` erases to
`int<0, max>`, while `3&unit_int<'meter'>` erases to `3`. Parameter references and variadic markers must also match.
Union and intersection order and nullable spelling do not matter. `@phpstan-*` takes priority over the ordinary tag. An
already promoted `@phpstan-*` tag with exactly the same unit-bearing structure is accepted idempotently. Any other
mismatch leaves the fallback unchanged and reports a diagnostic.

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

//! expects unit_int<'meter'>, 3&unit_int<'international_foot'> given
storeWarehouseLength(unit(3, 'foot'));
```

Without tag promotion, both calls are checked against the ordinary `int` fallback and are valid.

The integration is opt-in because it replaces internal PHPStan parser services for analyzed source and stubs. It may
conflict with another extension replacing the same services and remains an upgrade risk. Application code should
normally use direct Yumemi types; integrations for third-party libraries can use ordinary PHPStan stubs or the
separately packaged integrations described below.

### Third-Party Integrations

Curated stubs for third-party packages live in the separately versioned
[Yumemi Apocrypha](https://github.com/jbboehr/yumemi-apocrypha.php) package. Apocrypha uses the generic `@yumemi-*`
promotion mechanism above while owning package selection, supported-version policy, upstream fixtures, and integration
documentation. Keeping those concerns outside core avoids adding framework scope or dependencies to Yumemi itself.

## Diagnostics

Yumemi emits stable rule identifiers so errors can be suppressed or included in a PHPStan baseline at the appropriate
scope:

| Identifier                             | Reported condition                                                                                           |
| -------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `yumemi.dynamicUnitExpression`         | A native helper argument does not reveal its complete unit expression during analysis                        |
| `yumemi.ambiguousUnitExpression`       | Native helper alternatives produce more than one semantic result unit                                        |
| `yumemi.invalidUnitCall`               | An invalid constant `unit()`, `unit_factor()`, or `unit_to()` call                                           |
| `yumemi.invalidUnitComparison`         | A native equality, identity, ordering, or spaceship comparison whose units are not definitionally equivalent |
| `yumemi.invalidUnitRoot`               | Native `sqrt()` received a branded unit without an exact symbolic square root                                |
| `yumemi.invalidUnitSelection`          | Native `min()` or `max()` can return an unbranded or differently branded candidate                           |
| `yumemi.invalidQuantityConstruction`   | Invalid `Units::quantity()`, `parseQuantity()`, `deltaQuantity()`, or `point()` construction                 |
| `yumemi.invalidQuantityArithmetic`     | Invalid quantity arithmetic operands, powers, or exact-root degrees and unit expressions                     |
| `yumemi.invalidQuantityConversion`     | An invalid or incompatible `Quantity` conversion or native-extraction target                                 |
| `yumemi.invalidQuantityComparison`     | A `Quantity` comparison whose statically known units are incompatible                                        |
| `yumemi.invalidPointQuantityOperation` | An invalid point translation, difference, conversion, extraction, or comparison                              |
| `yumemi.docTagSyntax`                  | Invalid `@yumemi-param`, `@yumemi-return`, or `@yumemi-var` syntax                                           |
| `yumemi.docTagDuplicate`               | More than one Yumemi tag targets the same fallback position                                                  |
| `yumemi.docTagUnsupported`             | A Yumemi tag appears on a declaration that does not support that tag kind                                    |
| `yumemi.docTagParameter`               | A parameter name is unknown or an unnamed `@yumemi-var` fallback is ambiguous                                |
| `yumemi.docTagType`                    | A Yumemi tag contains an invalid unit-bearing type                                                           |
| `yumemi.docTagTransform`               | Erasing the units does not reproduce the fallback PHPDoc structure                                           |
| `binaryOp.invalid`                     | Invalid native unit arithmetic; this is PHPStan's standard binary-operation identifier, not Yumemi's         |

Call and operation diagnostics apply even when an invalid call's result is unused. Syntax diagnostics preserve the
runtime parser's bounded caret excerpt while PHPStan anchors the error to the containing PHP or PHPDoc line.

When a Yumemi-owned diagnostic still has one exact constant unit argument, it uses the caller's reduced symbolic
spelling, such as `metres` rather than `meter`. Inferred types and diagnostics formed after unions, arithmetic, or other
semantic joins remain canonical because no single source spelling necessarily survives those operations.

Use the identifier to choose the first corrective step:

- For `binaryOp.invalid` or `yumemi.invalidUnitComparison`, remember that native PHP does not convert either operand.
  Convert explicitly with `unit_to()` or `unit_factor()`, or use `Quantity` when the operation should convert compatible
  units. See [Definitional Equivalence And Compatibility](#definitional-equivalence-and-compatibility).
- For `yumemi.dynamicUnitExpression` or `yumemi.ambiguousUnitExpression`, provide one statically recoverable semantic
  result, use an identifier-specific local suppression for an intentional dynamic boundary, or choose an explicit
  runtime object API. See [Constant Unit Expressions](#constant-unit-expressions).
- For `yumemi.invalidUnitCall`, `yumemi.invalidQuantityConstruction`, or `yumemi.invalidQuantityConversion`, check the
  constant unit spelling, the [configured registry](#registry-configuration), dimensional compatibility, and whether an
  affine coordinate was used where multiplicative algebra requires a `delta_*` unit.
- For `yumemi.invalidUnitRoot`, express the native brand with unit powers divisible by two. Native `sqrt()` does not
  substitute catalog definitions; use `Quantity::simplify()->root(2)` when that runtime transformation is intended.
- For `yumemi.invalidUnitSelection`, ensure every value that `min()` or `max()` can return has one definitionally
  equivalent unit. Convert compatible but differently branded values before selecting an extreme.
- For quantity arithmetic, comparison, or point diagnostics, verify the statically known dimensions and distinguish a
  `PointQuantity` coordinate from a multiplicative difference. Static generic types do not establish runtime context
  identity; objects combined at runtime must also belong to the same `Units` context.
- For `yumemi.docTag*`, confirm that [the optional integration](#extension-optional-annotations) is enabled and that
  erasing every Yumemi unit type exactly reproduces the ordinary fallback PHPDoc structure.

## Limitations

Important limits of the current static model are:

- Native `unit()`, `unit_factor()`, and `unit_to()` calls require statically recoverable unit expressions by default.
  Dynamic object parsing and conversion remain supported, but cannot retain a specific generic unit type.
- PHPStan supports one configured registry and does not track runtime registry identity per value.
- Explicit integer/float casts preserve native numeric brands and move a `unit_numeric_string` brand onto the resulting
  number. Implicit arithmetic and weak numeric coercion do not preserve a numeric-string brand; comparisons still
  require definitionally equivalent brands. `abs()`, `ceil()`, `floor()`, `round()`, `min()`, and `max()` preserve
  numeric unit brands when their operation has one sound result unit. `sqrt()` transforms brands with exact symbolic
  square roots. Other casts and unsupported PHP built-ins can erase brands.
- Native `+` and `-` cannot convert dimensionally compatible magnitudes; use an explicit conversion or `Quantity`.
- Native affine targets remain unbranded because native scalars do not retain point-versus-difference identity. Use
  `PointQuantity<'...'>` when that identity must remain statically visible.
- `unit_to()` and `unit_factor()` validate the Cartesian product of independent source and target alternatives and
  reject the call if any pairing is invalid. Valid alternatives must also collapse to one semantic result unit.
- Unit exponentiation supports constant integers only.
- PHPStan has no corresponding native float-range syntax, so branded floats can retain known constants but not
  continuous bounds.
- Dimensional analysis cannot distinguish different physical meanings with the same dimension, such as gray and sievert.

Add targeted PHPStan integrations for demonstrated application workflows rather than assuming every cast, built-in, or
third-party API preserves a unit brand.
