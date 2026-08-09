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
- a reduced symbolic expression model, Bison parser, hybrid SI/extension `Dimension`, and derived-unit normalization;
- a generated UDUNITS2 catalog with exact aliases, plurals, prefixes, introspection, and deterministic regeneration;
- mutable custom-registry construction producing immutable snapshots, with one typed effective entry per exact lookup so
  composite overlays select a whole layer before exposing prebuilt expressions or catalog metadata while legacy lookup
  overrides remain compatible;
- exact multiplicative and affine scale-and-offset conversion, synthesized affine-difference units, and point
  coordinates;
- exact `Quantity` construction, parsing, arithmetic, comparison, conversion, normalization, simplification, and output;
- exact `PointQuantity` conversion, translation, difference, comparison, and output;
- versioned native serialization, exact JSON representations, compact debug output, and scoped custom-registry
  deserialization for runtime value objects;
- configurable ASCII and Unicode formatting with catalog-aware names and fraction or negative-power division;
- native `unit_int` / `unit_float` and object `Quantity<'...'>` / `PointQuantity<'...'>` PHPStan types with arithmetic
  inference, branded integer constants and ranges, overflow-aware bounds, diagnostics, custom registries, strict native
  helper expressions, finite object-boundary unions, explicit numeric-cast and common scalar-function brand
  preservation, and optional `@yumemi-*` promotion;
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
- Nix flake checks pass
- GitHub Actions tests PHP 8.2 through PHP 8.5, plus a PHP 8.2 lowest-dependency installation
- PHPBench covers representative cold and warm runtime workflows; CI smoke-tests benchmark discovery without timing
  floors, while an optional Linux Perfidious profile captures local `perf_events` counters
- Infection runs separate CI campaigns against all handwritten runtime source and the in-process PHPStan adapter tests,
  with respective total and covered MSI floors of 86% and 85%; the generated parser remains excluded
- a separate Xdebug development shell supports [focused, local branch and path coverage audits](branch-coverage.md)
  without adding their cost to CI or `nix flake check`; branch and path percentages currently have no enforced floor
- isolated consumer fixtures install a mirrored Composer package, verify automatic and manual PHPStan registration, and
  run against release-style `composer archive` output in CI; Apocrypha owns the separate upstream-package matrices and
  release-style verification for curated integrations

## PHPStan Model And Status

Yumemi intentionally has native and exact-object presentation layers over the same unit engine:

| Layer                   | Magnitude model                           | Primary audience                               |
| ----------------------- | ----------------------------------------- | ---------------------------------------------- |
| Runtime `Quantity`      | Exact `Rational` magnitude                | Exact multiplicative conversion and arithmetic |
| Runtime `PointQuantity` | Exact `Rational` coordinate               | Affine points, translation, and differences    |
| PHPStan branded values  | Native PHP `int` / `float` plus an `Expr` | Existing application code using native data    |

The native path introduces no runtime wrapper; the object paths retain exact `Rational` state. All reuse the runtime
parser, resolver, registry, reducer, dimensions, formatter, and conversion engine. Multiplicative unit identity remains
a reduced Yumemi `Expr`; point identity additionally retains a named coordinate origin and difference scale.

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
rounded coefficient in plain or scientific form. A future policy API may allow callers to request IEEE infinity or zero
on float range loss; the default exact-to-native boundary should remain strict.

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

### Pre-Release Checklist

- Before creating the first release tag, remove `:dev-master` from the README and public installation instructions;
  after Packagist imports the tag, verify that the unqualified command installs the tagged release.
- Review the established [compatibility policy](compatibility.md) against the intended first release, then publish it
  with the tag without broadening support beyond the documented and tested surface.
- Follow the established [release and succession runbook](release-and-succession.md), including artifact verification,
  signed-tag publication, service checks, tagged-package installation, and the documented response to partial
  publication failures.

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
   obligations rather than migrating implementation tests to satisfy the directory shape.
4. **Established:** maintain the [compatibility policy](compatibility.md) as the classification of supported runtime
   APIs, PHPStan pseudo-types, diagnostics, configuration, grammar, persistent formats, integration contracts,
   provisional surfaces, and internal or generated details. Review it before each release and whenever a change alters
   the supported boundary; do not infer stability from PHP visibility or freeze human-readable diagnostic prose.
5. **Established:** use `composer test` for the complete PHPUnit suite without coverage, `composer test:coverage` for
   the existing PCOV CI run, `composer analyse` for PHPStan, and `composer check` for the ordinary PHP/Composer local
   gate. `composer check:full` adds documentation, benchmark discovery, and release-style consumer verification for
   relevant changes and release preparation. CI invokes the same focused Composer scripts instead of duplicating their
   tool commands. Mutation, Xdebug branch coverage, the parser “probator,” and the Nix-backed UDUNITS2 differential
   remain explicit specialist workflows; Nix remains the reproducible environment rather than the only record of how
   checks run.
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
- Maintain the PHP 8.2 lowest-dependency CI job, which uses `composer update --prefer-lowest --prefer-stable` followed
  by PHPStan and PHPUnit. Ordinary lock-file jobs verify only one dependency snapshot and do not prove the lower bounds
  declared in `composer.json`.
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
  excluded by the generated parser's success-or-throw contract. Add tests only when an uncovered outcome is reachable
  and observably meaningful. Path coverage remains informational because combinations grow rapidly;
  `PointQuantity::__unserialize()` alone exposes 4,096 paths through compound payload validation.
- Triage Infection's escaped and timed-out mutants periodically before raising the MSI floor. Add contract-level
  assertions for observable survivors, record or ignore behaviorally equivalent mutations, distinguish deliberately
  unreachable defensive branches, and confirm that timeouts are explained by removed termination guards rather than
  ordinary performance failures.
- After the first public release establishes a compatibility baseline, run an API compatibility checker such as Roave
  Backward Compatibility Check against the latest release tag. Treat intentional breaking changes through an explicit
  versioning policy instead of weakening or bypassing the check.

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
- Explicit integer/float casts and `abs()`, `ceil()`, `floor()`, and `round()` preserve native unit brands. Native
  `min()` and `max()` preserve a common definitionally equivalent brand across direct, array, and unpacked candidates,
  narrow known integer extrema, and report `yumemi.invalidUnitSelection` when a possible returning candidate is bare or
  differently branded. Native `sqrt()` transforms exact symbolic square units and diagnoses branded units without an
  exact symbolic root. Other casts and unsupported PHP built-ins can erase brands. Continue adding targeted integrations
  only for demonstrated workflows; `intdiv()`, generalized native powers, and trigonometric functions remain deferred
  because they require distinct unit, correlation, or exponent semantics. Exact runtime-object roots are supported
  through `Quantity::root()`.
- Native helpers accept finite alternatives only when every valid path produces one semantic result unit. Independent
  source and target alternatives lose value correlation, so conversion helpers validate the Cartesian product and fail
  closed if any pair is invalid. Quantity boundaries continue to preserve finite target unions.
- Lookup is case-sensitive. Short but valid prefix/symbol compositions such as `pa` (pico-are) and `PA` (peta-ampere)
  remain accepted while `Pa` is pascal; Yumemi does not special-case these catalog-valid ambiguities.
- Unit, dimension, and scientific-decimal exponents are bounded to `-10000` through `10000`; checked composition rejects
  larger effective powers before native integer overflow or unbounded GMP exponentiation.
- Dynamic runtime parsing currently has no library-level expression-length, token-count, nesting-depth, or numeric-size
  budget beyond exponent bounds and ordinary process limits. Use replayable “probator” findings and focused stress cases
  to determine whether explicit limits are necessary before selecting arbitrary thresholds; applications accepting
  untrusted expressions should impose appropriate input limits in the meantime.
- The UDUNITS2 importer still special-cases `cm2` syntax, and generated `prefixRegex` metadata is currently unused by
  resolution.
- Expression arithmetic reduces eagerly. The benchmark suite measures representative reduction and normalization, but no
  cross-machine regression floor or production-workload profile has established that this is a hot path.
- Paired helper-boundary benchmarks and local hardware-counter profiles identify repeated parsing as a concrete runtime
  cost. On the same PHP 8.2 host, repeated `Quantity::valueIn()` with a compound string target took about 17 times the
  wall time and 15 times the retired instructions of the equivalent pre-parsed target; string-based quantity
  construction took about 9 times both. Direct warm string conversion-factor and affine point-conversion subjects
  remained in the same low-single-digit-microsecond range as the pre-parsed quantity control because
  `UnitConversionResolver` already caches resolved strings.
- A broader runtime survey found the same parsing cost at other public boundaries. On that host, formatting a compound
  string took about 36 microseconds versus 7.5 for a retained expression, normalization took about 50 microseconds
  versus 15, `parseQuantity()` took about 72 microseconds, and constructing a point took about 49 microseconds.
  Function-level profiles attributed the string-formatting difference primarily to parsing rather than rendering.
- Affine delta derivation is a narrower repeated-work candidate: resolving the delta unit for a warmed coordinate name
  took about 34 microseconds and 720,000 retired instructions, accounting for most of repeated point construction.
  `deltaUnit()` parses the requested coordinate spelling during linearization and then parses the synthesized expression
  even after ordinary conversion resolution is warm.
- Persistence validation is intentionally substantial. Representative quantity and point deserialization took about 205
  and 112 microseconds respectively because restoration reparses and revalidates normalized units, dimensions, origins,
  and scales. Preserve those semantic seals; first measure how much the general parsing and semantic caches remove
  before considering persistence-specific optimization.
- Representative rational arithmetic and decimal rendering remained below 4 microseconds, cached dimensions and
  compatibility below 0.4 microseconds, custom registry overlay construction below 0.4 milliseconds, and full-catalog
  description below 2 milliseconds. These measurements do not justify dedicated optimization work.
- Hardware-counter benchmarks depend on unreleased `phpbench-perfidious` adapter code and local Linux `perf_events`
  permissions; they are optional and intentionally excluded from CI.
- Dimensional analysis intentionally cannot distinguish semantically different quantities with the same dimension, such
  as gray and sievert.
- Exact catalog decimals for angles can normalize to large rationals; this is correct but can produce unwieldy display
  text.

### Deferred Features

- Logarithmic units
- Exact rational powers beyond integer-degree roots; approximate results require explicit precision and rounding
- Configurable alternatives to the current strict float policy, which rejects non-finite input, overflow to infinity,
  and nonzero exact results that underflow to zero
- GNU Units import
- Formula interpolation
- Preferred/compact unit selection and broader formatting presets
- Additional convenience units only when a concrete integration establishes their semantics. A modern
  `typographic_pica`, basis points, frames, audio samples, voxels, and printer dots remain deferred rather than
  acquiring speculative bundled definitions.
- A separate strict-expression option for dynamic `Units`, `Quantity`, and `PointQuantity` boundaries if applications
  demonstrate a need beyond the native-helper policy. Their explicit runtime parsing role remains dynamic by default.
- A bounded successful-parse-work cache scoped to each immutable `Units` context. Paired helper-boundary benchmarks now
  justify this work, but a cache containing only the resolved `Expr` returned by `Units::parse()` would leave repeated
  symbolic formatting and `parseQuantity()` parsing untouched. Evaluate one immutable parsed-syntax record with lazy
  symbolic and registry-resolved forms, or another design that safely shares the parser result across those boundaries.
  Define capacity and eviction explicitly, preserve source spans, do not cache failures, and use the
  string-versus-object benchmark controls to verify each affected API independently.
- Cache successful affine delta derivation by coordinate spelling if it remains material after the shared parse-work
  cache. Preserve registry-context ownership and exact synthesized syntax; do not store failures or weaken point
  construction's coordinate-unit validation.
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
- Constant-valued native unit floats. Branded integer literals now use an internal `UnitConstantIntegerType` and the
  public intersection spelling `3&unit_int<'meter'>`; no separate `unit_const_int` pseudo-type is needed. A future
  `UnitConstantFloatType` may implement PHPStan's `ConstantScalarType`, but retaining a known binary float would not
  make that value mathematically exact. Float constants would be particularly useful for literal conversion ratios such
  as points, twips, pixels, or EMUs per inch without discarding their scalar values.
- Range-bearing native float types are a separate, more custom follow-up because PHPStan does not provide corresponding
  public float-range PHPDoc syntax or an integer-range-equivalent core type. They would be particularly valuable for
  bounded coordinates such as latitude and longitude, nonnegative fractional durations and rates, and other APIs that
  validate continuous intervals. Their arithmetic must define sound behavior for infinities, underflow, and NaN rather
  than treating them as integer ranges with different endpoints.
- Third-party stub breadth and package-version maintenance are tracked in Yumemi Apocrypha rather than this core plan.
- A possible `unit_numeric_string<'...'>` PHPStan type for numeric values that cross string-oriented framework
  boundaries, such as Laravel configuration, environment values, request parameters, headers, and serialized scalar
  fields. It should remain a subtype of `numeric-string`, carry the same unit expression as native brands, and require
  explicit construction or parsing. Arithmetic, coercion, casts, and conversion into `unit_int` / `unit_float` need a
  sound policy before implementation; package stubs should not introduce the type speculatively. This type would not
  cover complete dimension-bearing strings such as `10px` or formatted values such as `3:45`, because neither is a PHP
  numeric string. Parser-aware quantity strings, if eventually justified, require a distinct design.

The broader feature comparison and intentionally deferred Pint-style capabilities remain in
[pint-parity.md](pint-parity.md).
