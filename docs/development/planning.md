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

The generic [Ruinenwert](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/master/RUINENWERT.md) guidance,
pinned for development through `jbboehr/doctrine-of-the-second-sun`, informs long-term decisions about conformance
evidence, generated artifacts, replacement boundaries, and recoverability without becoming a separate feature roadmap.

The durable component map, dependency direction, generated-artifact boundaries, expected decay points, and
project-specific Ruinenwert profile live in [`architecture.md`](architecture.md). This document retains roadmap,
rationale, risks, and future work.

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
- a reduced symbolic expression model, Bison parser, bounded successful syntax and resolved-expression caches, hybrid
  SI/extension `Dimension`, and derived-unit normalization;
- a generated UDUNITS2 catalog with exact aliases, plurals, prefixes, introspection, and deterministic regeneration;
- mutable custom-registry construction producing immutable snapshots, with one typed effective entry per exact lookup so
  composite overlays select a whole layer before exposing prebuilt expressions or catalog metadata while legacy lookup
  overrides remain compatible;
- exact multiplicative and affine scale-and-offset conversion, synthesized affine-difference units, and point
  coordinates;
- exact `Quantity` construction, parsing, arithmetic, comparison, conversion, normalization, simplification, and output;
- context-bound preferred-unit profiles for exact application-selected `Quantity` conversion by dimension;
- exact engineering-prefix compaction within caller-selected named unit families;
- exact `PointQuantity` conversion, translation, difference, comparison, and output;
- versioned native serialization, exact JSON representations, compact debug output, and scoped custom-registry
  deserialization for runtime value objects;
- configurable ASCII and Unicode formatting with catalog-aware names and fraction or negative-power division;
- native `unit_int` / `unit_float` and object `Quantity<'...'>` / `PointQuantity<'...'>` PHPStan types with arithmetic
  inference, branded integer constants and ranges, known branded float values, overflow-aware bounds, diagnostics,
  custom registries, strict native helper expressions, finite object-boundary unions, explicit numeric-cast and common
  scalar-function brand preservation, `unit_numeric_string<'...'>` for trusted string-oriented boundaries, and optional
  `@yumemi-*` promotion;
- a separately versioned [Yumemi Apocrypha](https://github.com/jbboehr/yumemi-apocrypha.php) package for curated
  third-party stubs, leaving the generic `@yumemi-*` mechanism in core;
- focused public documentation whose executable PHP and PHPStan examples are verified through Akashi, using child
  processes only for examples whose authored namespaces require isolation.

The public behavior is documented in [Core Concepts](../pages/core-concepts.md) and the
[PHPStan](../pages/reference/phpstan.md), [Unit Syntax](../pages/reference/unit-syntax.md),
[Runtime](../pages/reference/runtime.md), and [Catalog](../pages/reference/catalog.md) references. This document tracks
rationale, risks, and future work rather than duplicating those references or the durable
[architecture](architecture.md).

Current verification:

- PHPUnit passes
- PHPStan passes
- PHP-CS-Fixer passes
- Composer validation passes
- `nix flake check --keep-going -L` runs independent normal checks for PHP 8.2 through PHP 8.5, PHPStan, php-cs-fixer,
  formatting, documentation, generated artifacts, benchmarks, and isolated consumers
- GitHub Actions retains a small conventional PHP 8.2 baseline for PHPUnit, PHPStan, and php-cs-fixer while an
  independently generated exhaustive Nix matrix deliberately repeats those checks and adds the supported-PHP, consumer,
  generated-artifact, documentation, and other flake checks
- fresh lowest-dependency and highest-dependency solves for released requirements remain separate conventional jobs on
  PHP 8.2 and PHP 8.5, respectively; direct development-branch tools remain pinned to the revisions used by committed
  generated or copied integrations
- a separate master-focused, manually dispatchable advisory workflow exercises PHP 8.2 on macOS and Windows, including
  the release-style package consumers; it remains outside ordinary pull-request gates and does not weaken the required
  Linux matrix
- PHPBench covers representative cold and warm runtime workflows; CI smoke-tests benchmark discovery without timing
  floors, while an optional Linux Perfidious profile captures local `perf_events` counters
- Infection runs as two explicit Nix package jobs, outside ordinary `nix flake check`, against all handwritten runtime
  source and the in-process PHPStan adapter tests, with respective total and covered MSI floors of 86% and 85%; the
  generated parser remains excluded
- a separate Xdebug development shell supports [focused, local branch and path coverage audits](branch-coverage.md)
  without adding their cost to CI or `nix flake check`; branch and path percentages currently have no enforced floor
- isolated consumer fixtures install a mirrored Composer package, verify automatic and manual PHPStan registration,
  exercise consumer-owned degree and meter annotations against phpgeo 6.0.4, and run against release-style
  `composer archive` output in CI; the phpgeo fixture proves the generic downstream integration contract, while
  Apocrypha owns upstream-package matrices and release-style verification for curated integrations

## PHPStan Model And Status

Yumemi intentionally has native and exact-object presentation layers over the same unit engine:

| Layer                   | Magnitude model                                             | Primary audience                               |
| ----------------------- | ----------------------------------------------------------- | ---------------------------------------------- |
| Runtime `Quantity`      | Exact `Rational` magnitude                                  | Exact multiplicative conversion and arithmetic |
| Runtime `PointQuantity` | Exact `Rational` coordinate                                 | Affine points, translation, and differences    |
| PHPStan branded scalars | Native PHP `int`, `float`, or numeric string plus an `Expr` | Existing application code using native data    |

The branded scalar paths introduce no runtime wrapper; numeric strings require an explicit numeric cast before entering
unit-aware arithmetic. The object paths retain exact `Rational` state. All reuse the runtime parser, resolver, registry,
reducer, dimensions, formatter, and conversion engine. Multiplicative unit identity remains a reduced Yumemi `Expr`;
point identity additionally retains a named coordinate origin and difference scale.

The current type behavior, helper inference, diagnostic identifiers, and limitations are maintained in the
[PHPStan reference](../pages/reference/phpstan.md).

Branded integer metadata extraction is deliberately limited to direct branded integers and immediate integer
intersection constraints. A `unit_int` nested inside a callable return, array value, generic, or other compound type
remains metadata of that component and cannot cause the containing type to be classified as an integer.

### Annotation Surfaces

Direct unit types are the normal surface. Optional `@yumemi-param`, `@yumemi-return`, and `@yumemi-var` promotion exists
for libraries that require ordinary fallback PHPDoc, but replaces internal PHPStan parser services and remains an
upgrade and extension-conflict risk. The exact structural rules belong in
[Extension-Optional Annotations](../pages/reference/phpstan.md#extension-optional-annotations).

Curated third-party stubs and their package/version selection policy live in
[Yumemi Apocrypha](https://github.com/jbboehr/yumemi-apocrypha.php). This keeps framework scope and compatibility
matrices outside core while reusing the same generic annotation mechanism. Complex union signatures may carry an
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

### PHPStan Repetition Audit

The dated [PHPStan repetition audit](phpstan-repetition-audit.md) found one narrow consolidation worth implementing:
direct top-level union expansion and benevolent-result recombination are repeated across several resolver families and
have carried prior soundness defects. Slice 1 established the independently tested `UnitUnionTypeHelper`, including
ordinary-versus-benevolent source precedence and proofs that callable and array components are not traversed. Continue
in reviewable slices; Slice 2 migrated the root and scalar-preserving unary mappers without changing their local
failure policy. The remaining planned implementation is the binary-math Cartesian paths.

Do not generalize branded operand extraction, native-function ownership guards, resolver/rule wrappers, or
quantity/point inference merely because their control flow looks similar. Their failure, identity, array, correlation,
and diagnostic policies remain materially different. Reassess those candidates only after the narrow union helper has
demonstrated a real reduction in branching without hiding resolver-specific semantics.

## Runtime API Direction

The runtime deliberately has expression-level operations on `Units` and value-level operations on exact `Quantity`
objects. The complete API and examples live in the [runtime reference](../pages/reference/runtime.md).

Important design rule:

> Quantity addition and subtraction convert the right operand into the left operand's unit and preserve the left unit.
> The explicit `*WithSameUnit()` variants reject operands that would require conversion.

Comparisons follow the same compatible-unit conversion rule but return only a scalar result, so strict same-unit
comparison variants remain deferred. Multiplication and division reduce chosen symbolic syntax without silently
substituting catalog definitions. `abs()` preserves the symbolic unit while making the exact magnitude nonnegative, and
`isZero()` tests the exact magnitude independently of that unit. `isCompatibleWith()` returns whether two quantities
share both a `Units` context and a dimension; it returns `false` rather than converting or throwing for incompatible
operands. `normalize()`, `simplify()`, and explicit target conversion remain distinct operations.

Affine coordinates use a separate `PointQuantity` model. Point subtraction returns a multiplicative `Quantity` in the
left point's generated delta unit; adding or subtracting a compatible `Quantity` translates a point. Point-plus-point,
point multiplication, division, powers, negation, normalization, and simplification are intentionally absent. Generated
`delta_*` and `Δ` catalog entries are ordinary multiplicative units. `PointQuantity::isCompatibleWith()` follows the
quantity predicate's same-context and compatible-dimension contract without conversion. The runtime never rewrites an
affine name inside algebra: callers must write `delta_celsius / second`, not `celsius / second`.

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

## Rational Powers Beyond Exact Integer-Degree Roots

`Quantity::pow()` intentionally accepts only an integer. Widening it to `int|float` would be incorrect: binary
floating-point exponents cannot provide stable equality, cancellation, formatting, or PHPStan type identity for unit
expressions.

GMP integers can represent a finite decimal exactly as a coefficient and decimal scale (`coefficient * 10^-scale`).
Yumemi's `Rational` is more general because it also represents values such as `1/3` exactly. Neither representation can
store an irrational result such as `sqrt(2)` exactly, however, so arbitrary-precision decimal arithmetic does not by
itself make arbitrary real exponentiation exact.

Future general exact exponentiation should use a `Rational` exponent, never a `float`. For an exponent `p/q`, the exact
operation can succeed when the required `q`th roots of the magnitude's numerator and denominator are integers. Otherwise
the exact API should throw. Approximate results should require a separate API with explicit precision and rounding
rather than silently changing `Quantity` from exact rational arithmetic to decimal approximation.

Full rational unit powers would be a cross-cutting representation change. `Expr\Power`, reduction state, `Dimension`,
formatting, normalization, comparison, and PHPStan unit identity currently store integer powers. They would all need
canonical `Rational` powers before expressions such as `meter^(1/10)` could be represented safely.

A deliberately narrower exact root operation is now implemented:

```php
$units->quantity(4, 'meter^2')->root(2); // 2 meter
$units->quantity(8, 'meter^3')->root(3); // 2 meter
$units->quantity(2, 'meter^2')->root(2); // throws: sqrt(2) is not rational
```

`Rational::root()`, `Dimension::root()`, and `Quantity::root()` accept positive degrees through `10000`. They require an
exact rational magnitude root and powers divisible by the degree, keeping all resulting powers integral. Negative
magnitudes accept only odd degrees. `Quantity::root()` reduces but does not substitute the caller's symbolic unit names;
`kilometer * millimeter` therefore requires an explicit `simplify()` or `normalize()` before its square root can be
taken. PHPStan infers a rooted `Quantity` unit for a known valid degree and diagnoses invalid symbolic roots, but
runtime magnitude exactness remains a possible `NonExactRootException`.

General `Rational` exponents still require the cross-cutting representation work above. A future approximate API still
needs an explicit precision, rounding, unit-power, and PHPStan contract.

## Numeric Output Policy

Exact `Rational` storage remains authoritative, and every native conversion is explicit. The complete rounding,
termination, overflow, underflow, and PHPStan-branding policies are maintained in
[Native Numeric Output](../pages/reference/runtime.md#native-numeric-output).

Significant-digit output is separate from fixed-scale output: `toDecimal()` always interprets its integer as decimal
places, while `toSignificantDecimal()` always interprets it as significant precision. `DecimalNotation` renders the same
rounded coefficient in plain or scientific form. `FloatRangePolicy` keeps exact-to-native float output strict by default
and can explicitly select signed infinity or signed zero when binary64 range is lost. Native-float helper paths remain
strict because they also accept or produce already-approximate values.

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

## Source Spelling And Semantic Identity

The runtime already preserves a caller's reduced symbolic unit spelling where one value has one unambiguous presentation
choice. `Units::format()` parses string input symbolically, `Quantity` retains a symbolic expression, `PointQuantity`
retains its named coordinate scale, and conversion and addition preserve the documented target or left-hand unit. JSON
and native serialization retain those value-object spellings. This preserves identifiers and their symbolic algebra, not
original source bytes: whitespace, parentheses, explicit factors of one, and factor order may be reduced or formatted
canonically.

Resolved `Expr` values from `Units::parse()` and `Units::unit()` deliberately represent semantic expressions rather than
source provenance. Their registry resolution and shared caches must not acquire presentation-dependent identity. A
caller that needs to retain a display choice should keep the original string or construct a `Quantity` or
`PointQuantity`; formatting a string remains the direct presentation-only path.

PHPStan's `UnitExpression` similarly keeps a symbolic expression for unit algebra, but its type description remains
canonical within each arm. Alternatives that reduce to the same symbolic expression collapse to one semantic type.
Structurally distinct but definitionally equivalent same-carrier alternatives may remain a union when downstream fixed
native contracts must inspect nominal identity and fail closed; ordinary assignment still accepts definitional
equivalence and can therefore narrow a value to its declared boundary type. Yumemi-owned diagnostics quote the reduced
symbolic spelling of an exact unit argument while it remains directly available, but fall back to canonical presentation
after derived operations or other joins make provenance ambiguous. Do not add general source metadata to semantic `Expr`
identity, equality, or cache keys.

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

## Extensible Base Dimensions, Currency, And Raster Samples

The seven SI dimensions remain the built-in physical axes with their established named accessors and compact fixed
vector. User-defined primitive dimensions extend that model rather than replacing it with a string-keyed map for every
ordinary physical expression.

The implemented representation is a hybrid `Dimension` containing:

- the existing seven-element SI vector;
- a nullable sparse map of lower-snake-case named integer powers.

Dimension equality, multiplication, division, powers, formatting, JSON, debugging, and serialization include the
additional map. SI axes retain their established display order; extension axes use deterministic bytewise ordering.
Version-1 serialized SI-only dimensions remain readable through the version-2 representation.

Custom primitive-unit declarations belong to immutable registry metadata. `UnitRegistryBuilder::baseUnit()` associates
one canonical base unit with a named dimension, after which ordinary definitions derive other units from it.
`DimensionResolver` consults effective registry metadata before its hard-coded SI fast path. Registry fingerprints and
new quantity/point serialization seals include the dimension semantics, so PHPStan cache invalidation and restoration
detect changed declarations.

Currency is the acceptance case but does not become an eighth built-in axis, and Yumemi does not ship or fetch exchange
rates. `Dimension::CURRENCY` provides a conventional extension name. An application may choose one primitive currency
for an immutable registry snapshot and define other currencies through exact declared rates. The application remains
responsible for the rates' source, effective time, bid/ask policy, fees, and monetary rounding. Such a snapshot supports
dimensional checking and exact conversion; it is not a complete accounting or money model.

[GNU Units](https://www.gnu.org/software/units/manual/units.html#Currency-Exchange-Rates) demonstrates the snapshot
pattern by selecting one primitive currency and generating the remaining definitions from periodically updated rates.
Yumemi supports the same dimensional structure through custom registries without adopting GNU Units' updater or treating
mutable rates as catalog constants.

This preserves the current arithmetic policy: compatible currencies may be converted explicitly or through exact
`Quantity` operations, while branded native addition still cannot combine different currency units without an explicit
conversion. Cross-registry operations continue to reject values from different rate snapshots.

The bundled default registry uses the same extension-axis mechanism for nominal raster samples. `pixel` is the base unit
of the `image_sample` dimension, so pixel counts and areas remain distinct from physical lengths. `css_pixel` is a
separate length equal to `inch / 96`; conversion between raster samples and physical lengths requires an explicit
resolution such as `pixel / inch`. The authored supplement also provides exact `typographic_point`, `twip`, and
`english_metric_unit` definitions for document integrations while leaving ambiguous `px`, `pt`, `pica`, `dpi`, and `ppi`
spellings unchanged.

## Remaining Issues And Deferred Work

The multiplicative and affine-point runtimes and the PHPStan native/object paths are usable. Remaining work is mostly
developer-experience improvement, selected API and formatting polish, and explicitly deferred advanced features.

### Release Milestones

The implemented feature set is already sufficient for an initial public release. Release readiness should now be based
on contract clarity, clean-package verification, and experience upgrading tagged versions rather than completion of the
deferred feature list.

#### First Tagged Release: 0.1.0

- **Prepared for 0.1.0:** the compatibility policy states that patch releases within one `0.x` minor line preserve the
  documented contract, while a later `0.x` minor may deliberately break it with changelog and migration guidance.
- **Completed 2026-08-11:** the pre-release public-surface audit reviewed documented declarations and named parameters,
  exception categories and metadata, PHPStan pseudo-types and inference, configuration keys, diagnostic identifiers,
  JSON representations, native serialization, catalog behavior, and unit-language semantics. It narrowed concrete
  registries, alternate generated-catalog files, raw records, and direct serialization payload arrays to provisional or
  internal surfaces. Recheck this audit if later user-facing changes enter the release candidate.
- **Prepared for 0.1.0:** the changelog summarizes the shipped capabilities, public installation instructions use the
  `^0.1` release line, and status prose identifies 0.1 as a development contract rather than a stable API.
- Follow the established [release and succession runbook](release-and-succession.md) from a clean release commit. Run
  the complete Composer and Nix checks, inspect the archive, require it through the isolated runtime and PHPStan
  consumers, verify the exact GitHub Actions commit, create a signed tag, and confirm GitHub, Pages, Packagist, and a
  fresh unqualified installation after publication.
- Do not add another feature merely to make the first release appear larger. Fix correctness or contract problems found
  by the surface audit, but otherwise use the `0.x` series to discover real workflow needs.

#### Stable Release: 1.0.0

- Accumulate real use across multiple `0.x` releases, including runtime conversion, PHPStan analysis, a custom registry,
  and at least one maintained Apocrypha integration. Exercise upgrades, deprecations, release notes, and package
  publication before promising long-term compatibility.
- **Established after 0.1.0:** run the isolated Roave Backward Compatibility Check through `composer check:bc` and a
  full-history CI job, comparing committed `HEAD` with the latest stable tag. Treat it as a conservative class-like PHP
  signature safety net: global helper signatures, release-produced serialization fixtures, retained JSON and conformance
  cases, PHPStan inference and diagnostic fixtures, and explicit catalog review must continue to cover contracts it
  cannot see.
- **Established after 0.1.0:** maintain the PHP-specific
  [release persistence corpus](../../tests/Compatibility/README.md). Its initial `v0.1.0` directory was produced from an
  isolated installation of the tagged package and covers exact rationals, fixed and application-defined dimensions,
  default and custom-registry quantities, affine points, named-dimension values, and catalog descriptors. Each future
  tagged release should add immutable native-serialization and JSON evidence without rewriting earlier release bytes.
- Resolve avoidable ambiguity among supported, provisionally public, and internal declarations. Audit named arguments,
  construction paths, exceptions, persistent formats, registry integration, and formatting policy as contracts intended
  to survive for years rather than merely as useful current implementation.
- Establish a demonstrated PHPStan support cadence across the documented minimum and current releases. Adapter internals
  may change, but pseudo-types, diagnostics, configuration, automatic and manual registration, and optional tag behavior
  must retain tested upgrade paths.
- Perform one final cross-cutting semantic audit against the compatibility policy, invariants, conformance corpus,
  public examples, and released behavior. Enter `1.0.0` with no known correctness defect in the supported surface, not
  with every conceivable dimensional-analysis feature implemented.

Logarithmic units, contexts, currency, localization, uncertainty, generalized rational powers, range-bearing native
floats, formula interpolation, and broader third-party stubs do not block `1.0.0` without concrete user evidence to the
contrary. They remain independent additions or future design decisions under [Deferred Features](#deferred-features).

### Preservation Roadmap

Apply the [Ruinenwert](https://github.com/jbboehr/doctrine-of-the-second-sun/blob/master/RUINENWERT.md) principles
through the following ordered work. These tasks should consolidate and enforce knowledge Yumemi already possesses rather
than create documentation or abstractions for their own sake:

1. **Established:** maintain [`invariants.md`](invariants.md) as the inventory of durable semantic rules, their reasons,
   representative enforcement, invalid alternatives, consequence classifications, and known enforcement gaps. Update it
   whenever a change deliberately alters one of those rules; do not promote incidental class structure into an
   invariant.
2. **Established:** maintain [`architecture.md`](architecture.md) as the durable component and replacement-boundary map.
   [`RuntimeDependencyDirectionTest`](../../tests/Architecture/RuntimeDependencyDirectionTest.php) enforces that runtime
   source cannot depend on PHPStan or Yumemi's PHPStan adapter without introducing a general-purpose layering framework.
3. **Established:** maintain the versioned [runtime conformance corpus](../../tests/Conformance/README.md) as portable,
   public black-box evidence for syntax, canonical reduction, normalization, dimensions, exact conversion, quantity and
   affine behavior, source spans, and semantic error categories.
   [`RuntimeConformanceTest`](../../tests/Conformance/RuntimeConformanceTest.php) validates the fixture schema and
   executes every case through public runtime APIs without freezing exception prose. Keep PHPStan-specific behavior in
   PHP tests where its native type system is part of the contract, and add cases only for representative semantic
   obligations rather than migrating implementation tests to satisfy the directory shape. Maintain the separate
   [release persistence corpus](../../tests/Compatibility/README.md) for PHP-specific native serialization and
   documented JSON shapes emitted by real tagged packages; do not mix implementation-dependent PHP bytes into the
   portable corpus.
4. **Established:** maintain the [compatibility policy](compatibility.md) as the classification of supported runtime
   APIs, PHPStan pseudo-types, diagnostics, configuration, grammar, persistent formats, integration contracts,
   provisional surfaces, and internal or generated details. Review it before each release and whenever a change alters
   the supported boundary; do not infer stability from PHP visibility or freeze human-readable diagnostic prose.
5. **Established:** use `composer test` for the complete PHPUnit suite without coverage, `composer analyse` for PHPStan,
   and `composer check` for the ordinary PHP/Composer local gate. `composer check:full` adds documentation, benchmark
   discovery, and release-style consumer verification for relevant changes and release preparation.
   `nix flake check --keep-going -L` is the authoritative reproducible normal gate and exposes logically distinct checks
   as independently cached derivations. A small setup-php baseline intentionally overlaps it. Mutation remains an
   explicit Nix package used by CI, while Xdebug branch coverage and the parser “probator” remain specialist workflows.
6. **Established:** maintain the [generated-artifact inventory](generated-artifacts.md) for `src/Parser/Parser.php` and
   `data/udunits2.php`, including editing authorities, known reproducible tool versions, provenance, licensing, consumer
   requirements, and byte-identical plus behavioral verification. Nix checks exact regeneration of both artifacts;
   update the inventory whenever their source, generator, provenance, or reproduction policy changes.
7. **Established:** maintain the [release and succession runbook](release-and-succession.md) as the manual procedure for
   release preparation, Composer-first local verification, authoritative CI and Nix checks, signed annotated tags,
   GitHub and Packagist publication, fork-first succession, exceptional direct transfer, and intentional freezing.
   Reverify service access and update mechanisms before each release; never store credentials or recovery material in
   the repository.
8. Record concise architectural decisions only when their rationale affects future work. Initial candidates are the
   shared runtime semantic authority, branded scalars versus exact value objects, definitional equivalence versus
   dimensional compatibility, affine point/delta separation, committed generated catalogs, and the separation of
   Apocrypha from the core package. Do not reconstruct a project diary.

Do not pursue this roadmap by splitting the semantic core into more packages, adding interfaces without replacement
scenarios, preserving exact error messages, moving all existing tests, or creating empty policy documents. The durable
knowledge and executable boundaries are the objective.

### Verification Roadmap

Verification work should prioritize independent evidence and algebraic invariants over additional examples that can
repeat the implementation's assumptions:

- Maintain the Nix-backed differential PHPUnit suite against the `udunits2` executable. It compares representative base,
  prefixed, accepted, compound, affine, alias, incompatible, unknown, and intentionally unsupported cases. Fixtures
  carry separate Yumemi and UDUNITS2 spellings where the parser dialects differ instead of assuming AST or syntax
  parity. Yumemi's expectations remain exact; UDUNITS2's six-significant-digit textual output is compared with a `5e-6`
  relative plus `5e-12` absolute tolerance. Ordinary PHPUnit runs skip the group when the external executable or
  matching XML database is unavailable, while `nix flake check` supplies both and requires the suite to pass.
- Maintain the deterministic finite generative PHPUnit suite for bounded expression ASTs, rational magnitudes,
  compatible unit pairs and triples, formatter modes, quantities, and affine points. It verifies reduction and
  normalization idempotence, parser/formatter round trips, conversion composition and reversal, rational and quantity
  arithmetic identities, and point difference/translation identities. Expression depth and exponents are bounded, and
  named data sets report the complete replayable input for every failure.
- Maintain the Eris property-test experiment for branded integer interval arithmetic. Normal PHPUnit runs use the fixed
  default `ERIS_SEED` from `phpunit.xml.dist`; an explicit environment value explores or replays another seed. Generated
  intervals range more widely than the exhaustive small-domain suite while retaining bounded widths so every concrete
  result can be enumerated as an independent hull oracle. Keep property testing supplementary: preserve deterministic
  examples for named boundaries and promote every discovered counterexample into a focused regression test.
- Maintain the manual coverage-guided “probator” target for unit expressions. It combines parser robustness checks with
  AST and runtime parser/formatter round-trip oracles, starts from the committed corpus under `probator/corpus/`, and
  writes its evolving corpus and crash artifacts beneath ignored `tmp/probator/` storage. Keep “probator” runs outside
  mandatory CI and promote every genuine finding into a focused deterministic regression test. The first campaign
  exposed canonical rendering that changed the precedence of negative numeric power bases; `Pow::toString()` now
  parenthesizes those bases, with integer and decimal regressions in `ParserTest`.
- Maintain the PHP 8.2 lowest-dependency and PHP 8.5 highest-dependency CI jobs. Both perform fresh Composer solves for
  released requirements and run PHPStan plus PHPUnit: the lowest job proves their declared lower bounds, while the
  highest job detects incompatibility with newly available releases inside those constraints. Direct requirements locked
  to development branches remain at their committed revisions because copied or generated integrations are tested
  against those exact inputs. Ordinary lock-file jobs verify one reproducible dependency snapshot and do not cover
  either released edge.
- Continue focused Xdebug branch audits rather than enforcing a global path-coverage floor. The focused `src/Registry`
  audit reached 98.95% branch and 98.65% line coverage after adding contract tests for malformed catalog shapes,
  transactional builder batches, and resilient introspection; the remaining outcomes are structurally unreachable under
  normal PHP array construction. The `PointQuantity` audit reached 100% of its 83 branches and 132 executable lines,
  with all 137 focused mutants killed. The catalog semantic-core audit reached 98.75% branch and 99.03% line coverage;
  `AffineDeltaUnitSynthesizer` is fully covered, while `UnitDefinitionClassifier` leaves only a nameless record excluded
  by the catalog-record contract. The importer/exporter audit reached 100% of 214 branches and 233 executable lines,
  added clean domain failures for unreadable and malformed XML, and verifies byte-identical regeneration from the real
  split UDUNITS2 database in the Nix-backed test group. The focused formatting audit reached 86.36% of 110 branches and
  96.00% of 150 executable lines across 156 relevant tests, added a shortest-codepoint symbol-selection contract, and
  killed 119 of 125 focused mutants; all six survivors were behaviorally equivalent. Canonical reduction makes the
  remaining renderer branches unreachable, while the untested name fallbacks require contradictory registry lookup and
  descriptor APIs. A complete-suite Xdebug run proved impractical once generative round trips took several minutes per
  data case, so this is explicitly a focused percentage; the complete PHPUnit suite remains the separate behavioral
  gate. The handwritten parser audit covered 99.31% of 145 branches and 98.91% of 183 executable lines across 330
  focused tests after excluding generated `Parser.php`. It found one diagnostic-excerpt off-by-one and killed 186 of 193
  focused mutants for 96% covered MSI. The remaining uncovered branch is the defensive `parse() === false` fallback
  excluded by the generated parser's success-or-throw contract. A 2026-08-10 parser resource-budget follow-up covered
  98.84% of 172 handwritten parser branches, reached 100% branch and line coverage in `Lexer`, and raised focused
  covered MSI from 82% to 86% by adding contracts for eager input rejection, separated nesting, depth recovery, and
  limit diagnostics. No runtime defect was found; remaining survivors are equivalent or defensive states, apart from a
  timeout that removes circular-resolution termination. Add tests only when an uncovered outcome is reachable and
  observably meaningful. Path coverage remains informational because combinations grow rapidly;
  `PointQuantity::__unserialize()` alone exposes 4,096 paths through compound payload validation.
- Triage Infection's escaped and timed-out mutants periodically before raising the MSI floor. Add contract-level
  assertions for observable survivors, record or ignore behaviorally equivalent mutations, distinguish deliberately
  unreachable defensive branches, and confirm that timeouts are explained by removed termination guards rather than
  ordinary performance failures. A focused 2026-08-09 `Rational` audit generated 547 mutants and reduced escaped mutants
  from 80 to 66, raising covered MSI from 85% to 87%. The added contracts cover decimal parsing, native integer
  boundaries, exact rounding, binary64 exponent-estimate correction, significand carry, binary64 boundaries, and strict
  underflow. The remaining survivors are equivalent normalization and zero fast paths, scaling by one, sign guards,
  casts, or exception prose. Four timeouts remove progress or termination from denominator reduction for terminating
  decimals. The audit found no runtime defect.
- A focused 2026-08-14 audit of the native binary-math resolver and its diagnostic rule generated 190 mutants and raised
  covered MSI from 83% to 95% by adding contracts for incomplete calls, native fallback ownership, bare constants,
  nonnumeric alternatives, complete Cartesian union results, and symmetric benevolent-union handling. In the final
  campaign, 181 mutants were killed. The nine survivors are behaviorally equivalent: six remove redundant `int|float`
  casts before native float-returning functions, two reverse coalescing operands that are equal or have only one
  non-null value by construction, and one changes loop control only after a bare `float` result has already subsumed the
  remaining branded-float alternatives. The audit found no additional implementation defect.
- Maintain the machine-checked inventory of stable public `yumemi.*` diagnostic identifiers. It proves that every public
  rule identifier is represented in the compatibility policy and PHPStan reference, records an emitting implementation
  and representative local-ignore fixture for every listed identifier, and explicitly classifies non-diagnostic
  first-party keys. Focused rule tests own the behavioral proof that each ignore suppresses the identifier it names.
  Configuration keys, result-cache metadata, and human-readable diagnostic prose remain outside the public diagnostic
  inventory.
- Maintain the isolated Roave Backward Compatibility Check against the latest stable tag. Its full-history CI checkout
  and `composer check:bc` entry point protect committed PHP signatures without constraining the main PHP 8.2-8.5
  dependency matrix. Classify findings through the compatibility policy, and require migration guidance plus narrow,
  temporary acknowledgement for a deliberate later-`0.x` break instead of disabling or broadly bypassing the check.

### Known Limitations And Risks

- Native `unit()`, `unit_factor()`, and `unit_to()` calls require complete constant unit expressions by default. Dynamic
  calls can retain native fallback types through local suppression or configuration; runtime object APIs remain the
  intentional dynamic path.
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
- Yumemi intentionally does not declare a Composer conflict with PHPStan versions older than 2.2.5 because runtime-only
  consumers may legitimately have an older analyzer installed. Extension users require PHPStan 2.2.5 or later; automatic
  registration in a project with an older version remains an unsupported integration and should produce clear setup
  guidance rather than making the runtime package uninstallable.
- Explicit integer/float casts and decimal `intval()`/`floatval()`/`doubleval()` conversions preserve native numeric
  brands and move a `unit_numeric_string` brand onto the resulting number. A non-decimal or dynamic `intval()` base
  leaves a branded numeric-string result unbranded. Implicit arithmetic and weak numeric coercion do not preserve a
  numeric-string brand; comparisons still require definitionally equivalent brands. `abs()`, `ceil()`, `floor()`, and
  `round()` preserve native numeric unit brands. Supported finite constant `round()` inputs retain exact result
  alternatives when precision and half-rounding mode are statically known; dynamic, non-finite, excessively broad, PHP
  8.4 directional-enum, and cross-era cases retain the generalized brand. Native `min()` and `max()` preserve a common
  definitionally equivalent brand across direct, array, and unpacked candidates, retain finite constant extrema, narrow
  known integer ranges, and report `yumemi.invalidUnitSelection` when a possible returning candidate is bare or
  differently branded. Native `sqrt()` transforms exact symbolic square units, retains finite nonnegative constants,
  generalizes negative or non-finite constants, and diagnoses branded units without an exact symbolic root. Native
  `fdiv()` follows division unit algebra; `fmod()` and `hypot()` require definitionally equivalent branded operands and
  diagnose mixed or incompatible calls. Native `intdiv()` applies quotient unit algebra to integer operands, retains
  exact constants and truncation-toward-zero ranges, and leaves zero-divisor and `PHP_INT_MIN / -1` throw analysis to
  PHPStan. Native `pow()` mirrors `**` for statically known integer exponents, including bounds, constant folding,
  integer ranges, overflow promotion, finite alternatives, and derived-unit overflow. Other casts and unsupported PHP
  built-ins can erase brands. Continue adding targeted integrations only for demonstrated workflows. Native `deg2rad()`,
  `rad2deg()`, direct and inverse trigonometry, and binary `atan2()` enforce the canonical angle, exact unscaled-ratio,
  and equivalent-operand contracts in the completed [angle-function design](native-angle-functions.md). Fractional or
  otherwise generalized native powers remain deferred because they require distinct correlation or approximation
  semantics. Exact runtime-object roots are supported through `Quantity::root()`.
- Native helpers accept finite alternatives only when every valid path produces one semantic result unit. Independent
  source and target alternatives lose value correlation, so conversion helpers validate the Cartesian product and fail
  closed if any pair is invalid. Quantity boundaries continue to preserve finite target unions.
- Lookup is case-sensitive. Short but valid prefix/symbol compositions such as `pa` (pico-are) and `PA` (peta-ampere)
  remain accepted while `Pa` is pascal; Yumemi does not special-case these catalog-valid ambiguities.
- Unit, dimension, and scientific-decimal exponents are bounded to `-10000` through `10000`; checked composition rejects
  larger effective powers before native integer overflow or unbounded GMP exponentiation.
- Dynamic runtime parsing now enforces one shared fixed budget before resolution: 4,096 input bytes, 256 non-whitespace
  lexical tokens, 64 nested parentheses, and 1,024 bytes in one identifier or numeric token. The input check precedes
  Doctrine Lexer's eager token allocation and the successful AST cache; token count bounds subsequent expression-tree
  work. Runtime, custom-registry, catalog, and PHPStan paths share the same policy and
  `ExpressionLimitExceededException` category. These bounds are defense in depth rather than a substitute for smaller
  application-specific limits at external boundaries.
- The UDUNITS2 importer still special-cases `cm2` syntax, and generated `prefixRegex` metadata is currently unused by
  resolution.
- Expression arithmetic reduces eagerly. The benchmark suite measures representative reduction and normalization, but no
  cross-machine regression floor or production-workload profile has established that this is a hot path.
- Paired helper-boundary benchmarks and local hardware-counter profiles identified repeated parsing as a concrete
  runtime cost. Before caching, repeated `Quantity::valueIn()` with a compound string target took about 17 times the
  wall time and 15 times the retired instructions of the equivalent pre-parsed target; string-based quantity
  construction took about 9 times both. Formatting, normalization, quantity parsing, point construction, and affine
  delta derivation showed the same parser-heavy behavior.
- Successful parser ASTs now use one process-local, exact-input LRU cache, while fully resolved expressions use a
  separate cache owned by each immutable `Units` context. Both retain at most 256 expressions no longer than 512 bytes.
  The AST cache additionally retains at most 16 KiB of source-input weight across all entries; each resolved cache
  permits at most 64 KiB. These weights bound represented input rather than exact PHP heap usage. Oversized inputs and
  all failures bypass caching. Immutable raw ASTs may be shared across registries, but resolved meaning never crosses a
  `Units` boundary. The AST budget is smaller because dense syntax trees and their source spans retain materially more
  memory per input byte than reduced expressions.
- On the same PHP 8.2 host after caching, warm compound `parse()` fell from about 55 to 0.23 microseconds, string and
  pre-parsed `Quantity::valueIn()` converged at about 4.5 and 4.2 microseconds, and string normalization converged with
  pre-parsed normalization at about 15 microseconds. Formatting fell from about 36 to 10 microseconds, point
  construction from about 49 to 2.7, affine delta derivation from about 34 to 1.9, and `parseQuantity()` from about 72
  to 33.
- Persistence validation is intentionally substantial. Representative quantity and point deserialization took about 205
  and 112 microseconds before caching and about 87 and 27 afterward. Restoration still revalidates normalized units,
  dimensions, origins, and scales; preserve those semantic seals rather than pursuing lower timings by weakening them.
- Representative rational arithmetic and decimal rendering remained below 4 microseconds, cached dimensions and
  compatibility below 0.4 microseconds, custom registry overlay construction below 0.4 milliseconds, and full-catalog
  description below 2 milliseconds. These measurements do not justify dedicated optimization work.
- Hardware-counter benchmarks depend on unreleased `phpbench-perfidious` adapter code and local Linux `perf_events`
  permissions; they are optional and intentionally excluded from CI.
- The opt-in end-to-end PHPStan benchmark isolates result-cache directories and separates bootstrap, ordinary scalar,
  branded native, runtime-object inference, annotation-promotion, and mixed workloads. Use several fixture sizes before
  attributing elapsed time to Yumemi: PHPStan container startup is substantial, scalar and branded fixtures are not
  structurally identical, each workload is one source file and therefore single-process, and local wall times are
  diagnostic rather than portable regression floors or project-scale throughput measurements.
- On the same PHP 8.2 host with 400 generated cases and isolated result caches, Yumemi-enabled startup took about 0.88
  seconds versus 0.86 without the extension, while ordinary scalar analysis took about 3.98 seconds with Yumemi versus
  3.77 without it. Focused branded workloads took about 1.2 seconds for PHPDoc type resolution, 2.28 for operators and
  ranges, 1.48 for `abs()`, 2.78 for `min()`/`max()`, 1.38 for `sqrt()`, 3.48 for the composite built-ins workload, and
  2.99 for native helpers. Combined native, quantity/point, annotation-promotion, and mixed workloads took about 4.98,
  3.49, 1.78, and 3.68 seconds respectively. The composite result is not an independent optimization target. These
  results are linear enough to reject a broad scaling defect, but identify extrema and helper analysis as the first
  candidates for deeper profiling.
- Dynamic return/expression inference and companion diagnostic rules both call the same `analyseCall()` methods for
  helpers, extrema, and roots. A focused 400-case extrema experiment safely memoized analysis by exact AST node and
  `Scope`, but moved the local median only from about 2.898 to 2.886 seconds (roughly 0.4%); the cache was therefore
  discarded. Do not apply node-level memoization to helpers or roots by analogy. Profile the helper path to identify a
  material repeated operation before adding cache state; root analysis is already comparatively cheap.
- The 2026-08-09 native-helper profiling pass measured a byte-identical 400-case pair at about 2.766 seconds without
  Yumemi and 2.989 seconds with it, placing the extension's helper-fixture cost near 222 milliseconds. Focused
  one-helper pairs attributed roughly 114 milliseconds each to `unit()` and `unit_factor()` and 124 milliseconds to
  `unit_to()`, so no helper is a singular hotspot. In a separate 20-case Xdebug profile, all helper inference and
  diagnostic entry points accounted for about 104 milliseconds of 3.61 instrumented seconds; parser calls accounted for
  49 milliseconds, argument lookup for 3.3, and finite-string extraction for 1.3. The rule and return extensions receive
  different `FiberScope` and `MutatingScope` wrappers, so an exact node-and-scope cache cannot share their analyses,
  while a node-only cache would risk stale scope-dependent types. Retain the controlled benchmark, but do not add helper
  cache state without a new profile identifying safely reusable material work.
- Dimensional analysis intentionally cannot distinguish semantically different quantities with the same dimension, such
  as gray and sievert.
- Exact catalog decimals for angles can normalize to large rationals; this is correct but can produce unwieldy display
  text.

### Deferred Features

- Logarithmic units
- Exact rational powers beyond integer-degree roots; approximate results require explicit precision and rounding
- Configurable range-loss behavior for native-float helpers such as `convertFloat()`, `unit_to()`, and `unit_factor()`;
  exact `Rational`, `Quantity`, and `PointQuantity` outputs now provide an explicit policy, while helper paths remain
  strict pending a separate input-and-intermediate-value contract
- GNU Units import
- Formula interpolation
- General preferred-basis optimization, compound-unit compaction, and authored custom prefixes remain deferred. The
  implemented [selection design](preferred-compact-unit-selection.md) keeps explicit profiles and named-family
  engineering compaction separate from presentation-only formatting.
- Additional convenience units only when a concrete integration establishes their semantics. A modern
  `typographic_pica`, basis points, frames, audio samples, voxels, and printer dots remain deferred rather than
  acquiring speculative bundled definitions.
- A separate strict-expression option for dynamic `Units`, `Quantity`, and `PointQuantity` boundaries if applications
  demonstrate a need beyond the native-helper policy. Their explicit runtime parsing role remains dynamic by default.
- A cache of derived `parseQuantity()` components only if production measurements show repeated parsing of complete
  quantity strings to be material. The shared syntax cache has already halved this path's cost; any additional cache
  must preserve fresh immutable `Quantity` results, exact constants, source spans, and registry-context ownership.
- Whitespace-normalized resolved-expression cache keys only if measured workloads show meaningful variation in otherwise
  equivalent input. The lexer ignores whitespace runs, so trimming outer whitespace and collapsing each internal run to
  one separator can preserve token boundaries; never delete internal whitespace, because `meter second` and
  `metersecond` are different expressions. Keep parser AST cache keys byte-exact because their half-open source spans
  refer to the original input, and retain cacheability limits based on the original byte length.
- A unified multiplicative/affine conversion-plan cache keyed by each immutable `Units` context. Current profiles do not
  justify it: cached string resolution keeps repeated conversion-factor and affine point paths comparatively small.
  Reconsider only if a production profile identifies conversion-plan construction as material after parse caching.
- An application-specific generator for a small requested set of native conversion-factor constants, with deterministic
  regeneration tests against the exact runtime engine. Do not generate every possible catalog pair; ordinary code should
  normally hoist `unit_factor()` outside repeated arithmetic.
- Consider retaining stable effective registry entries when profiles justify the allocation tradeoff. Avoid a separate
  unbounded per-name entry cache: unknown-name suggestion ranking inspects the complete catalog and could populate that
  cache with every entry after one failed lookup.
- Stable registry identifiers and an application resolver for serialized graphs containing values from several custom
  `Units` contexts. Native serialization currently supports the default context plus one dynamically scoped custom
  context through `Units::deserialize()` and rejects semantic drift. Broader ecosystem integrations remain deferred.
- Strict same-unit comparison variants and PHP object comparison operators unless a concrete use case appears
- Compare the local `Rational` implementation with
  [`brick/math`](https://github.com/brick/math) in a disposable spike before considering any dependency or
  representation change. Keep Yumemi's public API and conformance corpus fixed while comparing canonical reduction,
  decimal parsing and formatting, significant-digit rounding, exact roots, binary64 conversion and range policies,
  exception translation, public GMP numerator/denominator access, and released serialization bytes. Benchmark catalog
  generation, parsing, conversion, and numeric rendering; record memory and dependency effects; and count the adapter
  code that would remain. Prefer the local implementation unless the spike demonstrates a substantial net maintenance
  reduction with equivalent observable behavior and no material performance, persistence, or portability regression.
- Range-bearing native float types remain deferred to PHPStan's upstream
  [float-range design](https://github.com/phpstan/phpstan/issues/6963). PHPStan does not yet provide corresponding
  public PHPDoc syntax or an integer-range-equivalent core type, and its open design questions include endpoint
  inclusivity, binary floating-point bounds, infinities, underflow, and NaN. Such types would be valuable for bounded
  coordinates, nonnegative fractional durations and rates, and other continuously validated APIs, but Yumemi should not
  introduce a competing proprietary interval syntax or type model while the upstream contract remains unsettled.
- Third-party stub breadth and package-version maintenance are tracked in Yumemi Apocrypha rather than this core plan.

The broader feature comparison and intentionally deferred Pint-style capabilities remain in
[pint-parity.md](pint-parity.md).
