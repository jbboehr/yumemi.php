# Pint Feature Comparison

Snapshot date: 2026-07-29

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
NumPy integration, and serialization patterns. Much of that breadth is Python-specific or secondary to Yumemi's static
analysis goal.

The useful comparison is therefore not a single parity percentage. Yumemi has strong coverage of its chosen exact scalar
and PHPStan scope, partial coverage of general-purpose formatting and advanced unit semantics, and intentionally little
coverage of scientific-array and uncertainty ecosystems.

## Feature Analysis

### 1. Expression Model And Reduction

Status: **Done** | Importance: **P0** | Remaining difficulty: **S**

Yumemi represents constants, units, products, and integer powers explicitly. Reduction flattens products, combines
constants and powers, cancels inverse units, and orders factors deterministically. Structural equality and the public
seven-axis `Dimension` model no longer depend on formatted-string comparisons.

The remaining concern is performance under repeated analysis, not missing algebra for the supported multiplicative
model. Rational powers are a separate advanced feature.

### 2. Unit Parser

Status: **Done for the documented grammar** | Importance: **P0** | Remaining difficulty: **M**

The generated Bison parser supports identifiers, exact decimal and scientific constants, multiplication, division,
integer powers, grouping, Unicode middle dots, and signed superscript powers. Product precedence follows UDUNITS2.
Malformed syntax receives bounded caret diagnostics with byte spans.

The grammar intentionally recognizes some upstream forms that semantic layers reject. Pint-style `**`, source spans for
post-parse semantic errors, and arbitrary real powers remain possible extensions rather than gaps in the documented
language.

### 3. Registry And Default Catalog

Status: **Done for the current model** | Importance: **P0** | Remaining difficulty: **S/M**

Yumemi ships deterministic generated UDUNITS2 data with base and derived units, aliases, symbols, prefixes, explicit
plurals, and safe generated plurals. Lookup is case-sensitive, exact names precede prefix decomposition, and
introspection preserves spelling provenance and semantic capabilities.

Future work is primarily indexing and performance optimization for bulk introspection and formatting.

### 4. Custom Unit Definitions

Status: **Partial** | Importance: **P1** | Remaining difficulty: **M/L**

`UnitRegistryBuilder` supports mutable programmatic definitions and aliases over either the default catalog or an empty
registry. Each build creates an immutable snapshot, overlays correctly mask base records, and PHPStan can consume the
same registry through a configured factory.

Definition files and user-defined base dimensions outside the fixed seven SI axes are absent. Those features should be
added only when an application requires them; programmatic custom units already cover ordinary project-specific scales.

### 5. Quantity Creation

Status: **Done for exact inputs** | Importance: **P0** | Remaining difficulty: **S**

`Units::quantity()` accepts `int` or `Rational` magnitudes, and `parseQuantity()` parses exact constants from complete
quantity strings. Catalog scale remains in the symbolic unit unless the caller explicitly simplifies or converts.

Direct float construction is intentionally absent because it would undermine the exact input contract. Decimal strings
can be represented through `Rational::fromDecimalString()` or parsed quantity expressions.

### 6. Quantity Arithmetic

Status: **Done for multiplicative quantities** | Importance: **P0** | Remaining difficulty: **M**

Yumemi supports exact compatible-unit addition and subtraction, strict same-unit variants, multiplication, division,
integer powers, negation, comparisons, symbolic cancellation, and context checks. Addition and subtraction convert the
right operand into the left unit; multiplication and division preserve caller-selected symbolic units.

Absolute value and convenience predicates remain optional additions. Affine point-versus-difference arithmetic uses the
separate `PointQuantity` model described below.

### 7. Explicit Conversion And Compatibility

Status: **Done** | Importance: **P0** | Remaining difficulty: **S/M**

The runtime provides exact factors, exact value conversion, float conversion, dimensional compatibility, quantity
conversion, and native helpers. Explicit conversion supports exact affine scale-and-offset transforms; factor APIs
reject offset-dependent conversions.

Affine point and difference semantics now build on the same conversion boundary.

### 8. Normalization, Simplification, Base Units, And Root Units

Status: **Done for explicit definition substitution** | Importance: **P1** | Remaining difficulty: **M**

`normalize()` substitutes definitions without changing a quantity's stored magnitude. `simplify()` also folds resulting
scale into the magnitude. Explicit `to()` conversion requests a caller-selected target. These semantics are documented
and intentionally distinct.

Pint's preferred-unit and compact-unit selection are separate absent features. Yumemi should not alias `simplify()` to a
system-specific base-unit operation unless unit systems are designed first.

### 9. Dimensionality API

Status: **Done for SI dimensions** | Importance: **P0** | Remaining difficulty: **M/L**

`Dimension` exposes the seven SI axes, integer powers, arithmetic, equality, accessors, and deterministic strings.
`Units`, `Quantity`, and resolved expressions expose dimensions publicly.

The fixed vector cannot represent user-defined base dimensions and deliberately cannot distinguish semantic meanings
that share physical axes, such as gray and sievert.

### 10. Numeric Types And Output Policy

Status: **Done for the exact core** | Importance: **P1** | Remaining difficulty: **M**

`Rational` supports exact integer and decimal construction, arithmetic, truncating and exact integer output, all PHP 8.4
fixed-scale rounding modes, terminating-decimal output, and correctly rounded binary64 conversion with strict overflow
and underflow handling. Quantity extraction converts to the requested unit first.

Significant-digit and scientific-notation formatting remain absent. Approximate decimal arithmetic should remain a
separate explicit model rather than replacing rational storage.

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
canonical identity and spelling provenance.

The main remaining work is performance-oriented indexing, not unresolved lookup policy.

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

Status: **Absent** | Importance: **P2** | Remaining difficulty: **M/L**

Yumemi never chooses a more readable target automatically. Prefix selection and preferred derived units require
magnitude heuristics, user preferences, and system metadata. Explicit `to()` conversion remains predictable in the
meantime.

### 18. Constants

Status: **Partial** | Importance: **P2** | Remaining difficulty: **M**

UDUNITS2 constants are available through the catalog as dimensionless definitions. Decimal source values become exact
rationals representing the catalog's finite decimal, not symbolic irrational values.

A dedicated constants API and symbolic constants are absent. Documentation must continue distinguishing an exact stored
decimal approximation of pi from mathematical pi.

### 19. Comparisons, Equality, And Predicates

Status: **Done for quantity ordering** | Importance: **P1** | Remaining difficulty: **S/M**

`Quantity` provides exact compatible-unit equality, three-way comparison, and all ordered predicates. Incompatible
dimensions and registry contexts fail explicitly, and PHPStan diagnoses known invalid comparisons.

Convenience predicates such as `isZero()` or `isCompatibleWith()` may be useful. Strict same-unit comparison variants
remain low value because comparisons do not produce a unit-bearing result.

### 20. Math Functions

Status: **Partial** | Importance: **P2** | Remaining difficulty: **L**

Exact integer `pow()` exists for rational magnitudes, expressions, dimensions, quantities, and PHPStan inference.
Rational roots, approximate real powers, trigonometric functions, logarithms, and exponentials are absent.

An exact `root(int)` could succeed only when both the rational magnitude and normalized unit powers have exact roots.
Approximate functions need a separate precision and rounding contract.

### 21. Static Analysis With PHPStan

Status: **Done for the current core** | Importance: **P0** | Remaining difficulty: **M/L**

Yumemi resolves `unit_int<'...'>`, `unit_float<'...'>`, and `Quantity<'...'>`; infers native and object arithmetic;
validates construction, conversion, extraction, and comparisons; supports native conversion helpers; preserves finite
literal-string unions; configures custom registries; and provides stable diagnostics.

Remaining work is integration breadth: selected casts, built-ins, third-party stubs, more precise diagnostics, and
future advanced unit semantics. Dynamic strings intentionally fall back to unbranded types.

### 22. Function Boundary Checking

Status: **Done statically; runtime decorators absent** | Importance: **P1/P2** | Remaining difficulty: **M**

Ordinary PHPDoc parameters and returns enforce branded native and generic quantity types through PHPStan. Optional
`@yumemi-*` tags support extension-optional libraries, and standard PHPStan stubs support third-party APIs.

Pint-style runtime decorators or PHP attributes that convert arguments are absent. They should be introduced only if
static contracts and explicit runtime conversion prove insufficient.

### 23. Serialization

Status: **Absent** | Importance: **P2** | Remaining difficulty: **M**

There is no canonical JSON or persistence representation for `Quantity`. A design must choose whether to preserve
symbolic display syntax, how to represent exact rationals, and how stored values identify custom registry versions.

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

Status: **Deliberately absent** | Importance: **P3** | Remaining difficulty: **L**

Exchange rates are time-varying, jurisdictional, and application-specific. Currency should remain outside core. A custom
registry can represent fixed contractual relationships where appropriate.

### 28. Localization

Status: **Absent** | Importance: **P3** | Remaining difficulty: **M/L**

Formatting has no translated names, plural rules, or locale-aware number output. The formatter architecture can grow a
localization layer later without making locale part of expression identity.

### 29. Performance And Caching

Status: **Partial** | Importance: **P1** | Remaining difficulty: **M**

Resolvers and formatters cache several name, definition, conversion, and semantic lookups, and registries are immutable
after construction. PHPBench now covers representative cold and warm runtime workflows. Bulk catalog introspection still
performs repeated grouping and sorting, and expression operations still reduce eagerly.

Use repeated local benchmarks and real PHPStan or runtime profiles to identify hot paths before optimizing.
Catalog-build indexing remains a reasonable deferred optimization even without changing expression semantics.

### 30. Error Messages And Developer Experience

Status: **Partial but strong** | Importance: **P1** | Remaining difficulty: **M**

Syntax errors include bounded caret excerpts and source spans. Runtime exceptions distinguish unknown units,
incompatibility, unsupported syntax, affine factor misuse, logarithmic evaluation, context mismatch, and native range
loss. PHPStan supplies stable identifiers and operation-specific diagnostics.

Unknown-unit suggestions and source spans for post-parse semantic errors remain absent. Diagnostic identifiers may be
split further only where users need more precise suppression.

### 31. Documentation And Examples

Status: **Done for the current public surface** | Importance: **P1** | Remaining difficulty: **S/M**

The root README is a concise landing page, and mdBook contains focused concept, PHPStan, syntax, runtime, and catalog
guides. Public PHP examples execute under PHPUnit, PHPStan-relevant examples verify expected diagnostics, and mdBook is
built through Composer and Make.

Documentation must continue to evolve with behavior, but the missing infrastructure and organization from the original
comparison are now present.

### 32. Packaging, CI, And Release Hygiene

Status: **Done for development; release automation absent** | Importance: **P0/P1** | Remaining difficulty: **M**

Composer, Nix, treefmt, pre-commit hooks, PHP-CS-Fixer, PHPStan, PHPUnit, generated artifacts, GitHub Actions, and
Infection with enforced mutation-score floors are configured. Catalog and parser regeneration are documented. An
isolated consumer smoke test verifies runtime use plus automatic and manual PHPStan registration from a release-style
Composer archive.

The project still lacks a tagged release and release workflow. Lowest- and highest-dependency jobs may be useful after
the first release establishes a compatibility promise.

## Parity Matrix

| Feature                          | Yumemi status                      | Importance | Remaining difficulty |
| -------------------------------- | ---------------------------------- | ---------- | -------------------- |
| Expression model and reduction   | Done                               | P0         | S                    |
| Parser                           | Done for documented grammar        | P0         | M                    |
| Default catalog                  | Done                               | P0         | S/M                  |
| Custom unit definitions          | Partial                            | P1         | M/L                  |
| Quantity creation                | Done for exact inputs              | P0         | S                    |
| Quantity arithmetic              | Done for multiplicative quantities | P0         | M                    |
| Explicit conversion              | Done                               | P0         | S/M                  |
| Normalization and simplification | Done                               | P1         | M                    |
| Dimensionality API               | Done for SI dimensions             | P0         | M/L                  |
| Numeric output                   | Done for exact core                | P1         | M                    |
| Formatting                       | Partial                            | P1         | M/L                  |
| Names, prefixes, and plurals     | Done                               | P1         | S/M                  |
| Affine units                     | Conversion only                    | P1         | L/XL                 |
| Logarithmic units                | Recognized, not evaluable          | P3         | XL                   |
| Pint contexts                    | Absent                             | P2         | XL                   |
| Unit systems                     | Absent                             | P2         | L                    |
| Preferred and compact units      | Absent                             | P2         | M/L                  |
| Constants                        | Partial                            | P2         | M                    |
| Comparisons                      | Done for quantities                | P1         | S/M                  |
| Math functions                   | Integer powers only                | P2         | L                    |
| PHPStan                          | Done for current core              | P0         | M/L                  |
| Function boundaries              | Static contracts only              | P1/P2      | M                    |
| Serialization                    | Absent                             | P2         | M                    |
| Collections and ecosystems       | Native-brand interoperability only | P3         | L/XL                 |
| Measurements and uncertainty     | Absent                             | P3         | M/L                  |
| Buckingham Pi theorem            | Absent                             | P3         | M/L                  |
| Currency                         | Deliberately absent                | P3         | L                    |
| Localization                     | Absent                             | P3         | M/L                  |
| Performance and caching          | Partial                            | P1         | M                    |
| Errors and developer UX          | Partial but strong                 | P1         | M                    |
| Documentation                    | Done for current surface           | P1         | S/M                  |
| Packaging and CI                 | Done except release automation     | P0/P1      | M                    |

## Strategic Conclusions

Yumemi should continue to optimize for shared runtime and static semantics rather than headline parity with Pint. Its
strongest choices remain string unit expressions, generated catalog data, exact rational conversion, explicit registry
contexts, and native PHPStan brands alongside exact quantity objects.

The highest-value Pint gaps are those that improve ordinary PHP workflows: selected serialization, better formatting,
and integrations proven by actual applications. Contexts, nonlinear units, uncertainty, and scientific-array features
should remain independent decisions rather than a presumed route to parity.

See [planning.md](planning.md) for the current ordering of work.
