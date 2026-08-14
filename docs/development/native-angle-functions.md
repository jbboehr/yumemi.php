# Native Angle Function Design Spike

Status: **Design complete; implementation deferred to the slices below.**

This spike defines how Yumemi's PHPStan extension should model PHP's native angle conversion and trigonometric
functions. It does not add runtime wrappers or change unbranded PHP calls.

## Decision Summary

Yumemi should model these functions when their relevant operands carry native unit brands. This table is a proposed
contract for the implementation slices, not a description of current inference:

| Function family | Proposed required branded input | Proposed branded output |
| --- | --- | --- |
| `deg2rad()` | canonical `arc_degree` | `unit_float<'radian'>` |
| `rad2deg()` | canonical `radian` | `unit_float<'arc_degree'>` |
| `sin()`, `cos()`, `tan()` | canonical `radian` | `unit_float<'1'>` |
| `asin()`, `acos()`, `atan()` | exact unscaled ratio `1` | `unit_float<'radian'>` |
| `atan2()` | two definitionally equivalent branded numeric operands | `unit_float<'radian'>` |

Under this proposed contract, Yumemi performs no runtime conversion and does not wrap the result. A bare call remains
owned by PHPStan and retains PHPStan's native `float` result.

The first implementation should use one stable diagnostic identifier:

```text
yumemi.invalidUnitAngleFunction
```

It should cover an angle function receiving a known but invalid unit, mixed branded and unbranded `atan2()` operands,
and branded `atan2()` operands whose units are not definitionally equivalent. PHPStan remains responsible for missing
arguments, nonnumeric arguments, and ordinary native signature errors.

## Why Angle Identity Must Be Stricter

The default catalog correctly treats plane angle as dimensionless. Consequently, normalized equivalence alone is too
broad for native angle functions:

- `radian`, `1`, `steradian`, and `meter / meter` all normalize to dimensionless one;
- `arc_degree` and `degree_north` have the same exact scale;
- `percent` is dimensionless but has a scale of `0.01`.

PHP nevertheless defines `sin()` in radians and `deg2rad()` in ordinary angular degrees. No runtime conversion occurs
before those functions receive the scalar. Accepting every definitionally equivalent or dimensionally compatible unit
would therefore admit solid angles, directional coordinates, percentages, and other semantically different values.

For the fixed-unit unary functions, resolve a simple unit spelling through registry metadata and require its canonical
name to be the verified `radian` or `arc_degree` entry. This accepts catalog aliases such as `rad` and `degree` while
rejecting independently named lookalikes such as `steradian`, `degree_north`, `arc_minute`, and `turn`, even when their
resolved expressions or scales compare equally. Composite expressions do not acquire canonical angle identity merely
by reducing to the same value.

Inverse trigonometric functions should require an expression structurally equal to dimensionless one. Reduced ratios
such as `meter / meter` satisfy that requirement. Named dimensionless units such as `radian`, `steradian`, `count`, and
scaled units such as `percent` do not. Callers can convert or deliberately rebrand a value at the boundary when its
ratio semantics are known.

This is a function-contract distinction, not a new global relation among units. Ordinary assignment, arithmetic, and
conversion continue to use Yumemi's established structural, definitional, and dimensional relations.

## Ownership And Fallback

The extension should claim a call only when at least one relevant operand carries a Yumemi numeric brand and the
complete operand type can be analyzed safely.

- A wholly unbranded call returns `null` from the expression resolver and produces no Yumemi diagnostic.
- A union containing valid branded arms is transformed only when every possible numeric arm has a definite valid
  interpretation.
- A known invalid branded arm produces the angle diagnostic even when another arm is valid.
- A nonnumeric arm causes Yumemi to defer to PHPStan's native signature checking rather than duplicate its diagnostic.
- `unit_numeric_string` is not accepted implicitly. The caller must use an explicit numeric cast or conversion first.
- First-class callables, incomplete calls, and namespaced functions shadowing a native name remain unclaimed.
- Positional and native named arguments are both supported (`num`, or `y` and `x` for `atan2()`).

This follows the existing resolver/rule division: one analysis method computes either an inferred type, a Yumemi
message, or a neutral result; the expression extension consumes the type and the rule emits the message.

## Unary Functions

`deg2rad()` and `rad2deg()` are explicit scale conversions with fixed canonical units. The proposed contract accepts
branded integers and floats and returns a float branded respectively as `radian` or `arc_degree`.

`sin()`, `cos()`, and `tan()` consume canonical radians. Their result is an unscaled ratio, represented as
`unit_float<'1'>` rather than a bare float under the proposed contract.

`asin()`, `acos()`, and `atan()` reverse that relation for branded unscaled ratios, with a proposed canonical
`unit_float<'radian'>` result.

The extension should preserve finite constant results when PHPStan knows an exact input constant. If the native result
is `INF` or `NAN`, retain only the output brand, matching the existing finite-constant policy for modeled built-ins.
This is binary floating-point evaluation by the native PHP function, not exact rational trigonometry.

The static rule should reject direct misuse rather than silently relabeling the result. In particular, the planned
contract rejects an `arc_degree` brand passed directly to `sin()` and a `percent` brand passed directly to `asin()`.

Callers must convert explicitly to `radian` before passing a scalar represented in another angular scale to direct
trigonometric functions.

## `atan2()`

The proposed `atan2($y, $x)` contract accepts coordinates, vector components, or other magnitudes only when both
branded operands have one definitionally equivalent unit. The common unit may be dimensional or dimensionless; the
quotient implicit in the angle calculation cancels it. The proposed output is canonical radians. For example, `meter`
and `100 * centimeter` operands satisfy the unit relation.

Dimensionally compatible but differently scaled operands remain invalid because PHP does not convert either scalar:
the planned rule rejects a `meter` operand paired with a `foot` operand.

Mixing one branded operand with one bare numeric operand is also diagnosed, following `fmod()` and `hypot()`. A wholly
bare `atan2()` call remains an ordinary PHPStan `float` expression.

Cartesian union analysis must fail closed for any mixed or incompatible pair. Ordinary operand unions remain ordinary;
do not add an angle-specific benevolence policy in the first implementation. Defer benevolent-union propagation until
a reachable native input demonstrates which established policy should apply.

## Custom Registries

The configured PHPStan registry is authoritative for parsing user brands, but PHP's meanings of radians and degrees are
not application-configurable. Normalized expressions cannot establish that identity: a custom `radian` alias targeting
`count` would still normalize to dimensionless one, and a replacement `arc_degree` could reproduce the expected scale
without retaining the canonical unit.

Before enabling angle inference, the implementation should verify that the effective configured entries for `radian`
and `arc_degree` retain the bundled canonical catalog identity. The check should compare each complete effective entry,
including its prebuilt and catalog-record channels, with the immutable bundled entry rather than comparing only selected
dimensions or definitions. A shadowing alias, replacement record, or prebuilt unit must fail this check even when it
normalizes to the expected value. If the available registry API cannot establish that identity, the extension should
decline the call. The dimensionless literal `1` does not require a named registry entry.

If those references are absent or redefined, the angle extension should decline calls rather than crash analysis or
infer units from altered meanings. An empty or specialized custom registry must remain usable for unrelated Yumemi
features. This limitation should be documented publicly when the feature ships.

Aliases added by a valid custom registry may be accepted only when registry metadata resolves their canonical name to
one of those verified entries. Structural or normalized equality alone is insufficient.

## Proposed Implementation Shape

Prefer one `UnitAngleFunctionTypeResolverExtension` and one `InvalidUnitAngleFunctionRule`. The resolver should receive
the configured `UnitExpressionParser`, configured registry metadata, and a bundled registry or equivalent immutable
reference contract. A small internal function table can describe each unary function's required input, output, and
native evaluator; `atan2()` should use a separate binary path because its validation is relational.

Do not add runtime wrappers, public classes, configuration flags, or a general nominal-dimension abstraction for this
feature. The special identity checks belong to the fixed contracts of these native functions.

When implementation begins, register the new source paths in the PHPStan Infection filter and the extension service in
`extension.neon`. Add the stable diagnostic to the inventory, compatibility policy, PHPStan reference, local-ignore
fixture, and changelog in the same slice that first emits it.

## Implementation Slices

### Slice 1: Explicit angle conversion

Implement `deg2rad()` and `rad2deg()` with:

- verified canonical reference units;
- alias-aware canonical-identity checks;
- finite constant inference;
- bare and unsupported fallback;
- namespaced-shadow and named-argument handling;
- the initial diagnostic rule and identifier.

This slice establishes the shared unary machinery with the smallest semantic surface.

### Slice 2: Direct and inverse trigonometry

Extend the unary table with `sin()`, `cos()`, `tan()`, `asin()`, `acos()`, and `atan()`. Lock in the branded
dimensionless result, exact-unscaled-ratio input rule, domain/non-finite constant behavior, and union policy.

### Slice 3: Two-argument direction

Add `atan2()` using definitionally equivalent operand checks, mixed-brand diagnostics, complete Cartesian union
validation, and canonical radian output. Compare its union behavior explicitly with native `/`, `fdiv()`, `fmod()`, and
`hypot()` rather than copying one resolver mechanically.

Pause after each slice for review. Do not combine `intdiv()`, generalized `pow()`, hyperbolic functions, logarithms, or
runtime approximate quantity arithmetic with this work.

## Verification Plan

Each implementation slice should include focused resolver tests and end-to-end PHPStan fixtures covering:

- accepted canonical names and catalog aliases;
- rejected dimensionless and directional lookalikes;
- branded integer, float, constant, range, union, and benevolent-union inputs where reachable;
- bare calls and nonnumeric alternatives delegated to PHPStan;
- finite and non-finite constant outputs;
- positional and named arguments;
- missing arguments and first-class callables;
- namespaced shadows;
- custom registries with valid, absent, and redefined angle references;
- stable diagnostics and identifier-specific local ignores;
- exact inferred source spellings for output brands;
- mutation coverage for the shared analyzer and rule.

Run focused tests, formatting, PHPStan, the complete PHPUnit suite, and the ordinary Nix gate for each completed slice.
Benchmark the combined built-ins fixture after the final slice; do not add a dedicated performance optimization unless
the measured extension cost is material.

## Alternatives Rejected

- **Accept every dimensionless value as radians.** This admits solid angles, counts, percentages, and unrelated ratios
  without runtime conversion.
- **Accept every angle-compatible value.** Plane angle is dimensionless in the catalog, and PHP does not convert degrees
  or other scales before calling trigonometric functions.
- **Return bare floats from branded direct trig calls.** This discards known ratio semantics and breaks the natural
  `asin(sin($angle))` branded flow.
- **Brand all bare native calls.** This would alter ordinary PHP code merely because the Yumemi extension is installed.
- **Introduce a nominal plane-angle dimension.** That would contradict the current UDUNITS2-compatible dimension model
  and is disproportionate to native function inference.
- **Add approximate trigonometry to `Quantity` in the same change.** Runtime approximation needs an explicit numeric
  precision contract and remains a separate design problem.
