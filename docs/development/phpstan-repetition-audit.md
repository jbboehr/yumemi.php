# PHPStan Repetition Audit

Snapshot date: 2026-08-15

## Purpose

This audit reviews repeated implementation patterns in `src/PHPStan/` and distinguishes shared semantic machinery from
similar-looking code that enforces different contracts. The objective is not to minimize line count. It is to centralize
small, historically error-prone policies without hiding the behavior of individual PHPStan extensions.

The adapter currently contains 52 authored PHP files and about 10,900 lines including headers, PHPDoc, and logia. Raw
size therefore overstates executable duplication. The audit searched direct union traversal, branded numeric extraction,
native-function ownership checks, result-union construction, and resolver/rule pairs, then compared each occurrence
against the semantic invariants and focused regression history.

## Findings

### Direct Union Handling Is The Best Consolidation Candidate

Immediate `UnionType` expansion appears in angle, binary-math, extrema, comparison, operator, root, conversion, and
branded-type code. Root, scalar-preserving, unary-angle, and binary-math resolvers also repeat result recombination and
some form of benevolent-union preservation.

This policy is small but semantically important. Previous defects have shown that:

- recursively traversing nested component types can mistake a callable or container for a branded scalar;
- allowing one benevolent operand to make an ordinary multi-arm operand benevolent can admit an unsound result;
- a resolver must retain its existing fail-closed policy when one arm is bare, nonnumeric, or invalid;
- `TypeCombinator::union()` may collapse the mapped result to one type, in which case there is no union to mark as
  benevolent.

A narrow internal helper can own only two operations:

1. return the direct alternatives of a top-level `UnionType`, without using `TypeTraverser` or descending into callable,
   array, generic, shape, or object component types;
2. combine mapped results while preserving benevolence only when at least one source is benevolent and no multi-arm
   source is an ordinary union.

The helper must not decide whether an arm is valid, extract unit metadata, create diagnostics, or choose a result unit.
Those policies remain with each resolver.

### Numeric Operand Extraction Is Similar But Not Yet Uniform

`UnitFloatType::extract()` is used in ten PHPStan source files and `UnitIntegerTypeHelper::extract()` in thirteen.
Several call sites then derive a unit, constant value, integer bounds, or carrier kind. This resembles a shared operand
model, but the consumers intentionally differ:

- `fdiv()` permits branded values paired with bare numeric operands, while `fmod()` and `hypot()` do not;
- angle functions require verified canonical identity or an exact unscaled ratio, not ordinary dimensional or
  definitional equivalence;
- extrema distinguish required, optional, unpacked, and possibly empty array candidates;
- comparisons distinguish unsupported nonnumeric alternatives from invalid branded/bare combinations;
- integer operators need bounds and overflow behavior that float-returning functions do not.

Do not introduce a general native-function evaluator or carrier-neutral operand abstraction now. After direct union
handling is shared, reconsider a read-only top-level operand-facts value only if at least two consumers still have
byte-for-byte equivalent extraction policy and the abstraction removes more branching than it adds.

### Resolver And Diagnostic Rule Pairs Should Remain Explicit

The angle, binary-math, root, and extrema diagnostic rules all call a resolver's `analyseCall()` method and turn a
message into one stable identifier. Their executable wrappers are short. A generic base rule or callback interface would
save little while making service registration, diagnostic ownership, and identifier lookup less direct.

Continue sharing each resolver's actual analysis with its companion rule. Do not consolidate the rule classes unless a
future family adds materially more repeated behavior than the present call-and-build wrapper.

### Native Function Ownership Guards Are Low-Value Repetition

Several resolvers check for a `FuncCall`, reject first-class callables, use `ReflectionProvider` to respect namespaced
shadowing, and compare the resolved native function name. This is deliberate integration boilerplate and usually fewer
than ten lines per family. A shared guard would need to accept different function sets and argument policies while
coupling an otherwise simple helper to PHPStan reflection.

Keep these checks local. Reconsider only if another substantial group of native-function resolvers is added and the
guard itself acquires tested semantics beyond name resolution.

### Existing Shared Helpers Are At The Right Boundaries

Retain the established helpers rather than broadening them:

- `NativeUnitArgumentResolver` owns positional/named argument lookup and exact finite constant-string extraction;
- `UnitExpressionAlgebra` owns unit multiplication, division, inversion, powers, and exact roots;
- `UnitIntegerTypeHelper` owns direct branded integer extraction, integer constraints, and branded range construction;
- `UnitFloatType` owns direct branded float extraction and construction;
- `ShouldNotHappenException` owns translation of unexpected adapter failures.

Quantity and point return extensions should also remain separate. Multiplicative quantities and affine points have
different conversion, arithmetic, and identity rules even where their control flow looks similar.

## Recommended Implementation Slices

### Slice 1: Introduce And Prove The Direct-Union Helper (implemented 2026-08-15)

`UnitUnionTypeHelper` now provides direct-alternative and mapped-result-combination operations. Its independent tests
cover:

- one non-union input;
- an ordinary union;
- a benevolent union;
- two benevolent sources;
- one benevolent source paired with one ordinary multi-arm source;
- a result collapsed by `TypeCombinator` to one type;
- a callable and an array containing branded types, proving that their components are never traversed.

The helper must preserve source order only where PHPStan promises it; callers that require deterministic diagnostics
should continue sorting their own display strings.

### Slice 2: Migrate Unary Mappers (implemented 2026-08-15)

`UnitRootFunctionTypeResolverExtension` and `UnitPreservingFunctionTypeResolverExtension` now use the helper. Root
diagnostics remain local, and focused coverage preserves the scalar-preserving resolver's immediate fallback when any
mapped arm is unsupported.

### Slice 3: Migrate Binary Math (implemented 2026-08-15)

The ordinary binary-math and `intdiv()` Cartesian paths now use the helper. Function-specific branded/bare and
unit-equivalence policy remains local. Focused unit and PHPStan inference coverage preserves the established rule that
any ordinary multi-arm operand keeps the result ordinary, even when another operand is benevolent.

### Slice 4: Reassess, Do Not Automatically Expand (completed 2026-08-15)

Unary angle mapping matched the helper contract and now uses it. `atan2()` remains local and ordinary because its
correlated binary policy intentionally does not preserve source benevolence. Extrema, comparison, conversion, and
operator code remain unchanged because their array, correlation, diagnostic, or nested-result behavior does not
independently match the helper's contract.

## Success Criteria

The refactor is worthwhile only if it:

- removes duplicate direct-union and benevolence policy from at least the root, preserving, and binary-math resolvers;
- makes the ordinary-versus-benevolent rule readable in one place;
- introduces no recursive type traversal;
- preserves all public inferred types and diagnostic identifiers;
- keeps resolver-specific failure policy visible at each call site;
- reduces executable branching without introducing a general adapter framework.

If these criteria cannot be met with one small helper, retain the local implementations.
