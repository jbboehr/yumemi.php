# Yumemi Semantic Invariants

This document records the rules that define Yumemi independently of its current class layout. It is maintainer-facing:
the public guides explain how to use the library, while this inventory explains which apparent simplifications would
change its meaning.

The implementation and tests linked below are representative enforcement points, not an exhaustive index. When they
disagree with an invariant, do not silently change whichever side is easiest. Determine whether the implementation is
wrong, the invariant has deliberately changed, or the document has become stale.

## Classification

Each invariant identifies the consequence of violating it:

- **Correctness defect:** the implementation no longer satisfies Yumemi's intended semantics.
- **Compatibility break:** observable behavior or a supported integration contract changes and must follow the project's
  compatibility policy.
- **Accepted tradeoff:** a documented limitation or policy could change deliberately without contradicting the semantic
  core, although tests and public documentation may still require updates.

One change may have more than one classification. Compatibility classifications follow the
[compatibility policy](compatibility.md), which must be reviewed whenever a change affects a supported or provisional
surface.

## One Runtime Semantic Authority

**Invariant.** Runtime and PHPStan behavior must derive unit meaning from the same parser, registry, expression model,
dimensions, reduction, normalization, and conversion rules. The PHPStan extension may adapt those results into PHPStan
types, but it must not maintain an independent catalog or incompatible unit algebra.

**Reason.** Separate implementations would eventually disagree about accepted syntax, aliases, dimensions, exact scales,
affine behavior, or canonical forms. Static acceptance would then cease to predict runtime behavior.

**Representative enforcement.** [`Units`](../../src/Units.php) assembles the runtime pipeline.
[`UnitExpressionParser`](../../src/PHPStan/UnitExpressionParser.php) delegates PHPStan parsing, dimensions, and
normalization to that facade, while [`UnitExpressionAlgebra`](../../src/PHPStan/UnitExpressionAlgebra.php) combines the
same expression and dimension objects.
[`RuntimeDependencyDirectionTest`](../../tests/Architecture/RuntimeDependencyDirectionTest.php) prevents runtime source
from importing PHPStan or Yumemi's PHPStan adapter.
[`UnitExpressionAlgebraTest`](../../tests/PHPStan/UnitExpressionAlgebraTest.php) and the PHPStan integration fixtures
exercise the semantic bridge.

**Invalid shortcut.** Reimplementing a small unit table, parser, conversion table, or dimension algebra inside
`src/PHPStan` because calling the runtime pipeline appears inconvenient.

**Classification.** Divergent results are a correctness defect. Deliberately replacing the shared semantics is also a
compatibility break.

## Scalar Brands Exist Only During Analysis

**Invariant.** `unit_int<'...'>`, `unit_float<'...'>`, and `unit_numeric_string<'...'>` describe ordinary PHP `int`,
`float`, and numeric-string values. Their unit identity exists only in PHPStan. They are not wrappers, runtime
subclasses, or hidden metadata attached to a scalar. A numeric-string brand crosses into numeric computation only
through an explicit integer or float cast, or through the corresponding `intval()`, `floatval()`, or `doubleval()`
conversion with decimal semantics; implicit coercion does not retain the brand.

Native `array_sum()` therefore aggregates only direct `unit_int` and `unit_float` values with one definitionally
equivalent unit. Native `array_product()` composes direct numeric brands only for sealed, statically known shapes; a
bare integer or float factor is dimensionless, but a branded numeric string still requires an explicit cast. Neither
operation treats implicit string coercion as proof that the numeric-string brand survived.

Native `range()` constructs values only when both endpoints and any explicit step carry one definitionally equivalent
numeric brand. Omitting the step uses PHP's contextual default increment; explicitly passing an unbranded step is not
equivalent because the call would otherwise assert a unit the argument does not possess. Branded numeric strings must be
cast before range construction.

**Reason.** Native brands provide interoperability and ordinary PHP arithmetic without allocation or dispatch. Runtime
code therefore cannot recover a source unit from a branded scalar and must receive that unit explicitly at conversion
boundaries.

**Representative enforcement.** [`unit()`](../../src/functions.php) validates its expression and returns the original
magnitude unchanged. PHPDoc declarations establish trusted numeric-string brands without runtime work. The custom types
and dynamic return extensions under [`src/PHPStan`](../../src/PHPStan) carry the analysis-only brand.
[`UnitFunctionTest`](../../tests/UnitFunctionTest.php) and the [`unit-ops.php`](../../tests/PHPStan/data/unit-ops.php)
fixture cover runtime identity and inferred arithmetic. The
[`unit-scalar-transformations.php`](../../tests/PHPStan/data/unit-scalar-transformations.php),
[`InvalidUnitArrayAggregationFunctionCalls.php`](../../tests/PHPStan/Fixtures/InvalidUnitArrayAggregationFunctionCalls.php),
and [`unit-numeric-string-weak-coercion.php`](../../tests/PHPStan/data/unit-numeric-string-weak-coercion.php) fixtures
distinguish explicit scalar conversions from implicit coercion. The public [Core Concepts](../pages/core-concepts.md)
guide documents the boundary.

**Invalid shortcut.** Describing a branded value as carrying its unit at runtime, attempting to recover the brand in
`unit_to()`, treating a branded numeric string as a parsed quantity, preserving its brand through implicit numeric
coercion, or changing `unit()` to allocate a quantity object.

**Classification.** Treating a brand as runtime state is a correctness defect in code or documentation. Changing the
native helper to return an object would be a compatibility break.

## Unit Relations Remain Distinct

**Invariant.** Structural equality, definitional equivalence, and dimensional compatibility are different relations:

- structural equality compares reduced symbolic forms;
- definitional equivalence compares normalized definitions, including exact scale; and
- dimensional compatibility permits an explicit conversion between units of the same dimension.

Native addition, subtraction, and modulo require definitional equivalence because PHP cannot convert either operand.
Native `intdiv()` follows quotient unit algebra and truncates only the stored integer magnitude; it does not convert
either operand. `Quantity` addition, subtraction, equality, and ordering may convert a dimensionally compatible right
operand into the left operand's unit. Strict `*WithSameUnit()` methods retain the definitional-equivalence rule.

`Quantity::equals()` and `PointQuantity::equals()` are total predicates: they convert compatible operands exactly and
return `false` for incompatible dimensions or registry contexts. Named ordering methods are partial and throw when the
operands cannot be compared. PHP's loose equality and ordering operators inspect object state without applying Yumemi's
compatible-unit conversion, so the PHPStan extension rejects them when either operand is statically known to include a
runtime quantity object. Strict identity remains available for deliberate instance-identity and nullable-presence
checks; it does not provide semantic quantity equality. Accepted zero-scale units compare through a nonzero canonical
basis; reciprocal zero-scale unit expressions are rejected during quantity construction because their scale is
undefined.

**Reason.** `meter` and `foot` measure the same dimension but represent different native magnitudes. Treating
compatibility as interchangeability would silently calculate the wrong number.

**Representative enforcement.** [`UnitExpression`](../../src/PHPStan/UnitExpression.php) exposes the three relations,
[`UnitOperatorTypeSpecifyingExtension`](../../src/PHPStan/UnitOperatorTypeSpecifyingExtension.php) applies the native
rule, [`InvalidNativeQuantityComparisonRule`](../../src/PHPStan/InvalidNativeQuantityComparisonRule.php) prevents PHP
object comparison from masquerading as unit-aware comparison, and [`Quantity`](../../src/Quantity.php) performs exact
compatible conversion. [`QuantityTest`](../../tests/QuantityTest.php),
[`UnitOperatorTypeSpecifyingExtensionTest`](../../tests/PHPStan/UnitOperatorTypeSpecifyingExtensionTest.php), and
[`RuntimeInvariantTest`](../../tests/RuntimeInvariantTest.php) cover both accepted and rejected paths.

**Invalid shortcut.** Allowing native `meter + foot` because the dimensions match, or requiring identical display text
where exact normalized definitions are equivalent, or treating PHP object comparison as a quantity comparison.

**Classification.** Confusing these relations is a correctness defect. Changing which public operation uses which
relation is also a compatibility break.

## Exactness Ends Only at Explicit Boundaries

**Invariant.** `Rational`, `Quantity`, `PointQuantity`, and exact `Units` operations preserve rational magnitudes. An
exact root either produces another rational value or fails; it never approximates an irrational result. Any rounding,
truncation, terminating-decimal requirement, or binary floating-point conversion must occur through an API whose name
and parameters disclose that policy.

A rational's normalized magnitude remains stable after construction and restoration. Mutating a caller-owned GMP input
or a component returned through the value's API must not change that magnitude or quantities sharing it.

**Reason.** Unit conversion often introduces fractions. Silent conversion to `float` would make exact equality,
round-tripping, and reproducible output depend on binary rounding.

**Representative enforcement.** [`Rational`](../../src/Number/Rational.php) stores normalized GMP numerator and
denominator values. [`Quantity`](../../src/Quantity.php) and [`PointQuantity`](../../src/PointQuantity.php) retain
`Rational` state. [`BinaryFloat`](../../src/Number/BinaryFloat.php) decodes finite binary64 inputs exactly, while named
output methods expose integer, decimal, and float policies. `Rational::root()` and `Quantity::root()` throw
`NonExactRootException` when the requested result cannot remain exact. Significant-decimal output computes one exact
rounded coefficient and decimal exponent before rendering either plain or scientific notation. Exact float output is
strict by default; an explicit `FloatRangePolicy` may instead select signed infinity or signed zero for binary64 range
loss. Exact integer and decimal extraction throw `NonIntegralValueException` and `NonTerminatingDecimalException`,
respectively, beneath the shared `NonExactOutputException` recovery category.
[`RationalTest`](../../tests/Number/RationalTest.php), [`BinaryFloatTest`](../../tests/Number/BinaryFloatTest.php), and
[`QuantityTest`](../../tests/QuantityTest.php) cover roots, rounding, non-terminating decimals, overflow, and underflow.

**Invalid shortcut.** Storing a `Quantity` magnitude as `float`, approximating a non-exact root, returning an
approximate decimal from an exact method, or mapping a nonzero exact value to zero or infinity at a native boundary
without an explicit range policy.

**Classification.** Silent precision loss is a correctness defect. Changing an explicitly documented output policy is a
compatibility break. The native `unit_to()`, `unit_factor()`, and `convertFloat()` APIs are accepted approximate
boundaries, not violations.

## Reduction, Normalization, and Formatting Stay Separate

**Invariant.** Symbolic reduction combines and cancels the units already written without substituting definitions.
Normalization expands definitions and then reduces. Formatting changes presentation without changing semantic meaning.
For a fixed expression, registry, and format policy, each operation must be deterministic and idempotent where
applicable; documented parseable output must round-trip to the same meaning.

**Reason.** A user may need `(meter / second) * second` to reduce to `meter` while preserving `centimeter / foot` rather
than silently replacing both names with base units. Display choices must not become hidden conversion.

**Representative enforcement.** [`ExprReducer`](../../src/Analyzer/ExprReducer.php) orders and combines symbolic
factors, [`UnitNormalizer`](../../src/Analyzer/UnitNormalizer.php) performs definition substitution, and
[`ExprFormatter`](../../src/Formatter/ExprFormatter.php) renders according to `FormatOptions`.
[`ExprReducerTest`](../../tests/Analyzer/ExprReducerTest.php),
[`UnitNormalizerTest`](../../tests/Analyzer/UnitNormalizerTest.php),
[`ParserFormatterRoundTripTest`](../../tests/Parser/ParserFormatterRoundTripTest.php),
[`RuntimeInvariantTest`](../../tests/RuntimeInvariantTest.php), and
[`BoundedAlgebraTest`](../../tests/Generative/BoundedAlgebraTest.php) cover ordering, idempotence, and round-trips.

**Invalid shortcut.** Implementing reduction as normalization, moving a unit scale into a quantity during ordinary
multiplication or division, allowing `root()` to substitute definitions implicitly, or producing output whose meaning
depends on map insertion order.

**Classification.** A semantic or round-trip change is a correctness defect. An intentional change to exposed canonical
or default display text may also be a compatibility break even when the represented unit remains equivalent.

## Preferred Units Remain Explicit Application Policy

**Invariant.** A preferred-unit profile maps each represented dimension to at most one complete target expression,
performs exact conversion, and applies only to quantities from its object-identical `Units` context. A missing target
leaves the immutable quantity unchanged. Preferred selection remains separate from normalization, simplification, and
formatting.

**Reason.** A registry can establish dimensional compatibility but cannot infer whether an application wants meters,
feet, nautical miles, or a domain-specific unit. Dimensions also do not encode quantity kind: gray and sievert share a
dimension, and distinct dimensionless quantities can require different presentation. The application boundary must own
that policy.

**Representative enforcement.** [`PreferredUnitProfile`](../../src/PreferredUnitProfile.php) validates and binds
targets, while [`Quantity::toPreferred()`](../../src/Quantity.php) applies them. Runtime behavior is covered by
[`PreferredUnitProfileTest`](../../tests/PreferredUnitProfileTest.php) and the versioned
[`RuntimeConformanceTest`](../../tests/Conformance/RuntimeConformanceTest.php); PHPStan deliberately returns an
unbranded `Quantity` because a profile's contents are runtime state.

**Invalid shortcut.** Choosing a catalog-wide “best” unit, permitting duplicate same-dimension targets with
order-dependent results, applying a profile from another context, or making `FormatOptions` convert magnitudes.

**Classification.** Incorrect target selection, inexact conversion, or cross-context interpretation is a correctness
defect. Changing the documented selection behavior is a compatibility break.

## Compact Units Use Explicit Families And Exact Prefixes

**Invariant.** Compact selection operates within one caller-selected named multiplicative unit family. Eligible prefixes
come from the quantity's immutable registry, have an exact scale of `10^(3n)`, and are validated by exact conversion to
the unprefixed base. Selection compares exact rational magnitudes, uses the unprefixed base for zero, and preserves the
sign. It does not infer a unit system or perform presentation formatting.

**Reason.** Dimensional compatibility cannot choose among metric, imperial, nautical, or application-specific policy.
Floating-point logarithms would also introduce avoidable approximation and boundary instability into an exact runtime
model. Exact conversion validation distinguishes a legitimate catalog collision such as `kilogram` from an unrelated
unit whose name only resembles a prefixed candidate.

**Representative enforcement.** [`Units::compactQuantity()`](../../src/Units.php) builds and caches validated candidate
families, while [`Quantity::toCompact()`](../../src/Quantity.php) exposes the supported value operation.
[`QuantityCompactionTest`](../../tests/QuantityCompactionTest.php) covers boundaries, saturation, collisions, custom
registries, and unsupported roots; [`RuntimeConformanceTest`](../../tests/Conformance/RuntimeConformanceTest.php)
preserves representative public results. PHPStan returns an unbranded `Quantity` because selection depends on runtime
magnitude.

**Invalid shortcut.** Scanning the whole catalog for an aesthetically preferable unit, using binary logarithms,
compacting a structural compound without an explicit policy, treating every concatenated name as a valid prefix, or
moving selection into `FormatOptions`.

**Classification.** Inexact or nondeterministic selection, accepting a false prefix collision, or crossing unit-family
or registry boundaries silently is a correctness defect. Changing documented boundary selection is a compatibility
break.

## Affine Points and Multiplicative Differences Stay Distinct

**Invariant.** A coordinate on an affine scale is a `PointQuantity`; a difference on that scale is an ordinary
multiplicative `Quantity` in a delta unit. Points may be converted and compared, translated by compatible differences,
and subtracted from one another to obtain a difference. Point-plus-point and affine units inside multiplicative algebra
remain invalid.

**Reason.** Temperature coordinates have an origin as well as a scale. Multiplying or adding coordinates as though they
were intervals produces meaningless results, while temperature differences require scale conversion without an origin
offset.

**Representative enforcement.** [`PointQuantity`](../../src/PointQuantity.php) implements coordinate operations,
[`UnitConversionResolver`](../../src/Analyzer/UnitConversionResolver.php) separates scale and offset, and
[`AffineDeltaUnitSynthesizer`](../../src/Catalog/AffineDeltaUnitSynthesizer.php) creates explicit multiplicative delta
definitions. [`PointQuantityTest`](../../tests/PointQuantityTest.php),
[`AffineConversionTest`](../../tests/AffineConversionTest.php), and
[`InvalidPointQuantityMethodRuleTest`](../../tests/PHPStan/InvalidPointQuantityMethodRuleTest.php) cover runtime and
static behavior.

**Invalid shortcut.** Treating Celsius coordinates as Kelvin intervals, permitting `celsius / second`, applying an
offset to a delta conversion, or returning a point from point subtraction.

**Classification.** Violating the point/difference distinction is a correctness defect and a public behavior change.

## Source Spans Survive Semantic Processing

**Invariant.** Parser spans are half-open byte ranges into the original expression. When a post-parse semantic failure
can be attributed to source text, it must retain the relevant originating span rather than report only an expanded
catalog definition or an unrelated location. Rendering may derive human character columns separately.

**Reason.** PHPStan and runtime diagnostics need to identify the expression the user wrote, including when Unicode makes
byte offsets differ from displayed columns or when catalog resolution introduces nested expressions.

**Representative enforcement.** [`SourceSpan`](../../src/Parser/SourceSpan.php) defines the range contract. AST nodes
carry spans through [`AstNode`](../../src/Parser/AstNode.php); [`AstConverter`](../../src/Analyzer/AstConverter.php) and
[`UnitConversionResolver`](../../src/Analyzer/UnitConversionResolver.php) propagate an originating context into semantic
exceptions. [`SourceSpanTest`](../../tests/Parser/SourceSpanTest.php),
[`ParserSyntaxErrorTest`](../../tests/Parser/ParserSyntaxErrorTest.php),
[`UnicodeSyntaxTest`](../../tests/Parser/UnicodeSyntaxTest.php), and
[`UnitExpressionParserTest`](../../tests/PHPStan/UnitExpressionParserTest.php) cover parser and adapter behavior.

**Invalid shortcut.** Recomputing offsets from normalized text, treating byte offsets as character offsets, or dropping
the source span while resolving a unit name or unsupported operation.

**Classification.** Incorrect attribution is a correctness defect. Changing the public byte-range convention is also a
compatibility break.

## Unit Expression Work Is Bounded

**Invariant.** Every unit-expression entry point rejects malformed UTF-8 and enforces one shared parser budget before
admitting an expression to runtime resolution or PHPStan analysis. Inputs are limited to 4,096 bytes, 256 non-whitespace
lexical tokens, 64 nested parentheses, and 1,024 bytes in one identifier or numeric token. Successful parser-cache
entries have already passed those checks; failures never enter the cache.

**Reason.** Doctrine Lexer materializes its token stream before the generated parser consumes it, while later expression
processing traverses the resulting tree. Explicit byte, token, token-size, and nesting limits bound those costs without
requiring an application to know which public API eventually invokes the shared parser.

**Representative enforcement.** The handwritten [`Lexer`](../../src/Parser/Lexer.php) owns the limits and counters,
while [`ParserUtils`](../../src/Parser/ParserUtils.php) checks input size before consulting the process-wide AST cache.
[`NativeParserAdapter`](../../src/Parser/NativeParserAdapter.php) admits only an extension whose atomic compatibility
check accepts the expected ABI when the process selection setting permits native parsing, and translates its structured
limit failures into the same exception contract; otherwise parsing stays on the PHP implementation. The extension's
committed Unicode classification snapshot is independent of PHP's runtime PCRE version.
[`ParserTest`](../../tests/Parser/ParserTest.php),
[`UnitRegistryBuilderTest`](../../tests/Registry/UnitRegistryBuilderTest.php),
[`UnitExpressionParserTest`](../../tests/PHPStan/UnitExpressionParserTest.php), and the versioned conformance corpus
cover direct parsing, custom definitions, PHPStan adaptation, cache behavior, exact boundaries, and multibyte inputs.

**Invalid shortcut.** Applying limits or encoding checks only in a controller, checking after tokenization, allowing
cached input to bypass the policy, or maintaining a separate PHPStan admission rule that can disagree with runtime
parsing.

**Classification.** Unbounded work through a documented parser path is a correctness defect. Changing a documented limit
or its failure category is a compatibility-sensitive runtime policy change.

## Native Helper Expressions Must Be Statically Definite

**Invariant.** By default, each native `unit()`, `unit_factor()`, and `unit_to()` argument that controls inferred unit
identity must expose its complete finite set of constant strings to PHPStan. Every valid alternative must parse and the
operation must collapse to one semantic result unit. A broad `string`, an incomplete finite subset, or alternatives with
different semantic results must not produce a precise brand.

Dynamic runtime APIs on `Units`, `Quantity`, and `PointQuantity` remain the intentional path for user-provided
expressions. Configuration may suppress the dynamic-expression diagnostic, but it does not invent static precision;
ambiguous semantic results remain errors unless suppressed locally.

**Reason.** A union such as `'meter'|'foot'` cannot safely become one runtime `float` brand merely because both branches
are known. PHP control flow cannot recover which same-carrier brand applies after the call.

**Representative enforcement.** [`NativeUnitArgumentResolver`](../../src/PHPStan/NativeUnitArgumentResolver.php)
requires the complete finite type, and [`InvalidUnitCallRule`](../../src/PHPStan/InvalidUnitCallRule.php) emits dynamic,
ambiguous, or invalid diagnostics.
[`NativeUnitArgumentResolverTest`](../../tests/PHPStan/NativeUnitArgumentResolverTest.php),
[`InvalidUnitCallRuleTest`](../../tests/PHPStan/InvalidUnitCallRuleTest.php), and the
[`native-unit-expression-diagnostics.php`](../../tests/PHPStan/data/native-unit-expression-diagnostics.php) integration
fixture cover the fail-closed policy and escape hatches.

**Invalid shortcut.** Calling `getConstantStrings()` and accepting a known subset of a broader type, or generating a
same-carrier union whose runtime value cannot identify its unit branch.

**Classification.** Unsound inferred brands are a correctness defect. Tightening or relaxing the default diagnostic
policy is a compatibility change; local and configured escape hatches are accepted tradeoffs.

## Registry Snapshots Define a Semantic Context

**Invariant.** A `Units` instance interprets names, dimensions, prefixes, and conversions through one registry snapshot.
Builder output and composite layers are treated as immutable after construction. Composite lookup selects one complete
effective entry from the winning layer. Runtime values and resolved expressions may be combined or interpreted only when
they belong to the same live `Units` object, even if two independent registries currently contain equivalent
definitions. During each semantic admission, wholly unbound low-level expressions may be copied into one live context; a
context-bound expression is never rebound.

PHPStan uses one configured registry and includes its effective semantics in result-cache invalidation. Applications
that share custom units across runtime and PHPStan must construct both layers from equivalent registry definitions.

The process-wide default `Units` context is synchronous bootstrap configuration, not Fiber-local state. Native helpers
may read a configured default from a Fiber, but `Units::setDefault()` rejects an actual context change from inside one.
Applications must not replace the default from the main execution context after Fiber scheduling begins, because PHP
does not expose that global lifecycle through the `setDefault()` call. Concurrent work that needs an isolated registry
must retain and use an explicit `Units` instance.

**Reason.** A unit name can have different definitions in different registries or rate snapshots. Comparing only the
spelling or dimension would allow values governed by different semantic contexts to mix silently.

**Representative enforcement.** [`UnitRegistryBuilder`](../../src/Registry/UnitRegistryBuilder.php) constructs registry
snapshots, [`CompositeUnitRegistry`](../../src/Registry/CompositeUnitRegistry.php) applies whole-layer precedence,
[`ExpressionContextResolver`](../../src/Analyzer/ExpressionContextResolver.php) protects expression algebra and semantic
entry points, and [`Quantity`](../../src/Quantity.php) and [`PointQuantity`](../../src/PointQuantity.php) enforce
`Units` identity. [`UnitRegistryResultCacheMetaExtension`](../../src/PHPStan/UnitRegistryResultCacheMetaExtension.php)
hashes effective registry data deterministically.
[`UnitRegistryBuilderTest`](../../tests/Registry/UnitRegistryBuilderTest.php),
[`CompositeUnitRegistryTest`](../../tests/Registry/CompositeUnitRegistryTest.php),
[`ExpressionContextTest`](../../tests/ExpressionContextTest.php), [`QuantityTest`](../../tests/QuantityTest.php), and
[`UnitRegistryResultCacheMetaExtensionTest`](../../tests/PHPStan/UnitRegistryResultCacheMetaExtensionTest.php) cover
these boundaries.

**Invalid shortcut.** Mutating a registry after installing it, merging prebuilt and catalog representations from
different composite layers, rebinding an expression after its context expires, or accepting cross-context operations
because unit names happen to match. Treating `setDefault()` save/restore as request-local scope in concurrent code is
likewise invalid.

**Classification.** Semantic drift or cross-context mixing is a correctness defect. Deliberately changing registry
precedence or context identity is also a compatibility break.

## Generated Artifacts Remain Consumable and Recoverable

**Invariant.** The checked-in parser and UDUNITS2 catalog must be sufficient for ordinary consumers; installing Yumemi
must not require Bison, UDUNITS2 XML, or catalog regeneration. Authoritative source, generator procedure, provenance,
licensing, and verification must remain available so maintainers can reproduce or replace those outputs.

Generated files are outputs, not editing authorities. Changes belong in the grammar, importer, exporter, or source data
and must be regenerated deterministically where the toolchain permits it.

**Reason.** Committed output lets the last valid release survive generator decay, while committed generation knowledge
allows a future maintainer to modify rather than merely consume that release.

**Representative enforcement.** [`grammar.y`](../../src/Parser/grammar.y), the
[`Udunits2CatalogImporter`](../../src/Catalog/Udunits2CatalogImporter.php), and the
[`GenerateUdunits2CatalogCommand`](../../src/Command/GenerateUdunits2CatalogCommand.php) are authoritative inputs and
generators. [`Makefile`](../../Makefile) and [`composer.json`](../../composer.json) expose regeneration. The checked-in
[`Parser.php`](../../src/Parser/Parser.php) and [`data/udunits2.php`](../../data/udunits2.php) ship in consumer
archives. [`GenerateUdunits2CatalogCommandTest`](../../tests/Command/GenerateUdunits2CatalogCommandTest.php) verifies
byte-identical catalog regeneration from the reference database, and [`tests/Consumer/run`](../../tests/Consumer/run)
verifies archive contents.

**Invalid shortcut.** Hand-editing generated output, omitting it from release archives, or allowing CI to contain the
only known regeneration procedure.

**Classification.** A generated artifact that no longer represents its authoritative source is a correctness defect.
Removing required output or changing catalog semantics is a compatibility break. Updating the external source database
is an explicit project decision, not routine dependency churn.

The [generated-artifact inventory](generated-artifacts.md) consolidates the pinned tool versions, provenance, licensing,
consumer requirements, and exact checks. The Nix `generated-artifacts` check independently verifies byte-identical
parser and catalog output before running the UDUNITS2 differential cases.

## Serialization And JSON Restoration Preserve Their Boundaries

**Invariant.** Native serialized payloads are explicitly versioned and structurally validated. `Quantity` and
`PointQuantity` restoration must select the default or an explicitly scoped custom `Units` context and verify that the
restored unit, normalized form, dimension, and affine transform still have the serialized meaning. Unknown versions,
malformed fields, wrong registries, and custom values restored without `Units::deserialize()` must fail closed.

PHP serialization and JSON serve different purposes. `Units::quantityFromJson()` and `pointFromJson()` admit only the
documented exact value-and-unit object, bind the result to the receiving context, and reject malformed JSON structures.
JSON does not claim the source registry's identity or semantics; the receiving registry deliberately interprets its unit
string. Other JSON representations remain inspectable rather than implicit arbitrary-graph hydration APIs.

**Reason.** Restoring a magnitude under a changed registry can silently alter its physical meaning even when the unit
string remains unchanged. Version fields make payload evolution deliberate rather than dependent on private property
layout.

**Representative enforcement.** [`Rational`](../../src/Number/Rational.php), [`Dimension`](../../src/Dimension.php),
[`Quantity`](../../src/Quantity.php), and [`PointQuantity`](../../src/PointQuantity.php) define strict payload schemas.
[`DeserializationContext`](../../src/Internal/DeserializationContext.php) provides nested dynamic scoping, and
[`Units::deserialize()`](../../src/Units.php) forwards native `allowed_classes` and `max_depth` options.
[`SerializationTest`](../../tests/SerializationTest.php) covers typed JSON restoration, receiving-context semantics,
default and custom native contexts, nested graphs, legacy versions, malformed payloads, semantic seals, and native
options. [`ReleasePersistenceCompatibilityTest`](../../tests/Compatibility/ReleasePersistenceCompatibilityTest.php)
restores the quantity and point JSON shapes emitted by tagged releases.

**Invalid shortcut.** Serializing the entire registry object graph, restoring every quantity through the current default
registry without verification, accepting unknown JSON fields, or treating JSON as though it carried the source
registry's identity.

**Classification.** Accepting a native payload with changed semantics or accepting a malformed JSON value shape is a
correctness defect. Removing support for a documented payload version or changing its meaning is a compatibility break.
Supporting native graphs containing several custom registries remains an accepted deferred limitation.

## Diagnostic Identifiers Are Stable; Prose May Evolve

**Invariant.** PHPStan diagnostics exposed by Yumemi use specific `yumemi.*` identifiers. Suppression, integration, and
compatibility tests should depend on those identifiers where possible. Human-readable messages must remain accurate and
useful but may improve without becoming an exact-message compatibility contract.

**Reason.** Stable identifiers preserve machine-readable meaning while allowing clearer wording, additional context, or
future localization. Depending on complete English messages would make harmless improvements breaking changes.

**Representative enforcement.** Rule implementations such as
[`InvalidUnitCallRule`](../../src/PHPStan/InvalidUnitCallRule.php),
[`InvalidQuantityConstructionRule`](../../src/PHPStan/InvalidQuantityConstructionRule.php), and
[`YumemiTagPromotionRule`](../../src/PHPStan/YumemiTagPromotionRule.php) assign identifiers. The
[`DiagnosticIdentifierInventoryTest`](../../tests/PHPStan/DiagnosticIdentifierInventoryTest.php) is the machine-checked
compatibility inventory: it requires every public identifier to appear in both the public
[PHPStan reference](../pages/reference/phpstan.md#diagnostics) and the compatibility policy, classifies every
first-party `yumemi.*` PHPStan key, and records an identifier-specific local-ignore fixture for every public diagnostic.
Focused PHPStan rule tests separately exercise those fixtures with unmatched-ignore reporting enabled, proving that each
comment suppresses the identifier it names.

**Invalid shortcut.** Reusing one broad identifier for unrelated failures, renaming an identifier merely to improve its
wording, or asking users to suppress a complete message string.

**Classification.** Missing or incorrect identifiers are correctness defects for the extension. Renaming or removing a
documented identifier is a compatibility break. Message-only wording changes are normally compatible.

## Maintenance Rule

When a change affects one of these invariants:

1. state which invariant changes or remains preserved;
2. update representative enforcement or add a focused regression;
3. update public documentation if observable behavior changes;
4. classify the result as a correctness fix, compatibility change, or accepted tradeoff; and
5. record a decision only when the rationale will constrain future work.

Do not add invariants for incidental class structure, private implementation choices, or behavior that the project is
not prepared to preserve.
