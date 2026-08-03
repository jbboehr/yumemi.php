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
- a reduced symbolic expression model, Bison parser, seven-axis SI `Dimension`, and derived-unit normalization;
- a generated UDUNITS2 catalog with exact aliases, plurals, prefixes, introspection, and deterministic regeneration;
- mutable custom-registry construction producing immutable snapshots;
- exact multiplicative and affine scale-and-offset conversion, synthesized affine-difference units, and point
  coordinates;
- exact `Quantity` construction, parsing, arithmetic, comparison, conversion, normalization, simplification, and output;
- exact `PointQuantity` conversion, translation, difference, comparison, and output;
- versioned native serialization, exact JSON representations, compact debug output, and scoped custom-registry
  deserialization for runtime value objects;
- configurable ASCII and Unicode formatting with catalog-aware names and fraction or negative-power division;
- native `unit_int` / `unit_float` and object `Quantity<'...'>` / `PointQuantity<'...'>` PHPStan types with arithmetic
  inference, diagnostics, custom registries, finite literal-string unions, and optional `@yumemi-*` promotion;
- an explicit package-stub loader with unit-aware cache, lock, rate-limiter, HTTP-client, retry, and fake-upload
  signatures for `illuminate/cache` and `illuminate/http` 11 through 13;
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
- PHPBench covers representative cold and warm runtime workflows; CI smoke-tests benchmark discovery without timing
  floors, while an optional Linux Perfidious profile captures local `perf_events` counters
- Infection runs against all handwritten runtime source in CI with 86% total and covered MSI floors; the PHPStan adapter
  and generated parser are excluded
- a separate Xdebug development shell supports [focused, local branch and path coverage audits](branch-coverage.md)
  without adding their cost to CI or `nix flake check`; branch and path percentages currently have no enforced floor
- isolated consumer fixtures install a mirrored Composer package, verify automatic and manual PHPStan registration, and
  run against release-style `composer archive` output in CI; separate matrices verify the optional Illuminate Cache and
  HTTP stubs against majors 11 through 13 without adding Laravel to the root development dependencies. The local
  verification snapshots are `illuminate/cache` `v11.51.0`, `v12.64.0`, and `v13.23.0` on 2026-07-31, and
  `illuminate/http` `v11.51.0`, `v12.64.0`, and `v13.23.0` on 2026-08-02. CI continues to resolve the latest compatible
  release in each major rather than pinning those patch versions

## PHPStan Model And Status

Yumemi intentionally has native and exact-object presentation layers over the same unit engine:

| Layer                   | Magnitude model                           | Primary audience                               |
| ----------------------- | ----------------------------------------- | ---------------------------------------------- |
| Runtime `Quantity`      | Exact `Rational` interval or magnitude    | Exact multiplicative conversion and arithmetic |
| Runtime `PointQuantity` | Exact `Rational` coordinate               | Affine points, translation, and differences    |
| PHPStan branded values  | Native PHP `int` / `float` plus an `Expr` | Existing application code using native data    |

The native path introduces no runtime wrapper; the object paths retain exact `Rational` state. All reuse the runtime
parser, resolver, registry, reducer, dimensions, formatter, and conversion engine. Multiplicative unit identity remains
a reduced Yumemi `Expr`; point identity additionally retains a named coordinate origin and difference scale.

The current type behavior, helper inference, diagnostic identifiers, and limitations are maintained in the
[PHPStan reference](../pages/reference/phpstan.md).

### Annotation Surfaces

Direct unit types are the normal surface. Optional `@yumemi-param`, `@yumemi-return`, and `@yumemi-var` promotion exists
for libraries that require ordinary fallback PHPDoc, but replaces internal PHPStan parser services and remains an
upgrade and extension-conflict risk. The exact structural rules belong in
[Extension-Optional Annotations](../pages/reference/phpstan.md#extension-optional-annotations).

The same opt-in configuration exposes `yumemi.stubs`, an explicit list of supported Composer packages. The loader
detects installed versions through Composer, sorts and deduplicates selections, and rejects unknown, absent, or
unsupported package majors. Laravel integrations target every released and verified major beginning with Laravel 11; the
explicit list is currently 11 through 13, and future majors remain rejected until their signatures pass the same
compatibility matrix. Illuminate Cache is the reference integration. Illuminate HTTP adds second and millisecond client
boundaries plus exact fake-upload sizes and byte returns shared by those majors. Complex union signatures carry an
equivalent pre-promoted PHPStan tag alongside `@yumemi-param`: PHPStan can request stub reflection recursively while the
promoting parser is still initializing, so the direct tag supplies a stable bootstrap representation while idempotent
promotion verifies that both declarations remain identical.

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

Affine coordinates use a separate `PointQuantity` model. Point subtraction returns a multiplicative `Quantity` in the
left point's generated delta unit; adding or subtracting a compatible `Quantity` translates a point. Point-plus-point,
point multiplication, division, powers, negation, normalization, and simplification are intentionally absent. Generated
`delta_*` and `Δ` catalog entries are ordinary multiplicative units. The runtime never rewrites an affine name inside
algebra: callers must write `delta_celsius / second`, not `celsius / second`.

## Parser And Syntax Direction

The parser is intentionally broader than the semantic runtime layer. This is acceptable because the long-term goal is
UDUNITS2 compatibility, but syntax must not imply semantic support.

The grammar is derived in part from UDUNITS2 `lib/parser.y`. The derivative grammar is distributed under the project
license while the incorporated upstream portions remain subject to the UCAR License. Its product precedence follows
UDUNITS2: adjacency, `*`, `.`, `·`, and `/` associate left at one tier, while powers bind more tightly. Consequently,
`meter / second kilogram` means `(meter / second) * kilogram`; a compound denominator requires parentheses.

The accepted public grammar and semantic boundaries are maintained in the
[Unit Syntax reference](../pages/reference/unit-syntax.md). The exact conversion resolver separately interprets
standalone affine definitions at explicit conversion and point-coordinate boundaries. Catalog generation and custom
registry construction synthesize explicit multiplicative difference units from those definitions. Logarithmic
definitions remain introspectable but unevaluable.

Parser AST nodes retain zero-based, half-open byte spans. Post-parse unknown-name and unsupported-semantic failures
preserve those spans through the multiplicative, quantity, conversion, point, and PHPStan parsing paths. Resolution of
aliases and stored catalog definitions deliberately attributes an inner failure to the outer identifier written by the
caller.

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

## Deterministic Unknown-Unit Suggestions

Unknown-unit diagnostics suggest close names deterministically for one immutable registry snapshot. Candidate
enumeration does not affect the result: catalog insertion order and composite-registry layer enumeration are removed by
a complete locale-independent ordering.

The ranking defines this total order:

1. ASCII case-folded exact match;
2. edit distance;
3. absolute byte-length difference;
4. candidate kind, preferring canonical names before aliases, plurals, and symbols;
5. raw UTF-8 byte order through `strcmp()`.

Non-case variants must differ by no more than two bytes in length and have a bytewise Levenshtein distance of at most
two. The resolver returns at most five suggestions and omits the clause when no candidate passes. Symbols rank after
canonical names, aliases, and plurals rather than displacing equally close word-like spellings.

`UnitNotFoundException` retains the ordered suggestions structurally, and runtime and PHPStan diagnostics render the
same exception message. Exact-order tests cover case variants, name-kind ties, result bounds, reversed insertion order,
and equivalent composite registries with different layer enumeration. A changed registry may legitimately change a
suggestion; equivalent immutable registry snapshots produce the same ordered suggestions regardless of construction.

## Extensible Base Dimensions And Currency

The seven SI dimensions remain the built-in physical axes and should retain their current named accessors and compact
fixed vector. User-defined primitive dimensions should extend that model rather than replacing it with a string-keyed
map for every ordinary physical expression.

The preferred representation is a hybrid `Dimension` containing:

- the existing seven-element SI vector;
- a nullable sparse map of additional named integer powers, canonicalized by raw name.

Dimension equality, multiplication, division, powers, formatting, JSON, debugging, and serialization must include the
additional map. SI axes should retain their established display order; extension axes should use deterministic bytewise
ordering. Existing version-1 serialized SI-only dimensions should remain readable if the serialized shape changes.

Custom primitive-unit declarations belong to immutable registry metadata. A future builder API should associate a base
unit with a named dimension, after which ordinary definitions can derive other units from it. `DimensionResolver` must
consult the effective registry's primitive-dimension metadata rather than relying solely on its current hard-coded SI
base-unit table. Registry fingerprints must include those declarations so runtime serialization and PHPStan result-cache
invalidation detect semantic changes.

Currency is a useful motivating application but should not become an eighth built-in axis, and Yumemi should not ship or
fetch exchange rates. An application may create one custom `currency` dimension, choose one primitive currency for an
immutable registry snapshot, and define other currencies through exact declared rates. The application remains
responsible for the rates' source, effective time, bid/ask policy, fees, and monetary rounding. Such a snapshot supports
dimensional checking and exact conversion; it is not a complete accounting or money model.

[GNU Units](https://www.gnu.org/software/units/manual/units.html#Currency-Exchange-Rates) demonstrates the snapshot
pattern by selecting one primitive currency and generating the remaining definitions from periodically updated rates.
Yumemi may support the same dimensional structure through custom registries without adopting GNU Units' updater or
treating mutable rates as catalog constants.

This preserves the current arithmetic policy: compatible currencies may be converted explicitly or through exact
`Quantity` operations, while branded native addition still cannot combine different currency units without an explicit
conversion. Cross-registry operations continue to reject values from different rate snapshots.

## Remaining Issues And Deferred Work

The multiplicative and affine-point runtimes and the PHPStan native/object paths are usable. Remaining work is mostly
developer-experience improvement, selected API and formatting polish, extensible registry semantics, and explicitly
deferred advanced features.

### Pre-Release Checklist

- Before creating the first release tag, remove `:dev-master` from the README installation command; after Packagist
  imports the tag, verify that the unqualified command installs the tagged release.

### Verification Roadmap

Verification work should prioritize independent evidence and algebraic invariants over additional examples that can
repeat the implementation's assumptions:

- Add a separate Nix-backed differential suite against the `udunits2` executable. Compare representative base, prefixed,
  accepted, compound, affine, alias, and incompatible-unit cases with Yumemi's results. Yumemi's expectations remain
  exact; comparisons against UDUNITS2's textual floating-point output require an explicit tolerance. Keep this outside
  the ordinary PHPUnit requirement because it depends on an external executable and database.
- Add deterministic generative tests for bounded expression ASTs, rational magnitudes, compatible unit pairs and
  triples, formatter modes, quantities, and affine points. Verify reduction and normalization idempotence,
  parser/formatter round trips, conversion composition and reversal, quantity arithmetic identities, and point
  difference/translation identities. Use fixed seeds or finite enumeration, bounded depth and exponents, and report a
  replayable seed and input for every failure.
- Add a PHP 8.2 lowest-dependency CI job using `composer update --prefer-lowest --prefer-stable`, followed by the normal
  static analysis and PHPUnit checks. Ordinary lock-file jobs verify only one dependency snapshot and do not prove the
  lower bounds declared in `composer.json`.
- Continue focused Xdebug branch audits rather than enforcing a global path-coverage floor. Audit `src/Registry`,
  `src/Catalog`, `PointQuantity`, formatting, and parser diagnostics next; add tests for uncovered decisions only when
  an outcome is reachable and observably meaningful. Path coverage remains informational because combinations grow
  rapidly.
- Triage Infection's escaped and timed-out mutants periodically before raising the MSI floor. Add contract-level
  assertions for observable survivors, record or ignore behaviorally equivalent mutations, distinguish deliberately
  unreachable defensive branches, and confirm that timeouts are explained by removed termination guards rather than
  ordinary performance failures.
- After the first public release establishes a compatibility baseline, run an API compatibility checker such as Roave
  Backward Compatibility Check against the latest release tag. Treat intentional breaking changes through an explicit
  versioning policy instead of weakening or bypassing the check.

### Near-Term Work

- Extend `Dimension` and registry metadata with user-defined primitive dimensions while preserving the seven-axis SI
  fast path; use currency as a custom-registry acceptance case without bundling exchange-rate data.
- Split broad PHPStan diagnostic identifiers only where users need more precise suppression.

### Known Limitations And Risks

- Dynamic unit strings cannot be validated statically and intentionally fall back to native PHPStan return types.
- Direct `Units::conversionFactor()` calls retain their declared `Rational` type. Use `unit_factor()` when native
  target/source branding is needed for PHPStan arithmetic.
- Affine points and multiplicative differences are supported through `PointQuantity` and synthesized delta units. Direct
  affine `Quantity` construction, native affine PHPDoc brands, implicit affine-to-delta rewriting, and prefixed affine
  units remain unsupported.
- `unit_to()` returns plain `float` for affine targets because native affine brands cannot yet express absolute-versus-
  delta semantics. Affine sources converted to multiplicative targets retain the target brand.
- PHPStan assumes one authoritative registry. Flow-sensitive tracking of several runtime registry identities is not
  implemented. Native runtime helpers can be aligned with that registry through the process-wide `Units::setDefault()`;
  instance APIs remain preferable when an application uses several registries concurrently.
- The opt-in `@yumemi-*` parser integration depends on internal PHPStan parser services and may conflict with another
  parser-replacing extension.
- Bundled package stubs intentionally reject unsupported installed majors. Their reflected class, method, property, and
  parameter shapes are checked against real packages in isolated CI jobs, but upstream minor releases can still expose
  signature drift before Yumemi's compatibility range is updated.
- Casts and unsupported PHP built-ins can erase native unit brands. Add targeted extensions only for demonstrated
  workflows rather than trying to model every built-in preemptively.
- Finite source and target unions are supported by native helpers and Quantity boundaries. Independent source and target
  unions lose value correlation, so helper calls validate the Cartesian product and fail closed if any pair is invalid.
- Lookup is case-sensitive. Short but valid prefix/symbol compositions such as `pa` (pico-are) and `PA` (peta-ampere)
  remain accepted while `Pa` is pascal; Yumemi does not special-case these catalog-valid ambiguities.
- Unit, dimension, and scientific-decimal exponents are bounded to `-10000` through `10000`; checked composition rejects
  larger effective powers before native integer overflow or unbounded GMP exponentiation.
- The UDUNITS2 importer still special-cases `cm2` syntax, and generated `prefixRegex` metadata is currently unused by
  resolution.
- Expression arithmetic reduces eagerly. The benchmark suite measures representative reduction and normalization, but no
  cross-machine regression floor or production-workload profile has established that this is a hot path.
- Hardware-counter benchmarks depend on unreleased `phpbench-perfidious` adapter code and local Linux `perf_events`
  permissions; they are optional and intentionally excluded from CI.
- Dimensional analysis intentionally cannot distinguish semantically different quantities with the same dimension, such
  as gray and sievert.
- Exact catalog decimals for angles can normalize to large rationals; this is correct but can produce unwieldy display
  text.

### Deferred Features

- Logarithmic units
- Exact rational powers and roots; approximate results require explicit precision and rounding
- Significant-digit and scientific-notation numeric formatting
- Configurable alternatives to the current strict float policy, which rejects non-finite input, overflow to infinity,
  and nonzero exact results that underflow to zero
- GNU Units import
- Formula interpolation
- Preferred/compact unit selection and broader formatting presets
- Optimize bulk catalog introspection by pre-grouping canonical aliases, symbols, and plurals during generation, then
  lazily caching an effective index per immutable registry. Composite registries must build a composition-aware index so
  base aliases continue to follow overlay replacements. The same index should serve canonical/symbol formatter lookups
  so newly constructed formatters do not repeat catalog scans; expression resolution remains in `UnitResolver`.
- Replace the registry's split prebuilt-unit and catalog-record lookup channels with a typed effective-entry model.
  Until then, composite registries must mask both base channels whenever an overlay contains either representation.
- Stable registry identifiers and an application resolver for serialized graphs containing values from several custom
  `Units` contexts. Native serialization currently supports the default context plus one dynamically scoped custom
  context through `Units::deserialize()` and rejects semantic drift. Broader ecosystem integrations remain deferred.
- Strict same-unit comparison variants and PHP object comparison operators unless a concrete use case appears
- Constant-valued native unit types. A future `UnitConstantFloatType` can extend `UnitFloatType` and implement PHPStan's
  `ConstantScalarType`, preserving a known binary float and unit expression through supported operators; this would not
  make an approximate float mathematically exact.
- Range-bearing native integer unit types. The default `integerOverflowToFloat` policy conservatively infers a
  benevolent `unit_int|unit_float` result because `UnitIntegerType` does not retain PHPStan constant or integer-range
  bounds. A future branded range representation could prove an operation always remains integer, always overflows to
  float, or needs the union. It must preserve and compose signed bounds correctly through addition, subtraction,
  multiplication, unary negation of `PHP_INT_MIN`, and positive powers rather than adding isolated operator exceptions.
- Additional bundled Laravel stubs remain evidence-driven. `illuminate/support` is the highest-value next candidate:
  `Benchmark` returns milliseconds, `Sleep` exposes direct second and microsecond entry points, and `Timebox` accepts
  microseconds, although fluent `Sleep::for(...)->seconds()` cannot brand the value before its later unit selector with
  stubs alone. `illuminate/process` follows with second-based timeouts but changed from `int` to `CarbonInterval|int` in
  Laravel 13. `illuminate/queue` has a broader duration surface plus worker memory, but needs explicit policies for
  arrays, date-like delays, counts, and decimal megabytes versus mebibytes. Smaller candidates include Cookie lifetimes
  and scheduler overlap locks in minutes and Filesystem sizes in bytes. Absolute timestamps such as file modification
  times remain deferred until branded native point semantics can distinguish coordinates from elapsed durations.
- A possible `unit_numeric_string<'...'>` PHPStan type for numeric values that cross string-oriented framework
  boundaries, such as Laravel configuration, environment values, request parameters, headers, and serialized scalar
  fields. It should remain a subtype of `numeric-string`, carry the same unit expression as native brands, and require
  explicit construction or parsing. Arithmetic, coercion, casts, and conversion into `unit_int` / `unit_float` need a
  sound policy before implementation; package stubs should not introduce the type speculatively.

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
