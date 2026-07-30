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

The implemented foundation now includes:

- exact `Rational` arithmetic with explicit integer, decimal, and binary64 output policies;
- a reduced symbolic expression model, Bison parser, seven-axis `Dimension`, and derived-unit normalization;
- a generated UDUNITS2 catalog with exact aliases, plurals, prefixes, introspection, and deterministic regeneration;
- mutable custom-registry construction producing immutable snapshots;
- exact multiplicative and affine scale-and-offset conversion at explicit boundaries;
- exact `Quantity` construction, parsing, arithmetic, comparison, conversion, normalization, simplification, and output;
- configurable ASCII and Unicode formatting with catalog-aware names and fraction or negative-power division;
- native `unit_int` / `unit_float` and object `Quantity<'...'>` PHPStan types with arithmetic inference, diagnostics,
  custom registries, finite literal-string unions, and optional `@yumemi-*` promotion;
- focused public documentation whose executable PHP and PHPStan examples are verified in process.

The public behavior is documented in [Core Concepts](../pages/core-concepts.md) and the
[PHPStan](../pages/reference/phpstan.md), [Unit Syntax](../pages/reference/unit-syntax.md),
[Runtime](../pages/reference/runtime.md), and [Catalog](../pages/reference/catalog.md) references. This document tracks
architecture, rationale, risks, and future work rather than duplicating those references.

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

The native path introduces no runtime wrapper; the object path retains exact `Rational` state. Both reuse the runtime
parser, resolver, registry, reducer, dimensions, formatter, and conversion engine. Unit identity remains a reduced
Yumemi `Expr`, not a class-per-unit hierarchy or a second PHPStan-only expression model.

The current type behavior, helper inference, diagnostic identifiers, and limitations are maintained in the
[PHPStan reference](../pages/reference/phpstan.md).

### Annotation Surfaces

Direct unit types are the normal surface. Optional `@yumemi-param`, `@yumemi-return`, and `@yumemi-var` promotion exists
for libraries that require ordinary fallback PHPDoc, but replaces internal PHPStan parser services and remains an
upgrade and extension-conflict risk. The exact structural rules belong in
[Extension-Optional Annotations](../pages/reference/phpstan.md#extension-optional-annotations).

### Registry Configuration

PHPStan uses one configured immutable registry and fingerprints it for result-cache invalidation. Runtime code should
construct `Units` from the same registry when custom units are shared across both layers. Configuration is documented in
[Registry Configuration](../pages/reference/phpstan.md#registry-configuration).

### PHPStan Testing Notes

Prefer direct unit tests for container-free type and algebra logic, rule tests for diagnostics, and in-process
`TypeInferenceTestCase` fixtures for propagation. Assertion fixtures use `AssertsFixtureUnderCoverage` and run from the
test body rather than a data provider: PHPStan's process-global parser/PHPDoc caches would otherwise warm during test
discovery, outside coverage, and prevent parse-time extensions from executing again.

CLI integration tests still spawn the real PHPStan binary for startup, parser-service, and end-to-end checks. Their
child-process coverage is intentionally not merged; correctness matters more than an inflated coverage figure.

## Runtime API Direction

The runtime deliberately has expression-level operations on `Units` and value-level operations on exact `Quantity`
objects. The complete API and examples live in the [runtime reference](../pages/reference/runtime.md).

Important design rule:

> Quantity addition and subtraction convert the right operand into the left operand's unit and preserve the left unit.
> The explicit `*WithSameUnit()` variants reject operands that would require conversion.

Comparisons follow the same compatible-unit conversion rule but return only a scalar result, so strict same-unit
comparison variants remain deferred. Multiplication and division reduce chosen symbolic syntax without silently
substituting catalog definitions. `normalize()`, `simplify()`, and explicit target conversion remain distinct
operations.

## Parser And Syntax Direction

The parser is intentionally broader than the semantic runtime layer. This is acceptable because the long-term goal is
UDUNITS2 compatibility, but syntax must not imply semantic support.

The grammar is derived in part from UDUNITS2 `lib/parser.y`. The derivative grammar is distributed under the project
license while the incorporated upstream portions remain subject to the UCAR License. Its product precedence follows
UDUNITS2: adjacency, `*`, `.`, `·`, and `/` associate left at one tier, while powers bind more tightly. Consequently,
`meter / second kilogram` means `(meter / second) * kilogram`; a compound denominator requires parentheses.

The accepted public grammar and semantic boundaries are maintained in the
[Unit Syntax reference](../pages/reference/unit-syntax.md). The exact conversion resolver separately interprets
standalone affine definitions at explicit conversion boundaries; logarithmic definitions remain introspectable but
unevaluable.

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

Full rational unit powers would be a cross-cutting representation change. `Expr\Power`, reduction state, `Dimension`,
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

Exact `Rational` storage remains authoritative, and every native conversion is explicit. The complete rounding,
termination, overflow, underflow, and PHPStan-branding policies are maintained in
[Native Numeric Output](../pages/reference/runtime.md#native-numeric-output).

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

The runtime comparer remains the source of truth for definitional equivalence and dimensional compatibility. Native
arithmetic uses the stricter relation because it cannot convert operands; exact `Quantity` methods may use compatibility
because they perform conversion. Public semantics live in
[Definitional Equivalence And Compatibility](../pages/reference/phpstan.md#definitional-equivalence-and-compatibility)
and [Quantity Arithmetic](../pages/reference/runtime.md#quantity-arithmetic).

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

The multiplicative runtime and the PHPStan native/Quantity paths are usable. Remaining work is mostly documentation
expansion and polish, API polish, catalog semantics beyond multiplication, and explicitly deferred advanced features.

### Pre-Release Checklist

- Before creating the first release tag, remove `:dev-master` from the README installation command; after Packagist
  imports the tag, verify that the unqualified command installs the tagged release.

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
- Finite target unions are supported by `unit()` and on Quantity boundaries. `unit_to()` has independent source and
  target unions whose Cartesian product loses value correlation.
- Lookup is case-sensitive. Short but valid prefix/symbol compositions such as `pa` (pico-are) and `PA` (peta-ampere)
  remain accepted while `Pa` is pascal; Yumemi does not special-case these catalog-valid ambiguities.
- Syntax errors carry decoded-expression byte spans. Unknown-unit and unsupported-semantic errors occur after parsing
  and remain unspanned because AST nodes do not yet retain source locations.
- Very large parsed integer exponents may exceed PHP integer range before reaching the expression model.
- The UDUNITS2 importer still special-cases `cm2` syntax, and generated `prefixRegex` metadata is currently unused by
  resolution.
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
UnitResolver -> findCatalogRecord()/findPrebuiltUnit() -> AstConverter (defs/prefixes) -> Expr
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
