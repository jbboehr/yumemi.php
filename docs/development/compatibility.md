# Yumemi Compatibility Policy

This document defines which parts of Yumemi users may rely upon, which parts exist for integration, and which visible
implementation details may change. It narrows the public contract deliberately: PHP visibility alone does not make every
class, constructor, method, service name, or data structure a compatibility promise.

Read this policy with the [architecture](architecture.md), [semantic invariants](invariants.md), and versioned
[runtime conformance corpus](../../tests/Conformance/README.md). The architecture identifies ownership, the invariants
state what must remain true, and the corpus records representative observable behavior.

## Current Status

Yumemi 0.1.0 establishes the initial public development contract described by this document. Before 1.0, patch releases
within one `0.x` minor line preserve that documented contract. A later `0.x` minor release may deliberately introduce a
breaking change when its changelog and migration guidance explain the change.

Release notes and the changelog identify user-visible changes. Patch releases may still correct defects that never
formed part of the documented contract, subject to the defect policy under [Classifying Changes](#classifying-changes).

Support means that a surface is intentionally documented, tested, and reviewed for compatibility. It does not promise
that every implementation detail behind that surface remains unchanged.

Roave Backward Compatibility Check provides an automated, conservative comparison of class-like PHP declarations and
their members against the most recent stable tag. Run it with `composer check:bc`; the command installs its isolated
tool dependencies and requires the repository's release tags and committed `HEAD` to be available. CI fetches the
complete Git history before running the same comparison. Because the checker examines public declarations under
Composer's production autoload paths, it can report changes to provisionally public declarations that this policy does
not classify as supported. Every report must therefore be classified against the stability rules below rather than
accepted or ignored mechanically.

The signature check does not cover global helper-function signatures, runtime semantics, PHPStan inference, diagnostics,
configuration, catalog behavior, JSON or serialization compatibility, or persistent conformance fixtures. Those
contracts remain protected by the invariants, focused tests, documentation, and conformance evidence described in this
policy.

## Stability Classes

### Supported Application API

The supported application API consists of declarations marked `@api` and additional declarations deliberately taught in
the public mdBook. This includes their documented signatures, named arguments, return types, observable behavior, and
documented exceptions.

The principal supported surfaces are:

- `unit()`, `unit_factor()`, and `unit_to()`;
- `Units`, `Quantity`, `PreferredUnitProfile`, `PointQuantity`, `Rational`, `Dimension`, and `FloatRangePolicy`;
- `Expr` values obtained from supported `Units` and value-object methods;
- `UnitRegistryBuilder` and the immutable `UnitRegistry` snapshots it produces;
- `FormatOptions`, `ExprFormatter`, `DecimalNotation`, and the formatting policy enums;
- descriptors and backed enums returned by `describe()` and `describePrefix()`; and
- `ExceptionInterface`, documented exception categories and metadata, `ParseException`,
  `ExpressionLimitExceededException`, the exact-output exception hierarchy, and `SourceSpan`.

The documented JSON representations and native serialization round trips of `Rational`, `Dimension`, `Quantity`,
`PointQuantity`, and catalog descriptors are application persistence contracts under the constraints in
[Persistent Data](#persistent-data).

Only documented construction paths are supported. In particular:

- construct quantities and points through `Units` rather than their `@internal` constructors;
- obtain unit expressions through `Units::parse()`, `parseUnit()`, or `unit()` rather than depending on concrete
  `Expr\*` constructors or expression-tree layout;
- construct registries through `UnitRegistryBuilder`, treat the resulting `UnitRegistry` as an opaque snapshot, and use
  `Units::describe()` and `Units::describePrefix()` for supported catalog introspection; and
- treat descriptor constructors as implementation details even though descriptor values and documented properties are
  supported when returned by catalog introspection.

Documented exactness, conversion, preferred- and compact-unit selection, reduction, normalization, affine point/delta,
registry-context, numeric-output, and formatting semantics are part of this contract. The
[semantic invariants](invariants.md) state these obligations more precisely.

### Supported Integration API

The following surfaces are supported specifically for integration rather than ordinary application use:

- `extension.neon` as the primary PHPStan extension entry point;
- `yumemi-operators.neon` as the opt-in `Quantity` operator-inference entry point;
- `yumemi-tags.neon` as the opt-in annotation-promotion entry point;
- `YUMEMI_NATIVE_PARSER` as the process-level native-parser selection control;
- `PHPStan\UnitRegistryFactory` and its static `create(): UnitRegistry` contract;
- the `parameters.yumemi.*` configuration keys documented below;
- the PHPStan pseudo-types, optional annotation tags, and diagnostic identifiers documented below.

An integration contract may remain stable while its implementation is replaced. PHPStan container service names,
extension classes, parser wrappers, cache adapters, and internal type objects are not supported merely because the NEON
configuration must instantiate them.

### Provisionally Public

A PHP declaration that is publicly visible but is neither marked `@api` nor documented as an application or integration
surface is provisionally public. It may be useful for testing or advanced experimentation, but it may change before a
stable contract is assigned.

Important examples include concrete expression-node constructors, concrete registry implementations, optional alternate
generated-catalog file parameters, low-level registry storage and lookup channels, raw catalog-record arrays, and
undocumented methods or properties on otherwise supported classes. Depending on these surfaces should be an explicit,
reviewed choice.

Provisionally public does not mean silently disposable. Changes should still be reviewed for known consumers and noted
when they are likely to affect users, but they do not receive the same compatibility guarantee as supported surfaces.

### Internal and Generated Details

Declarations marked `@internal` are not compatibility promises. The same applies to:

- the `InternalQuantity` native-extension seam;
- analyzer and resolver implementation classes;
- parser AST nodes, the generated parser implementation, lexer plumbing, parser-generator interfaces, and the optional
  native-parser ABI and adapter;
- every class under `src/PHPStan` except `UnitRegistryFactory`;
- catalog importers, exporters, classifiers, synthesis helpers, and command implementations;
- internal deserialization, arithmetic, and rendering helpers;
- dependency-injection service names and wiring inside the NEON files;
- test utilities, benchmarks, development commands, Nix expressions, CI jobs, and documentation theme internals; and
- the PHP array layout of `data/udunits2.php` and other generated implementation data.

Generated parser and catalog files remain required consumer artifacts, but their internal code and storage layout are
replaceable. Their observable grammar and catalog semantics are governed separately below.

## Runtime Semantics

The runtime contract is behavioral rather than architectural. A replacement may reorganize the implementation while
preserving:

- exact rational conversion and arithmetic until an explicitly approximate output boundary, including roots that fail
  instead of approximating when the magnitude or symbolic unit expression has no exact result;
- ties-to-even exact float output with strict range handling by default and explicit signed infinity or signed zero when
  requested through `FloatRangePolicy`;
- the distinction among symbolic reduction, definition substitution, and display formatting;
- the distinction among structural equality, definitional equivalence, and dimensional compatibility;
- exact preferred-unit conversion through an explicit application profile bound to the same registry context;
- exact engineering-prefix compaction within a caller-selected named unit family;
- affine points, multiplicative differences, and their permitted operations;
- immutable registry snapshots and rejection of cross-context expression, quantity, and point operations;
- deterministic canonical behavior represented by the conformance corpus; and
- half-open byte source spans when a runtime failure can be attributed to user input.

Exception categories and documented structured metadata are supported. Complete English messages, suggestion ordering
unless specifically documented, stack traces, and internal exception-construction paths are not exact compatibility
contracts. Message changes must remain accurate and should not be used to merge semantically distinct failures.

## Unit Language and Catalog

The documented unit-expression language is supported, including operator precedence, left associativity of adjacency,
multiplication and division, exact decimal parsing, case-sensitive name lookup, Unicode operators and superscripts, and
the distinction between accepted grammar and executable semantic capabilities.

The following changes are compatibility-sensitive:

- rejecting syntax previously documented and accepted;
- accepting existing text with a different grouping or meaning;
- changing parser resource or exponent limits, source-span conventions, or exact numeric interpretation;
- changing reduction or normalization results represented by the conformance corpus; and
- changing documented parser-compatible formatter output so that it no longer round-trips.

When a compatible `ext-yumemi` parser ABI is loaded, leaving `YUMEMI_NATIVE_PARSER` unset or setting it to `1`, `true`,
`on`, or `yes` (case-insensitive) selects the native syntax adapter automatically. Setting it to `0`, `false`, `off`,
`no`, or an empty string forces the generated PHP fallback. Any other explicit value also fails closed to the fallback.
Changing that variable's name, accepted values, or default selection policy is compatibility-sensitive.

`Expr::toString()` is documented as a structural/debug representation rather than the configurable display API. Exact
structural strings committed to a conformance-corpus version remain evidence for that version, but callers needing
presentation stability should use `Units::format()` or `ExprFormatter` with explicit `FormatOptions`.

The bundled UDUNITS2-derived names, aliases, prefixes, dimensions, and conversion definitions are observable catalog
behavior. A catalog update may add names or correct upstream data, but it must be treated as an explicit project change.
Removing or redefining an existing documented name is compatibility-sensitive. Adding a name can also be incompatible
when it changes an expression that previously resolved through prefix decomposition or failed as unknown.

The external UDUNITS2 XML layout and the checked-in PHP catalog's internal array shape are generation details, not
application data formats.

## PHPStan Contract

### Types and Inference

The supported PHPDoc types are:

- `unit_int<'expression'>`;
- `unit_float<'expression'>`;
- `unit_numeric_string<'expression'>`;
- `Quantity<'expression'>`; and
- `PointQuantity<'coordinate-unit'>`.

Ordinary PHPStan constant and integer-range types may intersect with `unit_int`; there is no separate constant-unit type
syntax. `unit_numeric_string` remains a runtime string and preserves its unit only through documented explicit numeric
casts. The documented assignment, operator, comparison, helper-return, quantity, and point inference rules are part of
the extension contract. Native brands remain analysis-only and must not be described as runtime wrappers.

A change that causes previously valid documented code to fail, accepts a previously rejected unsound operation, changes
the inferred semantic unit, or erases a documented brand is compatibility-sensitive. A precision improvement that
preserves assignability may be compatible, but it must still be covered by tests because PHPStan users often depend on
inferred ranges and unions.

### Configuration

The supported configuration keys and current defaults are:

| Key                                           | Default | Contract                                                   |
| --------------------------------------------- | ------- | ---------------------------------------------------------- |
| `yumemi.integerOverflowToFloat`               | `true`  | Model integer operations that may overflow as float        |
| `yumemi.quantityOperators`                    | `false` | Model `Quantity` object operators supplied by `ext-yumemi` |
| `yumemi.requireConstantNativeUnitExpressions` | `true`  | Diagnose native helpers without complete constant units    |
| `yumemi.registryFactory`                      | `null`  | Select one `UnitRegistryFactory` for static analysis       |

Removing or renaming a key, changing its accepted type, or changing its default is compatibility-sensitive. New optional
keys with behavior-preserving defaults are normally additive.

Only the factory interface is a supported PHPStan implementation boundary. Classes used internally to parse unit types,
represent branded types, infer operators, emit rules, or integrate with PHPStan's service container may change as
PHPStan evolves.

### Optional Tags

The supported opt-in tags are `@yumemi-param`, `@yumemi-return`, and `@yumemi-var`. Their documented structural-erasure,
target-selection, duplicate, precedence, and idempotence rules are part of the integration contract.

The tags are inactive unless `yumemi-tags.neon` is loaded. The current parser-service replacement is an implementation
strategy and may change; the opt-in behavior and diagnostics are the supported surface.

### Diagnostics

The following Yumemi identifiers are stable integration keys:

- `yumemi.dynamicUnitExpression`
- `yumemi.ambiguousUnitExpression`
- `yumemi.invalidUnitAggregation`
- `yumemi.invalidUnitAngleFunction`
- `yumemi.invalidUnitCall`
- `yumemi.invalidUnitComparison`
- `yumemi.invalidUnitMathFunction`
- `yumemi.invalidUnitRange`
- `yumemi.invalidUnitRoot`
- `yumemi.invalidUnitSelection`
- `yumemi.invalidQuantityConstruction`
- `yumemi.invalidQuantityArithmetic`
- `yumemi.invalidQuantityConversion`
- `yumemi.invalidQuantityComparison`
- `yumemi.invalidPointQuantityOperation`
- `yumemi.nativeQuantityComparison`
- `yumemi.docTagSyntax`
- `yumemi.docTagDuplicate`
- `yumemi.docTagUnsupported`
- `yumemi.docTagParameter`
- `yumemi.docTagType`
- `yumemi.docTagTransform`

Renaming, removing, or reusing one of these identifiers for a different semantic category is a compatibility break.
Human-readable diagnostic text may improve. The `binaryOp.invalid` identifier used for invalid native arithmetic and
opt-in `Quantity` operators belongs to PHPStan and is not controlled by Yumemi.

Adding a diagnostic to code previously accepted by the documented contract is compatibility-sensitive even when the new
identifier is additive. Correcting acceptance that was unsound and contradicted the documented rules is a bug fix, but
it must be called out because it can make an existing analysis fail.

## Persistent Data

### JSON

The documented `JsonSerializable` shapes of `Rational`, `Dimension`, `Quantity`, `PointQuantity`, and catalog descriptor
values are supported inspectable representations. Exact integers remain decimal strings where required to avoid
precision loss. Adding, removing, renaming, or changing the meaning or type of documented keys is
compatibility-sensitive.

JSON is not a native round-trip protocol. Yumemi does not infer a registry or reconstruct runtime objects implicitly
from these arrays.

### Native Serialization

Values emitted by PHP's `serialize()` for the documented value objects in a tagged release are supported persistence
formats within their stated registry constraints. A later compatible release must continue to restore those values with
the same semantics, or fail only for a documented safety reason such as registry semantic drift.

The versioned [release persistence corpus](../../tests/Compatibility/README.md) records native payloads produced by an
isolated installation of each tagged release, together with the release commit and producer environment. Current code
must restore those historical bytes and preserve their observable semantics. These PHP-specific fixtures are separate
from the language-neutral runtime conformance corpus.

The direct return values of `__serialize()` and their PHP array layouts are implementation details, not application
APIs. Yumemi does not promise byte-identical output from `serialize()` for newly written values. It promises that values
emitted by supported tagged releases remain readable and that semantic validation continues to fail closed. Internal
readers for payloads emitted before `0.1.0` are migration aids and do not create a historical release contract.

Custom-context restoration through `Units::deserialize()` and its forwarding of `allowed_classes` and `max_depth` are
supported. Native PHP serialization remains inappropriate for untrusted input.

### Conformance Fixtures

The corpus marker `yumemi.conformance/v1` versions the fixture format. Existing cases define evidence for their declared
version; changing a result requires an explicit semantic decision. The fixtures are repository artifacts for replacement
and verification, not a runtime wire format shipped as part of the Composer package.

The release persistence marker `yumemi.release-persistence/v1` independently versions the manifest used for tagged
native-serialization and JSON evidence. Historical release directories are immutable evidence and must not be
regenerated with a newer implementation.

## Supported Environments

- The runtime package requires PHP `^8.2` and GMP.
- `ext-yumemi` is an optional, independently versioned companion. Yumemi's supported application API, method-based
  quantity arithmetic, and generated PHP parser do not require it. This repository's locked x86_64 Linux integration
  matrix verifies compatible extension behavior on PHP 8.2 through 8.5 without transferring the extension's platform
  support policy into this package.
- The companion's `InternalQuantity` class name and native parser ABI remain coordinated internal seams. Applications
  receive compatibility through Yumemi's public `Quantity` and parser contracts, explicit ABI/Unicode gates, and PHP
  fallback rather than by calling those native declarations directly.
- The exhaustive Nix matrix tests PHP 8.2, 8.3, 8.4, and 8.5 from the lock file. Separate conventional jobs perform
  fresh lowest- and highest-dependency solves for released requirements on PHP 8.2 and PHP 8.5. Direct tools required
  from development branches remain at their lock-file revisions because committed generated or copied integrations are
  verified against those exact inputs.
- Newly released PHP 8.x versions allowed by Composer are expected to work but are not considered verified until added
  to the test matrix.
- Runtime-only consumers do not need PHPStan and may coexist with an older PHPStan installation.
- The PHPStan extension requires PHPStan 2.2.5 or later. Yumemi tests its locked dependency and the lowest supported 2.x
  dependency; compatibility with a future PHPStan major version is not implied.
- Automatic registration through `phpstan/extension-installer` and manual inclusion of `extension.neon` are both
  supported installation paths.

Dependency constraints describe installability, not proof that unknown future versions have been tested. When a
dependency's public or internal API changes, the affected adapter should be replaced without altering runtime semantics.

## Classifying Changes

A change is normally breaking when it:

- removes, renames, or incompatibly changes a supported declaration or named argument;
- changes a documented runtime result, exactness policy, unit relation, or failure category;
- changes unit syntax, precedence, canonical semantics, catalog meaning, or source-span convention;
- changes a PHPStan pseudo-type, inferred semantic unit, supported configuration key or default, optional tag rule, or
  stable diagnostic identifier;
- stops reading a supported serialized payload version or changes a documented JSON shape; or
- raises the minimum supported PHP or PHPStan version within a release line that promised the older version.

A change is normally compatible when it:

- adds an optional API or configuration key without changing existing behavior;
- improves performance while preserving observable semantics;
- improves diagnostic or exception prose without changing its category or structured metadata;
- replaces an internal adapter, generated implementation, build tool, or service wiring;
- increases inference precision without invalidating previously valid uses; or
- adds a catalog name that does not alter resolution of any existing expression.

Fixing a defect may change observed behavior. If the old behavior contradicted documented semantics or invariants, the
fix is not required to preserve the defect, but it must include a focused regression test and, when correcting behavior
present in a tagged release, a clear user-facing changelog note. If users were explicitly promised the old behavior,
changing it is a compatibility break even when the new behavior appears cleaner.

## Change Procedure

Before changing a supported or provisional surface:

1. identify its stability class and affected audience;
2. compare the change with public documentation, invariants, and conformance cases;
3. decide explicitly whether the change is compatible, a bug fix, or breaking;
4. update focused tests and portable conformance evidence where applicable;
5. update public documentation and the changelog when required;
6. preserve stable diagnostic identifiers and serialized readers unless the release permits a break; and
7. report any deliberate contract change separately from internal refactoring.

A deliberate breaking change for a later `0.x` minor release must remain explicit: document the migration and changelog
entry, identify why the current release-line contract permits the break, and narrowly acknowledge only the reported
signature changes with exact `ignored-regex` entries in `.roave-backward-compatibility-check.xml` while that release is
prepared. Do not disable the compatibility job or add broad exclusions merely to make it pass. Remove temporary
acknowledgements after the new minor tag becomes the comparison baseline.

Do not broaden compatibility accidentally by documenting internal classes as recommended extension points. Conversely,
do not remove a documented contract merely because its current implementation lacks an `@api` marker.
