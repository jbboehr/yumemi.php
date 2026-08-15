# Pint Feature Comparison

Snapshot date: 2026-08-09

This document compares Yumemi with Python's Pint library to identify useful capabilities and deliberate differences. It
is a feature comparison, not the project roadmap. Current priorities, architectural risks, and deferred work belong in
[planning.md](planning.md).

Yumemi is not intended to reproduce every Python or NumPy integration in Pint. Its distinguishing goal is static
dimensional analysis for ordinary PHP values, backed by the same exact runtime engine used by `Quantity`.

## Rating Scale

Status labels:

- **Done:** implemented well enough to rely on for the stated scope.
- **Partial:** useful behavior exists, but a meaningful portion of the feature remains.
- **Absent:** no meaningful implementation exists.
- **Deliberately absent:** excluded from the current product direction unless a concrete use case changes it.

Importance measures value to Yumemi rather than importance to Pint:

- **P0:** foundational to Yumemi's purpose.
- **P1:** important for a broadly useful first stable version.
- **P2:** valuable after the core is stable.
- **P3:** specialized or ecosystem-dependent.

Difficulty estimates the remaining work: **S**, **M**, **L**, or **XL**.

## Executive Summary

Yumemi now has a reliable scalar multiplicative runtime, exact affine points and differences, a configurable formatter,
custom registries, and a substantial PHPStan extension for branded native values, `Quantity<'...'>`, and
`PointQuantity<'...'>`. The core architecture remains sound: one parser, registry, expression model, and conversion
engine serve both runtime and static analysis.

Pint remains much broader in runtime convenience, unit systems, contexts, localization, nonlinear units, measurements,
NumPy integration, and ecosystem persistence patterns. Much of that breadth is Python-specific or secondary to Yumemi's
static analysis goal.

The useful comparison is therefore not a single parity percentage. Yumemi has strong coverage of its chosen exact scalar
and PHPStan scope, partial coverage of general-purpose formatting and advanced unit semantics, and intentionally little
coverage of scientific-array and uncertainty ecosystems.

## Feature Analysis

### 1. Expression Model And Reduction

Status: **Done** | Importance: **P0** | Remaining difficulty: **S**

Yumemi represents constants, units, products, and integer powers explicitly. Reduction flattens products, combines
constants and powers, cancels inverse units, and orders factors deterministically. Public expressions support exact
positive integer-degree roots while retaining integral powers and explicit definition substitution. Structural equality
and the hybrid SI-plus-extension `Dimension` model no longer depend on formatted-string comparisons.

The remaining concern is performance under repeated analysis, not missing algebra for the supported multiplicative
model. Rational powers are a separate advanced feature.

### 2. Unit Parser

Status: **Done for the documented grammar** | Importance: **P0** | Remaining difficulty: **M**

The generated Bison parser supports identifiers, exact decimal and scientific constants, multiplication, division,
integer powers, grouping, Unicode middle dots, and signed superscript powers. Product precedence follows UDUNITS2.
Malformed syntax receives bounded caret diagnostics with byte spans.

The grammar intentionally recognizes some upstream forms that semantic layers reject. AST locations now survive
post-parse lookup and unsupported-semantic resolution, including through aliases and stored definitions, so runtime
exceptions and PHPStan parse results can identify the caller-written name or construct. Pint-style `**` and arbitrary
real powers remain possible extensions rather than gaps in the documented language.

### 3. Registry And Default Catalog

Status: **Done for the current model** | Importance: **P0** | Remaining difficulty: **S/M**

Yumemi ships deterministic generated UDUNITS2 data with base and derived units, aliases, symbols, prefixes, explicit
plurals, and safe generated plurals. A small authored supplement adds nominal raster samples and exact document units
without changing the generated catalog's provenance. Lookup is case-sensitive, exact names precede prefix decomposition,
and introspection preserves spelling provenance and semantic capabilities.

Generated indexes now remove repeated whole-catalog grouping from bulk introspection. Additional registry optimization
should follow a concrete profile rather than assuming indexing remains unfinished.

### 4. Custom Unit Definitions

Status: **Done for programmatic registries** | Importance: **P1** | Remaining difficulty: **M**

`UnitRegistryBuilder` supports mutable programmatic definitions and aliases over either the default catalog or an empty
registry. Each build creates an immutable snapshot, overlays correctly mask base records, and PHPStan can consume the
same registry through a configured factory.

Definition files remain absent. `baseUnit()` adds user-defined primitive dimensions while retaining the fixed SI vector
and canonical sparse extension powers, and ordinary definitions derive related units from the declared base. Currency is
the acceptance case, but exchange-rate acquisition and money policy remain outside core. Programmatic registries now
cover both project-specific SI scales and independent application dimensions.

### 5. Quantity Creation

Status: **Done for exact inputs** | Importance: **P0** | Remaining difficulty: **S**

`Units::quantity()` accepts `int` or `Rational` magnitudes, and `parseQuantity()` parses exact constants from complete
quantity strings. Catalog scale remains in the symbolic unit unless the caller explicitly simplifies or converts.

Direct float construction is intentionally absent because it would undermine the exact input contract. Decimal strings
can be represented through `Rational::fromDecimalString()` or parsed quantity expressions.

### 6. Quantity Arithmetic

Status: **Done for multiplicative quantities** | Importance: **P0** | Remaining difficulty: **M**

Yumemi supports exact compatible-unit addition and subtraction, strict same-unit variants, multiplication, division,
integer powers, exact positive integer-degree roots, negation, absolute value, exact zero testing, comparisons, symbolic
cancellation, and context checks. Addition and subtraction convert the right operand into the left unit; multiplication,
division, roots, and absolute value preserve caller-selected symbolic units unless the caller explicitly normalizes or
simplifies first.

Additional convenience predicates remain optional. Affine point-versus-difference arithmetic uses the separate
`PointQuantity` model described below.

### 7. Explicit Conversion And Compatibility

Status: **Done** | Importance: **P0** | Remaining difficulty: **S/M**

The runtime provides exact factors, exact value conversion, float conversion, dimensional compatibility, quantity
conversion, and native helpers. Explicit conversion supports exact affine scale-and-offset transforms; factor APIs
reject offset-dependent conversions. Exact `Rational`, `Quantity`, and `PointQuantity` outputs use strict binary64 range
handling by default and can explicitly select signed infinity or signed zero through `FloatRangePolicy`; native-float
helper paths remain strict.

Affine point and difference semantics now build on the same conversion boundary.

### 8. Normalization, Simplification, Base Units, And Root Units

Status: **Done for explicit definition substitution** | Importance: **P1** | Remaining difficulty: **M**

`normalize()` substitutes definitions without changing a quantity's stored magnitude. `simplify()` also folds resulting
scale into the magnitude. Explicit `to()` conversion requests a caller-selected target. These semantics are documented
and intentionally distinct.

Preferred-unit profiles apply explicit application targets by dimension, while `toCompact()` selects an engineering
prefix within one named unit family. Yumemi does not alias `simplify()` to a system-specific base-unit operation. The
[selection design](preferred-compact-unit-selection.md) keeps both operations separate without requiring a unit-system
model.

### 9. Dimensionality API

Status: **Done** | Importance: **P0** | Remaining difficulty: **S**

`Dimension` exposes the seven SI axes, sparse named extension axes, integer powers, arithmetic, equality, accessors, and
deterministic strings. `Units`, `Quantity`, and resolved expressions expose dimensions publicly, while registry metadata
associates one canonical base unit with each custom primitive dimension.

Dimensions deliberately cannot distinguish semantic meanings that share the same axes, such as gray and sievert. That
would require a separate quantity-kind model rather than another dimension representation.

### 10. Numeric Types And Output Policy

Status: **Done for the exact core** | Importance: **P1** | Remaining difficulty: **M**

`Rational` supports exact integer and decimal construction, arithmetic, truncating and exact integer output, all PHP 8.4
rounding modes for fixed-scale and significant-digit output, terminating-decimal output, plain and scientific notation,
and correctly rounded binary64 conversion. Exact float output uses strict overflow and underflow handling by default and
can explicitly select signed infinity or signed zero through `FloatRangePolicy`. Quantity and point extraction converts
to the requested unit first. Approximate decimal arithmetic remains a separate potential model rather than replacing
rational storage.

### 11. Formatting And Display Units

Status: **Partial** | Importance: **P1** | Remaining difficulty: **M/L**

Formatting supports preserved, canonical, or symbol names; ASCII or Unicode typography; three dimensionless styles; and
fraction or negative-power division. Unicode numeric output round-trips through the parser, and reusable formatters
cache resolved names.

Pint additionally supports rich magnitude formatting, HTML, LaTeX, siunitx, localization, compact units, and broader
presets. Yumemi should add those only as concrete output targets emerge.

### 12. Aliases, Prefixes, Plurals, Symbols, And Case Sensitivity

Status: **Done for the declared catalog** | Importance: **P1** | Remaining difficulty: **S/M**

Generated aliases, explicit and generated plurals, symbols, and longest-prefix-first decomposition use exact catalog
metadata. Exact names win before prefix decomposition, lookup remains case-sensitive, and introspection exposes
canonical identity and spelling provenance. A typed effective entry centralizes composite-layer precedence while
retaining both a materialized prebuilt alias and its catalog metadata when one layer intentionally supplies both.
Generated name indexes and dynamically composed custom overlays make repeated introspection constant-time after registry
construction without changing lookup policy.

### 13. Offset And Affine Units

Status: **Done for exact points and differences** | Importance: **P1** | Remaining difficulty: **M**

Yumemi exactly converts UDUNITS2 and custom affine definitions. `PointQuantity` represents coordinate points; point
subtraction returns an ordinary multiplicative `Quantity`, and compatible quantities translate points. Catalog
generation and custom registry construction synthesize explicit `delta_*` names and `Δ°C` / `Δ°F` symbols.

Affine names themselves remain outside expression algebra, prefixes, powers, ordinary `Quantity`, and native brands.
This is intentional: accepting them directly would make point-versus-difference meaning ambiguous. Possible future work
is limited to demonstrated conveniences or static integrations, not a second affine arithmetic model.

### 14. Logarithmic Units

Status: **Recognized but not evaluable** | Importance: **P3** | Remaining difficulty: **XL**

The catalog classifies logarithmic definitions and preserves their metadata for introspection and diagnostics. Runtime
expression and conversion APIs reject evaluation with operation-specific exceptions.

Nonlinear conversion, reference values, compound behavior, and approximation policy make this unsuitable for the exact
rational core until a real application justifies a separate model.

### 15. Pint Contexts

Status: **Absent** | Importance: **P2** | Remaining difficulty: **XL**

Pint contexts permit transformations that are not ordinarily dimensionally compatible, such as wavelength to frequency
through the speed of light. Yumemi's `Units` object is a registry identity and must not be confused with this feature.

Parameterized transformation graphs would complicate static inference and exactness. Defer them until an important
workflow requires cross-dimensional conversion.

### 16. Unit Systems

Status: **Absent** | Importance: **P2** | Remaining difficulty: **L**

The catalog contains units from several systems but no metadata describing MKS, CGS, SI, US, or imperial preferences.
System-aware base-unit or preferred-unit conversion would require explicit system metadata and collision policy.

### 17. Preferred And Compact Units

Status: **Partial** | Importance: **P2** | Remaining difficulty: **M/L**

Yumemi implements explicit dimension-matched application preferences through `PreferredUnitProfile` and
`Quantity::toPreferred()`, plus exact engineering-prefix compaction through `Quantity::toCompact()`. The
[design record](preferred-compact-unit-selection.md) deliberately limits compaction to one caller-selected named family
and avoids catalog-wide scoring, a general preferred-basis optimizer, and implicit system selection. Explicit `to()`
conversion remains the predictable choice when one static target is known.

### 18. Constants

Status: **Partial** | Importance: **P2** | Remaining difficulty: **M**

UDUNITS2 constants are available through the catalog as dimensionless definitions. Decimal source values become exact
rationals representing the catalog's finite decimal, not symbolic irrational values.

A dedicated constants API and symbolic constants are absent. Documentation must continue distinguishing an exact stored
decimal approximation of pi from mathematical pi.

### 19. Comparisons, Equality, And Predicates

Status: **Done for quantity and point ordering and compatibility** | Importance: **P1** | Remaining difficulty: **S**

`Quantity` and `PointQuantity` provide exact compatible-unit equality, three-way comparison, and all ordered predicates.
Incompatible dimensions and registry contexts fail explicitly, and PHPStan diagnoses known invalid comparisons.

`isZero()` tests the exact magnitude independently of its unit. `Quantity::isCompatibleWith()` and
`PointQuantity::isCompatibleWith()` return `true` only for values in the same `Units` context with compatible
dimensions; incompatible dimensions and different contexts return `false` without conversion. Strict same-unit
comparison variants remain low value because comparisons do not produce a unit-bearing result.

### 20. Math Functions

Status: **Partial** | Importance: **P2** | Remaining difficulty: **L**

Exact integer `pow()` exists for rational magnitudes, expressions, dimensions, and quantities. PHPStan preserves branded
units through native `**` and `pow()` when every possible exponent is a statically known integer. `Rational::root()`,
`Dimension::root()`, and `Quantity::root()` support exact positive integer-degree roots while keeping unit powers
integral and symbolic substitution explicit. Native branded `sqrt()` transforms an exact symbolic square unit and
diagnoses non-rootable brands. Native `deg2rad()` and `rad2deg()` preserve canonical angle units; `sin()`, `cos()`, and
`tan()` map canonical radians to unscaled ratios, while `asin()`, `acos()`, and `atan()` map exact unscaled ratios to
canonical radians. Binary `atan2()` maps definitionally equivalent branded operands to canonical radians, completing the
[angle-function design](native-angle-functions.md). Fractional and other generalized powers, approximate runtime-object
powers and trigonometry, logarithms, and exponentials remain absent. Approximate runtime-object functions need a
separate precision and rounding contract.

### 21. Static Analysis With PHPStan

Status: **Done for the current core** | Importance: **P0** | Remaining difficulty: **M/L**

Yumemi resolves `unit_int<'...'>`, `unit_float<'...'>`, `unit_numeric_string<'...'>`, `Quantity<'...'>`, and
`PointQuantity<'...'>`; infers native, quantity, and point operations; validates construction, conversion, extraction,
and comparisons; supports native conversion helpers with strict, semantically unambiguous expressions; preserves finite
unions at runtime-object boundaries; configures custom registries; and provides stable diagnostics.

The separate [Yumemi Apocrypha](https://github.com/jbboehr/yumemi-apocrypha.php) package supplies curated third-party
stubs without expanding core's dependency graph. Branded integer constants and PHPStan integer-range intersections now
propagate through supported arithmetic, allowing exact bounds to distinguish safe integer results, guaranteed float
overflow, and mixed outcomes. Integer-brand extraction inspects only direct values and immediate intersection
constraints, so callables, arrays, and other compound types retain nested brands without collapsing into integers.
Explicit integer/float casts and `abs()`, `ceil()`, `floor()`, and `round()` preserve native brands. `min()` and `max()`
preserve one definitionally equivalent brand across every possible returning candidate and retain known finite extrema,
while native `sqrt()` transforms exact symbolic square units. Known finite native float values now survive construction,
conversion factors, direct conversion, supported arithmetic, casts, and modeled scalar functions when the result remains
finite, without being mistaken for exact rational quantities. Remaining work is branded float ranges, additional
built-ins justified by real workflows, more precise diagnostics, and future advanced unit semantics. Dynamic
native-helper expressions are diagnosed by default; explicit runtime parsing APIs remain dynamic, while deliberately
suppressed or configured native-helper calls retain their declared unbranded fallback type.

### 22. Function Boundary Checking

Status: **Done statically; runtime decorators absent** | Importance: **P1/P2** | Remaining difficulty: **M**

Ordinary PHPDoc parameters and returns enforce branded native and generic quantity types through PHPStan. Optional
`@yumemi-*` tags support extension-optional libraries. Yumemi Apocrypha applies those semantics to selected third-party
APIs while owning their stubs and compatibility matrices.

Pint-style runtime decorators or PHP attributes that convert arguments are absent. They should be introduced only if
static contracts and explicit runtime conversion prove insufficient.

### 23. Serialization

Status: **Done for current value objects** | Importance: **P2** | Remaining difficulty: **S/M**

`Rational`, `Dimension`, `Quantity`, `PointQuantity`, and catalog descriptor value objects expose exact JSON and compact
debug representations. Versioned native serialization preserves exact rational state and symbolic unit syntax. Default
quantities restore through the shared default `Units`; custom-context values restore through `Units::deserialize()`,
which validates unit semantics against the selected immutable registry.

One serialized graph may contain default values and values from one custom context. Graphs containing several custom
contexts still need stable registry identifiers and an application resolver. JSON intentionally represents value state
rather than embedding or reconstructing a registry.

### 24. Arrays, Collections, And Scientific Ecosystems

Status: **Partial through native brands** | Importance: **P3** | Remaining difficulty: **L/XL**

Branded native values remain ordinary scalars and therefore interoperate naturally with PHP arrays and ordinary APIs.
Yumemi has no quantity-array type, vectorized conversion, or dedicated Scientific PHP integration.

Targeted PHPStan extensions for real libraries are preferable to a speculative generic collection framework.

### 25. Measurements And Uncertainty

Status: **Absent** | Importance: **P3** | Remaining difficulty: **M/L**

Uncertainty propagation is a distinct mathematical layer. It should be a separate package or value type composed with
`Quantity`, not embedded in the unit engine.

### 26. Buckingham Pi Theorem

Status: **Absent** | Importance: **P3** | Remaining difficulty: **M/L**

The public `Dimension` model supplies useful prerequisites, but no Buckingham Pi helpers exist. Add them only if Yumemi
develops a scientific-computing audience.

### 27. Currency

Status: **Supported through custom registries; bundled data deliberately absent** | Importance: **P3** | Remaining
difficulty: **M**

Bundled rates remain inappropriate because exchange rates are time-varying and application-specific. An application may
model the conventional `currency` extension dimension by choosing one primitive currency and defining the others through
exact rates in an immutable registry snapshot. Rate sources, effective times, bid/ask spreads, fees, and monetary
rounding remain outside Yumemi; this facility provides dimensional checking and exact declared conversion, not a
complete money model.

### 28. Localization

Status: **Absent** | Importance: **P3** | Remaining difficulty: **M/L**

Formatting has no translated names, plural rules, or locale-aware number output. The formatter architecture can grow a
localization layer later without making locale part of expression identity.

### 29. Performance And Caching

Status: **Partial** | Importance: **P1** | Remaining difficulty: **M**

Resolvers and formatters cache name, definition, derived conversion, and semantic lookups, and registries are immutable
after construction. PHPBench covers representative cold and warm runtime workflows plus full-catalog introspection.
Generated alias/symbol/plural and primitive-dimension indexes eliminate repeated catalog grouping and whole-registry
dimension scans. Disjoint composites extend those indexes while retrying previously unresolved aliases; shadowing
composites rebuild the effective name index lazily. The validated bundled catalog is cached per process, while custom
catalog paths remain dynamically loaded and validated. Expression operations still reduce eagerly.

Paired helper-boundary benchmarks and local hardware-counter profiles identified repeated parsing as the material
runtime cost. Successful parser ASTs now use one process-local exact-input LRU cache, and fully resolved expressions use
a separate LRU cache owned by each immutable `Units` context. Both retain at most 256 expressions no longer than 512
bytes. The AST cache additionally retains at most 16 KiB of source-input weight across all entries; each resolved cache
permits at most 64 KiB. These weights bound represented input rather than exact PHP heap usage. Oversized inputs and
failures bypass caching, preserving fresh source diagnostics, while registry-independent immutable ASTs may be shared
without allowing resolved meaning to cross contexts. Warm string and pre-parsed quantity conversion and normalization
now perform comparably. The AST budget is smaller because dense syntax trees and their source spans retain materially
more memory per input byte than reduced expressions. Complete `parseQuantity()` strings still rebuild their derived
components; cache those only if a production profile makes that remaining work material. The existing resolved-string
cache already keeps repeated multiplicative and affine conversions comparatively small, so a separate pairwise
conversion-plan cache remains deferred until a production profile demonstrates additional need.

### 30. Error Messages And Developer Experience

Status: **Partial but strong** | Importance: **P1** | Remaining difficulty: **M**

Syntax errors and post-parse lookup or unsupported-semantic errors include source spans; malformed syntax additionally
receives a bounded caret excerpt. Runtime exceptions distinguish unknown units, incompatibility, unsupported syntax,
affine factor misuse, logarithmic evaluation, context mismatch, and native range loss. PHPStan supplies stable
identifiers and operation-specific diagnostics.

All authored Yumemi exceptions implement one marker interface, including wrappers around the corresponding built-in PHP
exception families. Unexpected failures crossing PHPStan extension entry points are attributed to Yumemi and include an
actionable issue link without rewriting expected PHPStan internal failures.

Unknown-unit errors carry bounded structured suggestions under a complete deterministic ranking shared by runtime and
PHPStan diagnostics. Equivalent immutable registries produce identical suggestion order regardless of insertion or
composite-layer enumeration. Post-parse lookup and unsupported-semantic errors retain the caller's source span,
including when resolution descends through aliases or stored definitions. Diagnostic identifiers may be split further
only where users need more precise suppression.

### 31. Documentation And Examples

Status: **Done for the current public surface** | Importance: **P1** | Remaining difficulty: **S/M**

The root README is a concise landing page, and mdBook contains focused concept, PHPStan, syntax, runtime, and catalog
guides. Akashi discovers public PHP examples for execution under PHPUnit and verifies PHPStan-relevant examples against
their expected diagnostics. The mdBook build and link check remain available through Composer and Make.

Documentation must continue to evolve with behavior, but the missing infrastructure and organization from the original
comparison are now present.

### 32. Packaging, CI, And Release Hygiene

Status: **Done for development; release automation absent** | Importance: **P0/P1** | Remaining difficulty: **M**

Composer, Nix, treefmt, pre-commit hooks, PHP-CS-Fixer, PHPStan, PHPUnit, generated artifacts, GitHub Actions, and
Infection with enforced mutation-score floors are configured. Catalog and parser regeneration are documented. A
Nix-backed differential suite compares representative conversions against the `udunits2` executable, and a deterministic
generative suite exercises bounded expression, quantity, and point identities. An isolated consumer smoke test verifies
runtime use plus automatic and manual PHPStan registration from a release-style Composer archive. The separately
versioned Yumemi Apocrypha project verifies curated upstream integrations without adding their dependencies or
maintenance surface to core. The normal matrix covers PHP 8.2 through PHP 8.5. Fresh Composer solves on PHP 8.2 and PHP
8.5 verify the declared lower bounds and newest permitted releases, respectively. Direct development-branch tools remain
at their lock-file revisions because committed generated or copied integrations are verified against those exact inputs.

The project has an initial tagged 0.1 release and a manual release and fork-first succession runbook, but still lacks an
automated publication workflow.

## Parity Matrix

| Feature                          | Yumemi status                      | Importance | Remaining difficulty |
| -------------------------------- | ---------------------------------- | ---------- | -------------------- |
| Expression model and reduction   | Done                               | P0         | S                    |
| Parser                           | Done for documented grammar        | P0         | M                    |
| Default catalog                  | Done                               | P0         | S/M                  |
| Custom unit definitions          | Done for programmatic registries   | P1         | M                    |
| Quantity creation                | Done for exact inputs              | P0         | S                    |
| Quantity arithmetic              | Done for multiplicative quantities | P0         | M                    |
| Explicit conversion              | Done                               | P0         | S/M                  |
| Normalization and simplification | Done                               | P1         | M                    |
| Dimensionality API               | Done                               | P0         | S                    |
| Numeric output                   | Done for exact core                | P1         | M                    |
| Formatting                       | Partial                            | P1         | M/L                  |
| Names, prefixes, and plurals     | Done                               | P1         | S/M                  |
| Affine units                     | Done for exact points/differences  | P1         | M                    |
| Logarithmic units                | Recognized, not evaluable          | P3         | XL                   |
| Pint contexts                    | Absent                             | P2         | XL                   |
| Unit systems                     | Absent                             | P2         | L                    |
| Preferred and compact units      | Partial; explicit operations done  | P2         | M/L                  |
| Constants                        | Partial                            | P2         | M                    |
| Comparisons                      | Done for quantities and points     | P1         | S/M                  |
| Math functions                   | Integer powers and exact roots     | P2         | L                    |
| PHPStan                          | Done for current core              | P0         | M/L                  |
| Function boundaries              | Static contracts only              | P1/P2      | M                    |
| Serialization                    | Done for current value objects     | P2         | S/M                  |
| Collections and ecosystems       | Native-brand interoperability only | P3         | L/XL                 |
| Measurements and uncertainty     | Absent                             | P3         | M/L                  |
| Buckingham Pi theorem            | Absent                             | P3         | M/L                  |
| Currency                         | Custom registries; rates unbundled | P3         | M                    |
| Localization                     | Absent                             | P3         | M/L                  |
| Performance and caching          | Partial                            | P1         | M                    |
| Errors and developer UX          | Partial but strong                 | P1         | M                    |
| Documentation                    | Done for current surface           | P1         | S/M                  |
| Packaging and CI                 | Done except release automation     | P0/P1      | M                    |

## Strategic Conclusions

Yumemi should continue to optimize for shared runtime and static semantics rather than headline parity with Pint. Its
strongest choices remain string unit expressions, generated catalog data, exact rational conversion, explicit registry
contexts, and native PHPStan brands alongside exact quantity objects.

The highest-value remaining work is that which improves ordinary PHP workflows without displacing Yumemi's static
analysis focus: extending unit-brand preservation only for demonstrated native scalar workflows, maintaining strong
diagnostics, and supporting integrations proven by actual applications through Yumemi Apocrypha. Broader formatting and
registry resolution for serialized graphs remain useful only when concrete output or multi-context persistence
requirements justify them. Contexts, nonlinear units, uncertainty, and scientific-array features should remain
independent decisions rather than a presumed route to parity.

See [planning.md](planning.md) for the current ordering of work.
