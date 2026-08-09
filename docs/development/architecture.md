# Yumemi Architecture

This document maps Yumemi's durable components, dependency direction, replacement boundaries, generated artifacts, and
likely decay points. It describes responsibilities rather than promising compatibility for every public PHP declaration;
that narrower classification belongs in the [compatibility policy](compatibility.md).

Read this document with the project-specific [semantic invariants](invariants.md). The architecture explains where
meaning lives. The invariants explain what that meaning requires.

## Governing Direction

Yumemi is one runtime unit engine with a PHPStan adapter, not two dimensional-analysis implementations:

> One expression model. One registry. One normalization engine.

Dependencies point toward the semantic core:

```text
PHPStan, commands, documentation, CI, benchmarks, and catalog acquisition
                                |
                                v
              Public runtime facades and value objects
                                |
                                v
        Resolution, reduction, normalization, and conversion
                                |
                                v
       Parser AST, expressions, dimensions, rationals, registries
```

An outer layer may adapt or present an inner layer. An inner layer must not depend upon an outer integration merely to
make that integration convenient.

The most important enforced boundary is:

```text
src/PHPStan -> runtime core: allowed and required
runtime core -> PHPStan or src/PHPStan: forbidden
```

[`RuntimeDependencyDirectionTest`](../../tests/Architecture/RuntimeDependencyDirectionTest.php) scans authored and
generated runtime PHP with the native tokenizer. It rejects direct PHPStan framework references, Yumemi adapter
references, aliases, fully qualified names, and class-name strings outside `src/PHPStan`.

## Component Map

### Foundational Model

The foundational model represents exact values and unit syntax without knowing about PHPStan or application workflows:

- [`Rational`](../../src/Number/Rational.php) and [`BinaryFloat`](../../src/Number/BinaryFloat.php) define exact
  rational values and explicit binary64 boundaries.
- [`Parser`](../../src/Parser/Parser.php), [`Lexer`](../../src/Parser/Lexer.php), and the AST under
  [`src/Parser/Ast`](../../src/Parser/Ast) represent accepted unit syntax and source spans.
- [`Expr`](../../src/Expr.php) and its expression nodes represent constants, units, products, and integer powers.
- [`Dimension`](../../src/Dimension.php) represents the seven SI dimensions plus named extension dimensions.
- [`UnitRegistry`](../../src/Registry/UnitRegistry.php) and
  [`UnitRegistryEntry`](../../src/Registry/UnitRegistryEntry.php) define the lookup boundary consumed by semantic
  resolution.

These types should remain independent of PHPStan, command-line presentation, documentation tooling, and hosted services.

### Semantic Core

The semantic core assigns meaning to expressions:

- [`UnitResolver`](../../src/Analyzer/UnitResolver.php) resolves exact names, aliases, prefixes, plurals, catalog
  records, and prebuilt units.
- [`AstConverter`](../../src/Analyzer/AstConverter.php) converts syntax into symbolic expressions while preserving
  source context.
- [`ExprReducer`](../../src/Analyzer/ExprReducer.php) performs deterministic symbolic cancellation without definition
  substitution.
- [`UnitNormalizer`](../../src/Analyzer/UnitNormalizer.php) substitutes definitions and reduces the resulting
  expression.
- [`DimensionResolver`](../../src/Analyzer/DimensionResolver.php) derives SI and extension dimensions.
- [`UnitConversionResolver`](../../src/Analyzer/UnitConversionResolver.php) produces exact scale-and-offset conversion
  plans, while [`ConversionFactorResolver`](../../src/Analyzer/ConversionFactorResolver.php) handles multiplicative
  factors.
- Catalog semantic helpers classify multiplicative, affine, logarithmic, and synthesized delta definitions before
  runtime use.

This layer is the source of truth for runtime and static analysis. Changes here must be evaluated against
[`invariants.md`](invariants.md), differential tests, bounded generative tests, and public examples.

### Runtime Surface

The runtime surface presents the semantic core to applications:

- [`Units`](../../src/Units.php) binds one registry context to parsing, resolution, conversion, formatting, and value
  construction.
- [`Quantity`](../../src/Quantity.php) retains an exact multiplicative magnitude and unit.
- [`PointQuantity`](../../src/PointQuantity.php) retains an exact coordinate and named affine scale.
- [`functions.php`](../../src/functions.php) exposes native-scalar boundary helpers.
- [`Formatter`](../../src/Formatter) contains configurable rendering policy and catalog-aware presentation.
- [`Exception`](../../src/Exception) provides the common project exception contract and domain-specific failures.
- [`UnitRegistryBuilder`](../../src/Registry/UnitRegistryBuilder.php) and
  [`CompositeUnitRegistry`](../../src/Registry/CompositeUnitRegistry.php) construct immutable effective registry
  snapshots. Generated catalogs may carry precomputed name-group indexes, while custom and shadowing registries derive
  the same internal view from effective entries; the index accelerates introspection without becoming a second source of
  unit semantics.

Public language visibility does not by itself settle long-term compatibility. The
[compatibility policy](compatibility.md) distinguishes supported application APIs from integration contracts and
implementation details.

### PHPStan Adapter

Everything under [`src/PHPStan`](../../src/PHPStan) translates runtime semantics into PHPStan concepts:

- type-node resolvers construct branded native and generic object types;
- dynamic return extensions infer helper and method results;
- operator extensions apply expression algebra and integer-range policy;
- rules produce user-facing diagnostics and stable identifiers;
- the optional tag-promoting parser transforms `@yumemi-*` annotations;
- registry configuration supplies one runtime `UnitRegistry` to the adapter; and
- result-cache metadata hashes effective registry semantics.

The adapter calls [`UnitExpressionParser`](../../src/PHPStan/UnitExpressionParser.php), which delegates parsing,
dimensions, and normalization to `Units`. Type-level algebra combines the same `Expr` and `Dimension` values rather than
maintaining another catalog.

PHPStan APIs are expected to decay faster than the runtime model. Adapter changes should preserve the runtime contract
and PHPStan conformance evidence instead of pushing tool-specific concepts inward.

### Acquisition and Presentation Tooling

The following layers support development or generation but do not define runtime semantics by themselves:

- [`Udunits2CatalogImporter`](../../src/Catalog/Udunits2CatalogImporter.php),
  [`PhpCatalogExporter`](../../src/Catalog/PhpCatalogExporter.php), and [`src/Command`](../../src/Command) acquire and
  generate catalog data. They are excluded from release archives.
- [`bin`](../../bin) supplies thin command entry points.
- [`tests`](../../tests), [`benchmarks`](../../benchmarks), and [`probator`](../../probator) provide independent
  evidence and investigation tools.
- Nix, Composer, Make, GitHub Actions, mdBook, and the theme encode reproducible development, packaging, and
  documentation workflows.

These layers may be replaced when their external ecosystems decay. Replacement must preserve the relevant generated
artifacts, public behavior, and verification rather than the current tool's internal shape.

### External Integration Package

[Yumemi Apocrypha](https://github.com/jbboehr/yumemi-apocrypha.php) owns curated third-party stubs and their upstream
version matrices. Yumemi core owns only the generic `@yumemi-*` promotion mechanism.

This package boundary is justified by an independent consumer surface, dependency matrix, release cadence, and
maintenance scope. Core must not acquire framework packages merely to broaden curated stub coverage.

## Runtime Data Flow

The main multiplicative path is:

```text
unit string
    -> Parser\Ast
    -> AstConverter and UnitResolver
    -> Expr
    -> ExprReducer or UnitNormalizer
    -> DimensionResolver or ConversionFactorResolver
    -> Units, Quantity, or native helper result
```

The exact conversion path is:

```text
source and target string or Expr
    -> UnitConversionResolver
    -> ResolvedConversionUnit for each side
    -> compatibility check
    -> ExactConversion(scale, offset)
    -> Rational result or explicit native numeric boundary
```

Affine coordinates use the same conversion core but enter through `PointQuantity`. Synthesized delta definitions expose
their scale as ordinary multiplicative units without carrying the coordinate origin into algebra.

## PHPStan Data Flow

The static-analysis path is:

```text
PHPDoc or call site
    -> configured registry and UnitExpressionParser
    -> runtime parse, dimension, normalization, and conversion semantics
    -> UnitExpression or PointUnitExpression
    -> UnitIntegerType, UnitFloatType, QuantityType, or PointQuantityType
    -> inference or a stable diagnostic identifier
```

Native brands disappear at runtime. `Quantity` and `PointQuantity` types describe objects that retain exact values and
registry contexts at runtime, but PHPStan still assumes one configured registry rather than tracking object contexts
flow-sensitively.

## Generated Artifacts

Two committed outputs allow consumers to use Yumemi without its generation toolchain:

| Generated output        | Authority and generator                                           | Runtime consumer              |
| ----------------------- | ----------------------------------------------------------------- | ----------------------------- |
| `src/Parser/Parser.php` | `src/Parser/grammar.y`, Bison, and `mrsuh/php-bison-skeleton`     | `Parser`, runtime and PHPStan |
| `data/udunits2.php`     | UDUNITS2 XML, `Udunits2CatalogImporter`, and `PhpCatalogExporter` | `Udunits2UnitRegistry`        |

Both artifacts have Nix-backed byte-identical regeneration checks. The
[generated-artifact inventory](generated-artifacts.md) records exact tool versions, provenance, licensing, consumer
requirements, and verification without changing this component boundary.

Generated files are committed consumption artifacts. Their grammar, importer, exporter, and upstream data are editing
authorities.

## Replacement Boundaries and Decay

Expected replacement boundaries are deliberately narrow:

| Boundary                | Likely cause of decay                             | What must survive                         |
| ----------------------- | ------------------------------------------------- | ----------------------------------------- |
| PHPStan adapter         | Internal PHPStan API and type-system changes      | Unit semantics, inferred contracts, IDs   |
| Parser generator        | Bison skeleton or generation-environment changes  | Grammar, spans, precedence, round-trips   |
| UDUNITS2 acquisition    | XML schema, package layout, or upstream changes   | Provenance, imported semantics, fixtures  |
| Documentation toolchain | mdBook, theme, or hosting changes                 | Markdown sources, examples, link validity |
| Nix and CI              | Package expressions, actions, or hosted services  | Local commands and reproducible checks    |
| Apocrypha integrations  | Third-party framework and package version changes | Generic promotion contract in core        |

Abstractions should be introduced only when one of these replacement scenarios requires a stable boundary. Core classes
may remain `final`; forkability depends on source, tests, specifications, and licensing rather than universal
subclassability.

## Operational Evidence

The architecture is supported by several independent forms of evidence:

- focused unit and integration tests for the parser, semantic core, runtime values, and PHPStan adapter;
- a versioned, language-neutral [runtime conformance corpus](../../tests/Conformance/README.md) exercised through public
  APIs;
- executable public documentation examples discovered, executed, and statically verified through Akashi;
- bounded generated-expression and algebra tests;
- Eris property tests for branded integer ranges;
- differential conversion tests against the independent UDUNITS2 executable;
- mutation campaigns for handwritten runtime and in-process PHPStan code;
- release-style consumer archives with automatic and manual extension registration; and
- local Nix, Composer, and Make entry points used by CI.

No one layer is the complete specification. Public behavior, [invariants](invariants.md), the
[conformance corpus](../../tests/Conformance/README.md), and implementation must be reviewed together when they
disagree.

## Change Rules

For architectural changes:

1. identify the affected layer and the direction of every new dependency;
2. state whether the change alters a semantic invariant or public contract;
3. prefer replacing an outer adapter to changing the semantic core for tool-specific reasons;
4. add enforcement at the narrowest real replacement boundary;
5. preserve generation sources and useful committed outputs together; and
6. update this document only when component ownership or dependency direction changes.

Do not mirror every class, create interfaces without replacement scenarios, or split packages solely to make the diagram
more symmetrical.
