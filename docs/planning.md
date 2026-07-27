# Iudex Mensurarum Mysticarum『夢見』〜Yumemi〜 Planning

Naming:

- Full name: `Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜`
- Short name: **Yumemi** (夢見, "dreaming")
- Package/repo: `jbboehr/yumemi` — <https://github.com/jbboehr/yumemi.php>
- PHP namespace: `jbboehr\Yumemi\`
- Meaning: roughly "Judge of the Mystical Measures"

The name is intentionally overdramatic, Latin, and chuuni-adjacent. The full Latin title _Iudex Mensurarum Mysticarum_
lives in the README and the per-file header; the short name **Yumemi** — folded from the initials Iu·Me·My of _Iudex
Mensurarum Mysticarum_ (IuMeMy → Yumemi) and read as 夢見, "dreaming" — keeps day-to-day package, namespace, and
conversation use practical. The Latin was chosen partly because its cadence echoes `Index Librorum Prohibitorum`, and
`Mysticarum` (an adjective agreeing with `mensurarum`) binds as one phrase — "of the mystical measures" — rather than
stacking two genitives the way the earlier `Mysteriorum` did.

## Project Goal

Yumemi should be both:

- A runtime unit expression, dimensional compatibility, and conversion library.
- A PHPStan extension for static dimensional analysis.

The runtime library is the source of truth. PHPStan should be an adapter over the same parser, registry, normalizer, and
conversion semantics rather than a separate implementation.

Important principle:

> One expression model. One registry. One normalization engine.

## Old Code Assessment

The old work was split across two repositories:

- `units.php`: stronger runtime/parser/analyzer foundation
- `phpstan-units`: early PHPStan prototype

Useful pieces from `units.php`:

- Bison parser for unit expressions
- AST model for parsed unit syntax
- AST-to-expression conversion approach
- Unit registry backed by generated UDUNITS2 data
- Prefix and plural resolution
- Derived-unit normalization
- Expression reduction and cancellation
- Conversion-factor compatibility checks

Less useful pieces from `phpstan-units`:

- Hardcoded unit classes and conversion classes
- Duplicated expression model
- Stub PHPStan operator extension

Conclusion: `units.php` is the reference implementation. `phpstan-units` is useful only as a sketch of PHPStan extension
registration.

## Current Status

Already implemented:

- Composer/Nix/project scaffold
- Exact `Rational` number type
- Core expression model:
  - `Expr`
  - `Expr\Constant`
  - `Expr\Unit`
  - `Expr\Term`
  - `Expr\Compound`
- Canonical expression reduction:
  - flatten compounds
  - combine constants
  - combine unit powers
  - cancel inverse units
  - deterministic unit ordering
- Derived-unit normalization
- Generated UDUNITS2 catalog data in `data/udunits2.php`
- Catalog import/export tooling:
  - `Udunits2CatalogImporter`
  - `PhpCatalogExporter`
  - `GenerateUdunits2CatalogCommand`
  - `bin/generate-udunits2-catalog`
- Runtime registry layer:
  - `UnitRegistry`
  - `Udunits2UnitRegistry`
  - generated aliases
  - generated prefix data
  - resolver-side prefix handling
  - simple plural stripping
- Runtime conversion-factor resolver
- Public `Units` facade with `Expr|string` ergonomics:
  - `unit()`
  - `parse()`
  - `normalize()`
  - `compatible()`
  - `conversionFactor()`
  - `convert()`
  - `quantity()`
- Ported generated parser from `units.php`:
  - grammar
  - lexer
  - AST nodes
  - generated `Parser.php`
  - Composer/Makefile generation wiring
- AST converter for supported runtime syntax:
  - identifiers
  - integer constants
  - decimal/scientific constants
  - multiplication
  - division
  - integer powers
- Explicit rejection for parsed-but-unsupported syntax:
  - addition
  - subtraction
  - `@`
- Runtime `Quantity` value object:
  - explicit `Units` context
  - exact `Rational` value storage
  - symbolic unit storage for display/chosen syntax
  - resolved unit storage for catalog-aware conversion
  - `to()` and `valueIn()` explicit conversion
  - `add()` and `sub()` with exact conversion of compatible right operands into the left unit
  - `addWithSameUnit()` and `subWithSameUnit()` for normalized-equivalent units without conversion
  - `mul()` and `div()` for unit arithmetic
  - `normalize()` for unit-definition substitution without changing stored value
  - `simplify()` for unit-definition substitution with scale folded into the stored value
  - context checks that reject arithmetic between quantities from different `Units` instances

Current verification:

- PHPUnit passes
- PHPStan passes
- PHPCS passes
- Composer validation passes

Known test-suite issue:

- PHPUnit reports one deprecation warning, likely from config/tooling. It has not affected test execution.

## Runtime API Direction

The runtime API is intentionally small, but it now has both expression-level and value-level entry points.

```php
$units = Units::default();

$units->normalize('kilometer'); // 1000 * meter
$units->compatible('meter / second', 'kilometer / minute'); // true
$units->conversionFactor('meter / second', 'kilometer / minute'); // 3/50
$units->convert(1, 'kilometer', 'meter'); // 1000
```

The main user-facing value API is `Quantity`:

```php
$distance = $units->quantity(12, 'foot');
$meters = $distance->to('meter');

$speed = $units->quantity(1, 'meter / second');
$pace = $speed->to('kilometer / minute');
```

Current `Quantity` methods:

- `value(): Rational`
- `unit(): Expr`
- `expr(): Expr`
- `to(Expr|string $unit): self`
- `valueIn(Expr|string $unit): Rational`
- `intValueIn(Expr|string $unit): int`
- `exactIntValueIn(Expr|string $unit): int`
- `add(self $other): self`
- `sub(self $other): self`
- `addWithSameUnit(self $other): self`
- `subWithSameUnit(self $other): self`
- `mul(self|int|Rational $other): self`
- `div(self|int|Rational $other): self`
- `normalize(): self`
- `simplify(): self`
- `valueToString(): string`
- `unitToString(): string`
- `toString(): string`

Important runtime rule:

> Quantity addition and subtraction convert the right operand into the left operand's unit and preserve the left unit.
> The explicit `*WithSameUnit()` variants reject operands that would require conversion.

For example, `(meter / second) * second` reduces to `meter`, but `centimeter / second / foot` stays in chosen symbolic
units until the caller explicitly asks for conversion or simplification.

There are currently two catalog-aware operations with different intent:

- `normalize()`: substitutes unit definitions without changing the stored quantity value.
- `simplify()`: substitutes unit definitions and folds any unit scale factor into the stored quantity value.

## Parser And Syntax Direction

The parser is intentionally broader than the semantic runtime layer. This is acceptable because the long-term goal is
UDUNITS2 compatibility, but syntax must not imply semantic support.

Supported by parser and converter now:

- `meter`
- `meter * second`
- `meter second`
- `meter / second`
- `meter^2`
- `second^-2`
- `(meter / second)^2`
- decimal constants such as `1.25 meter`
- scientific notation accepted by the lexer

Parsed but unsupported by converter:

- `meter + second`
- `meter - second`
- `meter @ 2`

This should remain explicit until offsets/affine units are designed.

The parser can read more UDUNITS2 syntax than the runtime chooses to support semantically. The importer currently skips
logarithmic definitions and the runtime rejects affine syntax such as Celsius offsets.

## Design Choices

Prefer unit strings over one PHP class per unit.

Good user-facing syntax:

```php
/** @var Quantity<'meter'> */
$distance;

/** @var Quantity<'meter / second'> */
$speed;
```

Avoid making users define or reference classes like:

```php
/** @var intWithUnit<Meter> */
$distance;
```

The string form can represent compound units without requiring a PHP class for every base, derived, or compound unit.

The PHPStan extension should eventually make `Quantity<'meter / second'>` meaningful statically, but runtime application
code should continue to use ordinary `Units` and `Quantity` objects.

## Compatibility And Conversion

Dimensional compatibility and conversion are related but distinct:

- `meter` and `foot` are dimensionally compatible.
- Converting between them requires a scale factor.
- `meter` and `second` are incompatible.

Runtime `Quantity::add()` and `Quantity::sub()` use dimension compatibility because the object can perform the required
exact conversion. `addWithSameUnit()` and `subWithSameUnit()` expose the exact-unit policy. Native PHPStan unit types
remain exact-unit-only for `+` / `-`: ordinary PHP numbers cannot perform the magnitude conversion required by an
operation such as `meter + foot`, so accepting merely compatible dimensions would be unsound.

## Formula Interpolation Idea

There may be value in a small format-string-like API for formulas:

```php
$distance = $units->formula('{} meter / second * {} second', 3, 2);
```

Or with named placeholders:

```php
$distance = $units->formula('{velocity} * {time}', [
    'velocity' => $units->quantity(3, 'meter / second'),
    'time' => $units->quantity(2, 'second'),
]);
```

If added, this should be typed interpolation, not string concatenation. Placeholder values should become expression
nodes:

- `int`, `Rational`, or numeric strings become scalar constants
- `Quantity` values become quantity expressions
- `Expr` values become expression fragments
- raw unit strings should either be rejected or require an explicit wrapper

This is a convenience API, not the core model. It should wait until quantity arithmetic, formatting, and PHPStan
semantics are stable enough that formula strings can share the same runtime/static behavior.

## Deferred Work

The multiplicative runtime foundation is now strong enough to start static analysis work. Remaining runtime gaps are
mostly catalog semantics, API polish, and edge-case formatting.

- GNU Units import
- User-defined registry/catalog composition
- Better plural handling using generated UDUNITS2 plural metadata rather than suffix stripping alone
- Offset and affine units, especially temperature
- Logarithmic units
- Better numeric output policies for decimal/float conversion
- PHPStan static analysis extension — see [phpstan-extension.md](phpstan-extension.md)
- Scalar-specific PHPDoc types such as `unit_int` or `unit_float` (optional edge types; not the core model)
- Public documentation for generated catalog regeneration
- Public documentation for runtime guarantees and non-goals

## Near-Term Roadmap

Suggested next slices (detail in [phpstan-extension.md](phpstan-extension.md)):

1. Add PHPStan type parsing. **Done for the native path:** `unit_int<'…'>` / `unit_float<'…'>` resolve via
   `UnitTypeNodeResolverExtension`, parsing the string through Yumemi's runtime parser and storing the reduced
   expression on `UnitIntegerType` / `UnitFloatType`. **Also done (Piece 7):** the `Quantity<'meter / second'>` object
   generic (sugar for `Quantity<Rational, '…'>`), resolved by the same `UnitTypeNodeResolverExtension`.

2. Add PHPStan diagnostics for invalid unit strings. **Done:** invalid units become `ErrorType` with Yumemi messages in
   PHPDoc and constant args, and `InvalidUnitCallRule` now emits standalone `yumemi.invalidUnitCall` diagnostics for
   invalid `unit()` / `unit_to()` calls.

3. Add PHPStan return-type inference. **Done for the native path:** operator inference for `+ - * / ** %`, plus `unit()`
   / `unit_to()` dynamic return types. **Also done (Piece 7):** the `Quantity` _method_ inference —
   `QuantityMethodReturnTypeExtension` brands `mul()` / `div()` / `pow()` / `neg()` / `add()` / `sub()` / `to()` /
   `normalize()` / `simplify()`, validates conversion/extraction targets, and brands `intValueIn()` /
   `exactIntValueIn()` results. `simplify()` removes the normalized scale constant from the static unit because runtime
   folds it into the magnitude.

4. Add PHPStan checks for `add()` and `sub()`. **Done:** native `+` / `-` require normalized-equivalent units;
   `Quantity::add()` / `sub()` require compatible dimensions; and `Quantity::addWithSameUnit()` / `subWithSameUnit()`
   require normalized-equivalent units. Native dimension-only addition is intentionally not supported because PHP cannot
   convert the right operand's magnitude.

5. Harden registry extensibility. **Mostly done:** immutable `UnitRegistry` + `UnitRegistryBuilder` (`empty()` /
   `default()` with UDUNITS2, `define('name = expr')`, `add()`, `alias()`, `CompositeUnitRegistry`), plus PHPStan's
   `parameters.yumemi.registryFactory` hook and automatic result-cache fingerprinting. Remaining: user-defined base
   dimensions.

6. Improve catalog semantics. Replace simple plural stripping with catalog plural aliases where possible, and design
   explicit behavior for affine and logarithmic definitions.

> **Update 2026-07-26:** The runtime **`Quantity<…>` object path** is now done (Piece 7 — commits `7b8b759`, `64786af`):
> `Quantity<'…'>` PHPDoc resolution, `Units::quantity()` inference, and fluent-method inference through `mul` / `div` /
> `pow` / `neg` / `add` / `sub` / `to` / `normalize`. `simplify()` inference and object-path `Quantity`
> addition/subtraction checks are also complete.

> **Update 2026-07-27:** PHPStan custom catalogs are now supplied by a typed `UnitRegistryFactory`, shared by every
> extension path and fingerprinted for result-cache invalidation. Native `+` / `-` deliberately remain exact-unit-only;
> a dimension-only mode would accept arithmetic whose raw PHP magnitudes have not been converted. Native/`Quantity`
> boundaries now reject mismatched branded construction and incompatible conversions, while integer extraction carries
> the requested unit back to `unit_int`.

## Current Architecture Sketch

```text
UDUNITS2 XML -> Udunits2CatalogImporter -> PhpCatalogExporter -> data/udunits2.php
data/udunits2.php -> Udunits2UnitRegistry (catalog records only)
UnitResolver -> record()/lookup() -> AstConverter (defs/prefixes) -> Expr
Parser string -> Parser\Ast -> AstConverter (resolving or symbolic) -> Expr
Expr -> ExprReducer -> reduced Expr
Expr -> UnitNormalizer -> normalized Expr
normalized Expr -> ConversionFactorResolver -> Rational factor
Units facade -> Quantity/runtime expression API
```

The PHPStan layer should later reuse the same pipeline.
