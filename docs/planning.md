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
- Exact `Rational` number type:
  - fixed-scale decimal output with all PHP 8.4 rounding modes
  - minimal exact decimal output for terminating rationals
  - correctly rounded binary64 output with strict overflow and underflow detection
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
  - generated plural aliases honoring explicit plurals and `<noplural>` metadata
  - generated prefix data
  - exact and dynamically prefixed catalog introspection preserving canonical names, component provenance, aliases,
    symbols, plural provenance, comments, and documentation
  - structured support reasons for retained affine and logarithmic definitions
  - mutable fluent registry construction producing immutable registry snapshots
  - resolver-side prefix handling
  - fail-closed, case-sensitive name resolution without runtime morphology
- Exact conversion resolver for multiplicative and affine scale-and-offset transforms
- Public `Units` facade with `Expr|string` ergonomics:
  - `unit()`
  - `parse()`
  - `parseUnit()` alias
  - `parseQuantity()` with exact explicit-constant extraction
  - `normalize()`
  - `compatible()`
  - `conversionFactor()`
  - `convert()`
  - `convertFloat()`
  - `quantity()`
  - `format()` and reusable registry-aware formatters
  - `describe()`
  - `describePrefix()`
- Explicit affine conversion support:
  - exact UDUNITS2 Celsius/Fahrenheit conversion through `Rational` scale and offset transforms
  - custom affine definitions and chained aliases through `UnitRegistryBuilder`
  - affine-aware dimensional compatibility and dimension lookup
  - value-independent `conversionFactor()` rejection through `NonMultiplicativeConversionException`
  - strict rejection of affine multiplication, division, powers, prefixes, quantities, and ordinary unit brands
- Ported generated parser from `units.php`:
  - grammar
  - lexer
  - AST nodes
  - generated `Parser.php`
  - Composer/Makefile generation wiring
  - exact half-open byte spans for syntax errors
  - bounded expression-local caret diagnostics shared by runtime and PHPStan
  - Unicode middle-dot multiplication and signed superscript integer powers
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
- Focused user references for unit syntax, runtime guarantees, catalog behavior, and deterministic regeneration;
  executable PHP blocks and PHPStan-relevant examples are verified in-process
- Runtime `Quantity` value object:
  - explicit `Units` context
  - exact `Rational` value storage
  - parsing complete quantity expressions while preserving catalog scale in the symbolic unit
  - symbolic unit storage for display/chosen syntax
  - resolved unit storage for catalog-aware conversion
  - `to()` and `valueIn()` explicit conversion
  - explicit integer, fixed-scale decimal, exact decimal, and float extraction in a requested unit
  - `add()` and `sub()` with exact conversion of compatible right operands into the left unit
  - `addWithSameUnit()` and `subWithSameUnit()` for normalized-equivalent units without conversion
  - `mul()` and `div()` for unit arithmetic
  - `normalize()` for unit-definition substitution without changing stored value
  - `simplify()` for unit-definition substitution with scale folded into the stored value
  - context checks that reject arithmetic between quantities from different `Units` instances
- Configurable expression formatting:
  - preserved, canonical, or symbol unit names
  - ASCII or round-trippable Unicode typography
  - numeric, word, or empty dimensionless presentation
  - fraction or negative-power division layout
  - direct named construction or immutable fluent option building
  - exact-before-prefix name resolution shared with the runtime resolver

Current verification:

- PHPUnit passes
- PHPStan passes
- PHP-CS-Fixer passes
- Composer validation passes
- Nix flake checks pass
- Infection runs against all handwritten runtime source in CI with 86% total and covered MSI floors; the PHPStan adapter
  and generated parser are excluded

## PHPStan Model And Status

Yumemi intentionally has two presentation layers over the same unit engine:

| Layer                  | Magnitude model                           | Primary audience                            |
| ---------------------- | ----------------------------------------- | ------------------------------------------- |
| Runtime `Quantity`     | Exact `Rational`                          | Code opting into value objects              |
| PHPStan branded values | Native PHP `int` / `float` plus an `Expr` | Existing application code using native data |

The PHPStan path does not introduce runtime wrappers for native values. `unit_int<'meter'>` remains an `int`, and
`unit_float<'meter / second'>` remains a `float`; the extension attaches unit identity during analysis. Runtime object
code can instead use `Quantity<'meter'>`, whose magnitude remains `Rational`.

Both paths reuse the runtime parser, resolver, registry, reducer, dimension resolver, comparer, formatter, and
conversion semantics. Unit identity is always a reduced Yumemi `Expr`, never a class-per-unit hierarchy or a duplicated
PHPStan expression model.

Implemented PHPStan behavior:

- `unit_int<'…'>`, `unit_float<'…'>`, and `Quantity<'…'>` resolve in ordinary PHPDoc type positions.
- Native unary and binary inference supports `+ - * / ** %`; multiplication and division combine units, exponentiation
  requires a constant integer exponent, and modulo requires equivalent `unit_int` operands.
- Native `+` / `-` require normalized-equivalent units. Merely compatible dimensions are insufficient because native
  arithmetic cannot convert the right magnitude.
- `unit()` brands a native multiplicative magnitude. `unit_factor()` returns a native float branded as target/source,
  allowing ordinary multiplication to cancel the source and infer the target. `unit_to()` performs multiplicative or
  affine runtime conversion; multiplicative targets return a branded float while affine targets remain plain `float`.
- `Units::quantity()`, `Units::parseQuantity()`, and all current unit-bearing `Quantity` methods preserve or transform
  the static unit brand.
- Quantity arithmetic, conversion, extraction, and comparisons receive standalone diagnostics even when an invalid
  result is unused.
- Finite literal-string unions are preserved for Quantity construction, quantity parsing, conversion, and native
  extraction.
- One configured registry is authoritative for a PHPStan run and is fingerprinted for result-cache invalidation.

Stable rule identifiers currently include:

- `yumemi.invalidUnitCall`
- `yumemi.invalidQuantityArithmetic`
- `yumemi.invalidQuantityConstruction`
- `yumemi.invalidQuantityConversion`
- `yumemi.invalidQuantityComparison`
- the `yumemi.docTag*` family for optional annotation promotion

Invalid native binary operations use PHPStan's `binaryOp.invalid` diagnostic. Genuinely dynamic unit strings fail open
to native return types because their unit cannot be proven; unknown constant strings fail closed with a diagnostic.

### Annotation Surfaces

Direct `unit_int<'…'>`, `unit_float<'…'>`, and `Quantity<'…'>` types require Yumemi's PHPStan extension. They work in
normal `@param`, `@return`, `@var`, generic, union, intersection, and nullable positions.

Libraries that want optional Yumemi support can pair ordinary fallback tags with `@yumemi-param`, `@yumemi-return`, or
`@yumemi-var`. Promotion is deliberately opt-in through `yumemi-tags.neon`. A Yumemi tag may replace a fallback only
when erasing its unit leaves produces the same PHPDoc structure, including parameter reference and variadic markers. A
mismatch leaves the fallback effective and reports a diagnostic.

The opt-in promoter replaces internal PHPStan parser services for analyzed source and stubs. That coupling is isolated
and integration-tested, but remains an upgrade risk and a potential conflict with another extension replacing the same
services. Ordinary third-party integrations should use standard PHPStan stub files containing direct Yumemi types
instead of parser promotion.

### Registry Configuration

`parameters.yumemi.registryFactory` names a class implementing `UnitRegistryFactory`. Its `create()` method returns the
complete immutable registry used by every extension path. The factory runs once and the resulting names, records,
prebuilt units, and prefixes contribute to PHPStan's result-cache fingerprint.

Runtime code should construct `Units` from the same registry when custom units are used in both layers. PHPStan does not
track a separate registry identity on every branded value, so one shared catalog is the supported static-analysis model.

### PHPStan Testing Notes

Prefer direct unit tests for container-free type and algebra logic, rule tests for diagnostics, and in-process
`TypeInferenceTestCase` fixtures for propagation. Assertion fixtures use `AssertsFixtureUnderCoverage` and run from the
test body rather than a data provider: PHPStan's process-global parser/PHPDoc caches would otherwise warm during test
discovery, outside coverage, and prevent parse-time extensions from executing again.

CLI integration tests still spawn the real PHPStan binary for startup, parser-service, and end-to-end checks. Their
child-process coverage is intentionally not merged; correctness matters more than an inflated coverage figure.

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
- `decimalValueIn(Expr|string $unit, int $scale, RoundingMode $mode): string`
- `exactDecimalValueIn(Expr|string $unit): string`
- `floatValueIn(Expr|string $unit): float`
- `add(self $other): self`
- `sub(self $other): self`
- `addWithSameUnit(self $other): self`
- `subWithSameUnit(self $other): self`
- `compareTo(self $other): int`
- `equals(self $other): bool`
- `lessThan(self $other): bool`
- `lessThanOrEqual(self $other): bool`
- `greaterThan(self $other): bool`
- `greaterThanOrEqual(self $other): bool`
- `mul(self|int|Rational $other): self`
- `div(self|int|Rational $other): self`
- `neg(): self`
- `pow(int $power): self`
- `normalize(): self`
- `simplify(): self`
- `valueToString(): string`
- `unitToString(): string`
- `toString(): string`

Important runtime rule:

> Quantity addition and subtraction convert the right operand into the left operand's unit and preserve the left unit.
> The explicit `*WithSameUnit()` variants reject operands that would require conversion.

Quantity comparisons likewise convert compatible right operands exactly into the left unit. They return only a scalar
comparison result, so strict `*WithSameUnit()` comparison variants are deferred until a concrete use case needs them.

For example, `(meter / second) * second` reduces to `meter`, but `centimeter / second / foot` stays in chosen symbolic
units until the caller explicitly asks for conversion or simplification.

There are currently two catalog-aware operations with different intent:

- `normalize()`: substitutes unit definitions without changing the stored quantity value.
- `simplify()`: substitutes unit definitions and folds any unit scale factor into the stored quantity value.

## Parser And Syntax Direction

The parser is intentionally broader than the semantic runtime layer. This is acceptable because the long-term goal is
UDUNITS2 compatibility, but syntax must not imply semantic support.

The grammar is derived in part from UDUNITS2 `lib/parser.y`. The derivative grammar is distributed under the project
license while the incorporated upstream portions remain subject to the UCAR License. Its product precedence follows
UDUNITS2: adjacency, `*`, `.`, `·`, and `/` associate left at one tier, while powers bind more tightly. Consequently,
`meter / second kilogram` means `(meter / second) * kilogram`; a compound denominator requires parentheses.

Supported by parser and converter now:

- `meter`
- `meter * second`
- `meter second`
- `meter / second`
- `meter^2`
- `second^-2`
- `(meter / second)^2`
- `meter · second`
- `meter²` and `second⁻²`
- decimal constants such as `1.25 meter`
- scientific notation accepted by the lexer

Parsed but unsupported by the multiplicative expression converter:

- `meter + second`
- `meter - second`
- `meter @ 2`

The exact conversion resolver separately supports a standalone `identifier @ number` at explicit conversion boundaries
and in catalog definitions. Affine units still cannot participate in multiplicative expression or quantity algebra.

The parser can read more UDUNITS2 syntax than the runtime chooses to support semantically. The catalog retains
logarithmic and affine definitions with explicit support reasons. Affine definitions now execute only through
conversion, compatibility, and dimension APIs; logarithmic definitions remain unevaluable.

## Rational Powers And Exact Roots

`Quantity::pow()` intentionally accepts only an integer today. Widening it to `int|float` would be incorrect: binary
floating-point exponents cannot provide stable equality, cancellation, formatting, or PHPStan type identity for unit
expressions.

GMP integers can represent a finite decimal exactly as a coefficient and decimal scale (`coefficient * 10^-scale`).
Yumemi's `Rational` is more general because it also represents values such as `1/3` exactly. Neither representation can
store an irrational result such as `sqrt(2)` exactly, however, so arbitrary-precision decimal arithmetic does not by
itself make arbitrary real exponentiation exact.

Future exact exponentiation should use a `Rational` exponent, never a `float`. For an exponent `p/q`, the exact
operation can succeed when the required `q`th roots of the magnitude's numerator and denominator are integers. Otherwise
the exact API should throw. Approximate results should require a separate API with explicit precision and rounding
rather than silently changing `Quantity` from exact rational arithmetic to decimal approximation.

Full rational unit powers would be a cross-cutting representation change. `Expr\Term`, reduction state, `Dimension`,
formatting, normalization, comparison, and PHPStan unit identity currently store integer powers. They would all need
canonical `Rational` powers before expressions such as `meter^(1/10)` could be represented safely.

A smaller future first step is an exact root operation:

```php
$units->quantity(4, 'meter^2')->root(2); // 2 meter
$units->quantity(8, 'meter^3')->root(3); // 2 meter
$units->quantity(2, 'meter^2')->root(2); // throws: sqrt(2) is not rational
```

An initial `root(int $degree)` should require both an exact rational magnitude root and normalized unit powers divisible
by the degree, keeping the resulting unit powers integral. This is useful but not currently on the near-term roadmap;
`pow(int)` remains the supported API until the exact-root semantics and symbolic-unit display policy are designed.

## Numeric Output Policy

Exact `Rational` storage remains authoritative. Converting a rational to a native representation is always explicit:

- `toDecimal($scale, $mode)` returns fixed decimal places and requires a `RoundingMode`.
- `toDecimalExact()` returns a minimal plain decimal or throws when the denominator has factors other than 2 and 5.
- `toFloat()` rounds to the nearest binary64 value with ties to even. It accepts representable subnormals, throws
  `OverflowException` instead of returning infinity, and throws `UnderflowException` when a nonzero value rounds to
  zero.

The corresponding `Quantity` methods convert to the requested compatible unit before extraction. PHPStan validates
constant target units, and `floatValueIn()` returns a `unit_float<'target'>` brand when the target is known.

Future formatting work should add significant-digit and scientific-notation APIs separately rather than overloading
fixed-scale semantics. A future policy API may also allow callers to request IEEE infinity or zero on float range loss;
the default exact-to-native boundary should remain strict.

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

The PHPStan extension makes `Quantity<'meter / second'>` meaningful statically, while runtime application code continues
to use ordinary `Units` and `Quantity` objects.

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

## Remaining Issues And Deferred Work

The multiplicative runtime and the PHPStan native/Quantity paths are usable. Remaining work is mostly release-facing
documentation, API polish, catalog semantics beyond multiplication, and explicitly deferred advanced features.

### Pre-Release Checklist

- Before creating the first release tag, remove `:dev-master` from the README installation command; after Packagist
  imports the tag, verify that the unqualified command installs the tagged release.
- Complete the public API naming pass, especially registry entry terminology and capability-oriented names for affine
  and logarithmic unit semantics.

### Near-Term Work

- Split broad PHPStan diagnostic identifiers only where users need more precise suppression.
- Decide whether user-defined base dimensions justify replacing or extending the fixed seven-axis `Dimension` vector.

### Known Limitations And Risks

- Dynamic unit strings cannot be validated statically and intentionally fall back to native PHPStan return types.
- Direct `Units::conversionFactor()` calls retain their declared `Rational` type. Use `unit_factor()` when native
  target/source branding is needed for PHPStan arithmetic.
- Affine units are currently absolute coordinate systems only. Delta-temperature units, affine `Quantity` construction
  and arithmetic, direct affine PHPDoc brands, and prefixed affine units remain unsupported.
- `unit_to()` returns plain `float` for affine targets because native affine brands cannot yet express absolute-versus-
  delta semantics. Affine sources converted to multiplicative targets retain the target brand.
- PHPStan assumes one authoritative registry. Flow-sensitive tracking of several runtime registry identities is not
  implemented.
- The opt-in `@yumemi-*` parser integration depends on internal PHPStan parser services and may conflict with another
  parser-replacing extension.
- Casts and unsupported PHP built-ins can erase native unit brands. Add targeted extensions only for demonstrated
  workflows rather than trying to model every built-in preemptively.
- Finite target unions are supported on Quantity boundaries. Extending `unit()` is straightforward, but `unit_to()` has
  independent source and target unions whose Cartesian product loses value correlation.
- Lookup is case-sensitive. Short but valid prefix/symbol compositions such as `pa` (pico-are) and `PA` (peta-ampere)
  remain accepted while `Pa` is pascal; Yumemi does not special-case these catalog-valid ambiguities.
- Syntax errors carry decoded-expression byte spans. Unknown-unit and unsupported-semantic errors occur after parsing
  and remain unspanned because AST nodes do not yet retain source locations.
- Very large parsed integer exponents may exceed PHP integer range before reaching the expression model.
- The UDUNITS2 importer still special-cases `cm2` syntax, and generated `prefixRegex` metadata is currently unused by
  resolution.
- Custom registry support metadata propagates through direct affine/logarithmic markers and exact-name synonym chains.
  Compound definitions that reference affine or logarithmic units are rejected lazily during resolution but do not yet
  receive transitive descriptor metadata.
- Expression arithmetic reduces eagerly and has not been benchmarked as a hot path.
- Dimensional analysis intentionally cannot distinguish semantically different quantities with the same dimension, such
  as gray and sievert.
- Exact catalog decimals for angles can normalize to large rationals; this is correct but can produce unwieldy display
  text.

### Deferred Features

- Delta-temperature units and affine quantity/arithmetic semantics; exact affine conversion boundaries are implemented
- Logarithmic units
- Exact rational powers and roots; approximate results require explicit precision and rounding
- Significant-digit and scientific-notation numeric formatting
- Configurable float overflow and underflow policies; current exact-to-float conversion is deliberately strict
- GNU Units import
- Formula interpolation
- Preferred/compact unit selection and broader formatting presets
- Optimize bulk catalog introspection by pre-grouping canonical aliases, symbols, and plurals during generation, then
  lazily caching an effective index per immutable registry. Composite registries must build a composition-aware index so
  base aliases continue to follow overlay replacements. The same index should serve canonical/symbol formatter lookups
  so newly constructed formatters do not repeat catalog scans; expression resolution remains in `UnitResolver`.
- Replace the registry's split prebuilt-unit and catalog-record lookup channels with a typed effective-entry model.
  Until then, composite registries must mask both base channels whenever an overlay contains either representation.
- Quantity serialization and ecosystem integrations
- Strict same-unit comparison variants and PHP object comparison operators unless a concrete use case appears
- Constant-valued native unit types. A future `UnitConstantFloatType` can extend `UnitFloatType` and implement PHPStan's
  `ConstantScalarType`, preserving a known binary float and unit expression through supported operators; this would not
  make an approximate float mathematically exact.
- Bundled third-party stubs until specific libraries are selected; ordinary PHPStan stubs already work

The broader feature comparison and intentionally deferred Pint-style capabilities remain in
[pint-parity.md](pint-parity.md).

## Current Architecture Sketch

```text
UDUNITS2 XML -> Udunits2CatalogImporter -> PhpCatalogExporter -> data/udunits2.php
data/udunits2.php -> Udunits2UnitRegistry (catalog records only)
UnitResolver -> record()/lookup() -> AstConverter (defs/prefixes) -> Expr
Parser string -> Parser\Ast -> AstConverter (resolving or symbolic) -> Expr
Expr -> ExprReducer -> reduced Expr
Expr -> UnitNormalizer -> normalized Expr
conversion string/Expr -> UnitConversionResolver -> exact scale-and-offset transform
normalized multiplicative Expr -> ConversionFactorResolver -> Rational factor (low-level API)
Units facade -> Quantity/runtime expression API

PHPDoc/call site -> UnitTypeNodeResolverExtension/dynamic return extensions/rules
configured UnitRegistryFactory -> shared Units -> UnitExpressionParser -> runtime pipeline above
UnitExpression -> UnitIntegerType/UnitFloatType/QuantityType -> PHPStan inference and diagnostics
```

The PHPStan layer is an adapter over the same runtime pipeline; it does not maintain a second catalog or expression
engine.
