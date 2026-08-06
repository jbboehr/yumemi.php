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

The generic [Ruinenwert](ruinenwert.md) guidance informs long-term decisions about conformance evidence, generated
artifacts, replacement boundaries, and recoverability without becoming a separate feature roadmap.

### Ruinenwert Profile

- **Durable core:** the grammar, parser AST, expression and dimension models, unit registry and resolution semantics,
  exact rational arithmetic, normalization, and conversion rules.
- **Replaceable adapters:** PHPStan extension APIs, command-line presentation, documentation tooling, CI, and catalog
  acquisition are expected to decay faster than the semantic core.
- **Preserved generated artifacts:** `src/Parser/Parser.php` and `data/udunits2.php` remain consumable in a checkout;
  their grammar, importer, source provenance, and deterministic regeneration paths remain available alongside them.
- **Conformance evidence:** public documentation examples, regression and property tests, the UDUNITS2 differential
  suite, bounded generated-expression tests, consumer fixtures, and release-style archive checks exercise behavior from
  several independent directions.
- **Observable contracts:** public runtime and PHPStan APIs, `yumemi.*` diagnostic identifiers, serialized formats, unit
  syntax, and documented numeric policies require deliberate compatibility decisions rather than incidental preservation
  of every internal class.
- **Local recovery path:** the Nix development shells, Composer and Make targets, and `nix flake check` keep essential
  generation, analysis, testing, packaging, and documentation work executable from a checkout.

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
  helper expressions, finite object-boundary unions, and optional `@yumemi-*` promotion;
- a separately versioned [Yumemi Apocrypha](https://github.com/jbboehr/yumemi-apocrypha.php) package for curated
  third-party stubs, leaving the generic `@yumemi-*` mechanism in core;
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

## Remaining Issues And Deferred Work

The multiplicative and affine-point runtimes and the PHPStan native/object paths are usable. Remaining work is mostly
developer-experience improvement, selected API and formatting polish, and explicitly deferred advanced features.

### Pre-Release Checklist

- Before creating the first release tag, remove `:dev-master` from the README installation command; after Packagist
  imports the tag, verify that the unqualified command installs the tagged release.
- Publish an honest compatibility policy that distinguishes supported runtime and PHPStan APIs, provisionally public
  extension points, tool-specific integration surfaces, serialized formats, generated implementation details, and
  explicitly internal code.
- Record the local release and succession procedure, including artifact verification, signing, publication services,
  required accounts and permissions without secret values, package transfer, compatible forks, and intentional project
  freezing.

### Preservation Roadmap

Apply the [Ruinenwert](ruinenwert.md) principles through the following ordered work. These tasks should consolidate and
enforce knowledge Yumemi already possesses rather than create documentation or abstractions for their own sake:

1. Create `docs/development/invariants.md`. For each durable semantic rule, record its reason, current enforcement, a
   tempting invalid alternative, and whether a violation is a correctness defect, compatibility break, or accepted
   tradeoff. Cover at least the shared runtime authority, analysis-only native brands, definitional equivalence versus
   compatible conversion, affine points and deltas, exactness boundaries, deterministic parsing and formatting, source
   spans, strict native helper expressions, immutable registry semantics, generated-data reproducibility, serialization,
   and stable diagnostic identifiers.
2. Extract the durable architecture from this planning document into `docs/development/architecture.md`. Identify the
   semantic core, dependency direction, replaceable adapters, generated inputs and outputs, and likely decay points. Add
   a focused architecture test that prevents runtime namespaces from acquiring dependencies on `src/PHPStan` without
   introducing a general-purpose layering framework solely for that assertion.
3. Add a small public black-box conformance corpus under an appropriate `tests/Conformance/` structure. Use versioned,
   data-driven fixtures where syntax, canonical forms, reduction, normalization, dimensions, exact conversion, affine
   plans, and stable error categories can be represented faithfully. Keep PHPStan-specific behavior in PHP tests where
   its native type system is part of the contract, and do not migrate existing tests merely to satisfy the directory
   shape.
4. Define the compatibility surface before the first release. State which runtime APIs, PHPStan pseudo-types,
   diagnostics, configuration, grammar, serialization formats, and numeric policies users may rely upon, while keeping
   tool-specific adapters and internal classes replaceable. Do not imply stability for every public PHP declaration or
   freeze human-readable diagnostic prose.
5. Provide conventional local entry points such as `composer test`, `composer analyse`, and `composer check`, composed
   from the existing focused commands. Audit CI so its authoritative checks invoke the same local workflows instead of
   encoding otherwise unavailable procedure; retain Nix as the reproducible environment rather than the only record of
   how checks run.
6. Consolidate a generated-artifact inventory for `src/Parser/Parser.php` and `data/udunits2.php`, recording
   authoritative inputs, generators, known tool versions, provenance, licensing, consumer requirements, and
   byte-identical or semantic reproduction checks. Link existing generation documentation instead of repeating it.
7. Complete the release and succession runbook described by the pre-release checklist. Reuse the existing legal,
   stewardship, packaging, and archive documentation; identify credential storage and transfer procedures without
   storing credentials in the repository.
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
  split UDUNITS2 database in the Nix-backed test group. Audit formatting next. Reassess parser-diagnostic branch
  coverage after reviewing “probator” findings rather than duplicating the current parser investigation. Add tests only
  when an uncovered outcome is reachable and observably meaningful. Path coverage remains informational because
  combinations grow rapidly; `PointQuantity::__unserialize()` alone exposes 4,096 paths through compound payload
  validation.
- Triage Infection's escaped and timed-out mutants periodically before raising the MSI floor. Add contract-level
  assertions for observable survivors, record or ignore behaviorally equivalent mutations, distinguish deliberately
  unreachable defensive branches, and confirm that timeouts are explained by removed termination guards rather than
  ordinary performance failures.
- After the first public release establishes a compatibility baseline, run an API compatibility checker such as Roave
  Backward Compatibility Check against the latest release tag. Treat intentional breaking changes through an explicit
  versioning policy instead of weakening or bypassing the check.

### Near-Term Work

- Preserve native unit brands through a conservative first set of scalar transformations. Establish PHPStan's current
  behavior and extension points, then cover explicit integer/float casts and clearly unit-preserving functions such as
  `abs()`, `ceil()`, `floor()`, and `round()`. Preserve semantic units while allowing numeric literals or bounds to
  generalize when exact refinement is not sound. Define `abs(PHP_INT_MIN)` against the existing integer-overflow policy,
  and defer `min()`, `max()`, `intdiv()`, roots, and trigonometric functions because they require separate unit,
  correlation, or exponent semantics. This work must not depend on future constant-valued or range-bearing float types.
- Split remaining broad PHPStan diagnostic identifiers only where users need more precise suppression. Native helpers
  now distinguish dynamic and ambiguous unit expressions from invalid constant calls.

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
- Casts and unsupported PHP built-ins can erase native unit brands. Add targeted extensions only for demonstrated
  workflows rather than trying to model every built-in preemptively.
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
- A separate strict-expression option for dynamic `Units`, `Quantity`, and `PointQuantity` boundaries if applications
  demonstrate a need beyond the native-helper policy. Their explicit runtime parsing role remains dynamic by default.
- A bounded parse cache scoped to each immutable `Units` context, and a unified multiplicative/affine conversion-plan
  cache keyed by that context. Benchmark helper-heavy workloads before fixing cache sizes or eviction policy.
- An application-specific generator for a small requested set of native conversion-factor constants, with deterministic
  regeneration tests against the exact runtime engine. Do not generate every possible catalog pair; ordinary code should
  normally hoist `unit_factor()` outside repeated arithmetic.
- Optimize bulk catalog introspection by pre-grouping canonical aliases, symbols, and plurals during generation, then
  lazily caching an index per immutable registry. Build that index from the typed effective-entry lookup so composite
  registries preserve whole-layer precedence and base aliases continue to follow overlay replacements. The same index
  should serve canonical/symbol formatter lookups so newly constructed formatters do not repeat catalog scans;
  expression resolution remains in `UnitResolver`. The index may also retain stable entries to avoid repeated wrapper
  allocation. Avoid a separate unbounded per-name entry cache: unknown-name suggestion ranking inspects the complete
  catalog and could populate that cache with every entry after one failed lookup.
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

## Current Architecture Sketch

```text
UDUNITS2 XML -> Udunits2CatalogImporter -> PhpCatalogExporter -> data/udunits2.php
data/udunits2.php -> Udunits2UnitRegistry (catalog records only)
UnitResolver -> UnitRegistry::findEntry() -> prebuilt Expr or AstConverter (catalog defs/prefixes) -> Expr
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
