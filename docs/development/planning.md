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
- focused public Markdown and selected source PHPDoc whose executable PHP and PHPStan examples are verified through
  Akashi, using stable diagnostic identifiers and child processes only where authored namespaces require isolation.

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
- a separate advisory workflow exercises PHP 8.2 on macOS and Windows, including the release-style package consumers,
  for pushes to `master` and `develop`, pull requests targeting `master`, and manual dispatches; it complements but does
  not weaken the required Linux matrix
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
in reviewable slices; Slice 2 migrated the root and scalar-preserving unary mappers without changing their local failure
policy. Slice 3 migrated the binary-math Cartesian paths and retained ordinary-union precedence over benevolence. The
final audit slice migrated unary angle mapping while deliberately leaving `atan2()` and the remaining resolver families
local. No further migration is warranted under the audited contract.

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

## Rational Backend Evaluation

The dated [rational backend evaluation](rational-backend-evaluation.md) compared the current direct-GMP implementation
with `brick/math` 0.19.1 over GMP, BCMath, and its native-PHP calculator. Brick provides a credible path to operation
without mandatory extensions, and 85,453 deterministic observable comparisons confirmed compatible primitives across
bounded arithmetic, fixed-scale rounding, exact decimals, roots, and ordinary finite float output. It is not a drop-in
replacement for Yumemi's significant-digit output, strict binary64 range policy, truncating integer conversion, decimal
grammar, public GMP values, JSON, exception taxonomy, or released serialization.

Retain direct GMP for now. Common Brick-over-GMP operations were generally several times slower, portable arbitrary-root
calculation was substantially slower, and a selectable Yumemi backend would add two execution paths without removing the
project-specific numeric policy. If GMP installation becomes a demonstrated adoption barrier, reconsider one
Brick-`BigInteger` representation in a deliberate `0.2` compatibility project, with GMP used only as Brick's optional
accelerator and with explicit migration of the released persistence and public GMP surfaces.

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

#### Next Release: 0.2.0

After the [documentation-detail cleanup](documentation-detail-audit-2026-09-06.md), the next planned work is preparation
of `0.2.0` from `develop`, in reviewable slices with review before each commit:

1. Review changes since `v0.1.1` against the compatibility policy and finish the upgrade guide, including rational
   component access and changed runtime and PHPStan behavior.
2. Prepare the changelog, installation/status prose, Composer branch alias, and required lock/Nix metadata. Identify the
   tested optional native-extension release or commit.
3. Follow the release runbook for dependency audit, full Composer and Nix checks, committed API comparison, historical
   persistence, archive inspection, and CI verification of the exact release commit before signed-tag publication.
4. Verify publication and clean installation, capture immutable `v0.2.0` persistence fixtures from the published
   package, and remove temporary compatibility acknowledgements once the new tag becomes the comparison baseline.

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
- Continue [focused Xdebug branch audits](branch-coverage.md) instead of enforcing a global path-coverage floor. Add
  tests when an uncovered outcome is reachable and observably meaningful. Record the focused scope when a complete
  Xdebug run is impractical, and keep the ordinary full suite as the separate behavioral gate.
- Triage [escaped and timed-out mutants](mutation-testing.md#investigating-an-escape) before raising the MSI floor. Add
  contracts for observable survivors, distinguish equivalent or unreachable states, and explain timeouts caused by
  removed termination guards. The [recorded audits](branch-coverage.md#recorded-audits) retain campaign counts,
  findings, and verification limits, including the rational and native binary-math investigations.
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
  implemented. Native runtime helpers can be aligned with that registry by calling the process-wide
  `Units::setDefault()` during synchronous bootstrap; instance APIs remain preferable when an application uses several
  registries concurrently.
- The opt-in `@yumemi-*` parser integration depends on internal PHPStan parser services and may conflict with another
  parser-replacing extension.
- Yumemi intentionally does not declare a Composer conflict with PHPStan versions older than 2.2.5 because runtime-only
  consumers may legitimately have an older analyzer installed. Extension users require PHPStan 2.2.5 or later; automatic
  registration in a project with an older version remains an unsupported integration and should produce clear setup
  guidance rather than making the runtime package uninstallable.
- Supported casts and native numeric functions preserve or transform brands as described in the
  [PHPStan reference](../pages/reference/phpstan.md#casts-and-scalar-functions). Other casts, unsupported built-ins, and
  implicit numeric-string coercion can erase brands. Add integrations only for demonstrated workflows. Fractional or
  generalized native powers remain deferred because they require distinct correlation or approximation semantics. Exact
  runtime-object roots are supported through `Quantity::root()`.
- Native helpers accept finite alternatives only when every valid path produces one semantic result unit. Independent
  source and target alternatives lose value correlation, so conversion helpers validate the Cartesian product and fail
  closed if any pair is invalid. Quantity boundaries continue to preserve finite target unions.
- Lookup is case-sensitive. Short but valid prefix/symbol compositions such as `pa` (pico-are) and `PA` (peta-ampere)
  remain accepted while `Pa` is pascal; Yumemi does not special-case these catalog-valid ambiguities.
- Unit, dimension, and scientific-decimal exponents are bounded to `-10000` through `10000`; checked composition rejects
  larger effective powers before native integer overflow or unbounded GMP exponentiation.
- Runtime and PHPStan parsing share fixed [resource limits](../pages/reference/unit-syntax.md#resource-limits),
  including catalog and custom definitions. These bounds are defense in depth; applications still need smaller limits
  appropriate to their external inputs. Preserve the
  [bounded-work invariant](invariants.md#unit-expression-work-is-bounded) when changing parsing or resolution.
- The UDUNITS2 importer still special-cases `cm2` syntax.
- Expression arithmetic reduces eagerly. The benchmark suite measures representative reduction and normalization, but no
  cross-machine regression floor or production-workload profile has established that this is a hot path.
- Repeated string parsing and conversion use bounded caches. Keep resolved meaning within its owning `Units` context,
  bypass failures, and preserve restoration validation. The [cache policy](architecture.md#cache-retention) records
  retention budgets; [runtime measurements](benchmarking.md#runtime-parsing-and-persistence) explain the tradeoff.
- PHPStan profiles did not justify additional inference caches. Exact node-and-scope memoization had negligible benefit,
  and sharing helper results across different scopes would risk stale types. Reconsider only after a controlled profile
  identifies material work that can safely be reused. Preserve the isolated benchmark controls and
  [memoization evidence](benchmarking.md#phpstan-inference-and-memoization).
- Preferred and compact selection measurements did not justify another selection cache. Revisit after a production
  profile identifies selection as material; retain the
  [first-use and repeated-use comparison](benchmarking.md#preferred-and-compact-selection).
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
- Strict same-unit comparison variants unless a concrete use case appears
- Removing mandatory GMP remains deferred until supported users demonstrate a material installation or deployment
  barrier. The completed [backend evaluation](rational-backend-evaluation.md) found that Brick offers a viable portable
  integer representation but does not justify the current performance, compatibility, persistence, and adapter costs.
- Range-bearing native float types remain deferred to PHPStan's upstream
  [float-range design](https://github.com/phpstan/phpstan/issues/6963). PHPStan does not yet provide corresponding
  public PHPDoc syntax or an integer-range-equivalent core type, and its open design questions include endpoint
  inclusivity, binary floating-point bounds, infinities, underflow, and NaN. Such types would be valuable for bounded
  coordinates, nonnegative fractional durations and rates, and other continuously validated APIs, but Yumemi should not
  introduce a competing proprietary interval syntax or type model while the upstream contract remains unsettled.
- Third-party stub breadth and package-version maintenance are tracked in Yumemi Apocrypha rather than this core plan.

The broader feature comparison and intentionally deferred Pint-style capabilities remain in
[pint-parity.md](pint-parity.md).
