# Preferred And Compact Unit Selection Design Spike

Status: **Preferred-unit profiles and named-family compaction implemented**

Reviewed against the working tree on 2026-08-13.

This record defines the deliberately narrow preferred-unit and compact-unit conversion boundary now implemented by the
runtime and PHPStan adapter.

## Decision Summary

Preferred-unit selection and compact-unit selection remain separate operations:

- **Preferred-unit selection** applies an explicit application policy to a quantity's dimension. It answers questions
  such as "display speeds in kilometers per hour" or "display energy in kilowatt hours."
- **Compact-unit selection** chooses an engineering prefix from a caller-selected unit family according to the exact
  magnitude. It answers questions such as "display this length using meter, millimeter, kilometer, or another available
  engineering-prefixed form."
- **Formatting** continues to choose spelling, symbols, typography, and expression layout after a unit has been chosen.
  `FormatOptions` must not acquire conversion policy or inspect magnitudes.

The implementation uses explicit preferred target expressions and exact prefix arithmetic. It does not introduce unit
systems, catalog-wide scoring, approximate logarithms, or a general optimizer.

## API

Preferred targets are collected in an immutable profile bound to one `Units` context:

```php
$displayUnits = $units->preferredUnitProfile([
    'kilometer / hour',
    'kilowatt * hour',
    'millibar',
]);

$displaySpeed = $speed->toPreferred($displayUnits);
```

The implemented public surface is:

```php
Units::preferredUnitProfile(iterable $targets): PreferredUnitProfile;
Quantity::toPreferred(PreferredUnitProfile $profile): Quantity;
```

Compact selection requires the caller to name the unit family:

```php
$distance = $units->quantity(12_500, 'meter')->toCompact('meter');
// exact value: 25/2 kilometer

$mass = $units->quantity(new Rational(1, 100), 'kilogram')->toCompact('gram');
// exact value: 10 gram
```

The implemented public surface is:

```php
Quantity::toCompact(Expr|string $baseUnit): Quantity;
```

Requiring `$baseUnit` avoids pretending that the catalog can infer an application's preferred system. It also handles
the kilogram correctly: callers select the prefix family rooted at `gram`, rather than receiving surprising units such
as `millikilogram`. A later optional-default overload should require evidence from real use.

## Preferred-Unit Profile

Each profile target defines both its dimension key and its complete output expression. Compound targets are therefore
ordinary explicit policy rather than a basis-solving problem:

```text
length / time -> kilometer / hour
mass * length^2 / time^2 -> kilowatt * hour
mass / (length * time^2) -> millibar
```

Profile construction parses and validates every target once through its `Units` context. The profile:

- accept multiplicative unit expressions, including named and compound units;
- require a symbolic unit expression without an explicit numeric multiplier after symbolic reduction;
- derive the dimension from the target instead of accepting a separately maintained dimension key;
- allow at most one target per dimension and reject duplicates instead of making insertion order observable;
- retain the reduced symbolic target spelling supplied by the application; and
- reject affine coordinate units, logarithmic units, and unsupported expressions through existing runtime semantics.

Catalog definitions may contain scale factors. The restriction on explicit numeric multipliers must not reject ordinary
named units such as `percent`, whose scale appears only after catalog resolution.

`toPreferred()` requires object-identical `Units` contexts, as quantity arithmetic already does. A profile built for
another context throws `IncompatibleQuantityContextException`, even if the registries happen to contain equivalent
definitions. This makes custom units deterministic and prevents a profile from being silently reinterpreted after
crossing a registry boundary.

When the profile contains a target for the quantity's dimension, `toPreferred()` performs the same exact conversion as
`to()`. When no target exists, it returns the immutable quantity unchanged. This best-effort behavior lets one profile
format heterogeneous result sets without requiring entries for every dimension.

A dimension is not a quantity kind. Gray and sievert intentionally share dimensions, and the bundled information units
are dimensionless, so a data rate and a frequency can both have dimension `1 / time`. A profile must therefore be scoped
to an application boundary where its dimension-to-target choices are meaningful; it is not a universal global policy.
When that distinction matters within one boundary, use separate profiles or explicit `to()` calls. Preferred selection
must not claim to infer semantic intent that the current model does not represent.

The profile is not serializable. Application configuration is its authority, and restoring a profile would otherwise
require the same custom-registry context problem already handled explicitly for quantities.

## Compact Selection

The compactor supports one named, multiplicative unit family. A named derived unit such as `watt` or `newton` is
eligible; a structural compound such as `meter / second` is not. Applications can use a named custom unit or a preferred
profile when a compound expression needs a stable display target.

The algorithm is deterministic and exact:

1. Parse the requested base unit and require one named unit after symbolic reduction.
2. Resolve its canonical name and verify multiplicative conversion support.
3. Enumerate canonical prefixes supplied by the same immutable registry.
4. Retain prefixes whose exact scale is `10^(3n)` for an integer `n`; include the unprefixed scale `10^0`.
5. Construct each candidate in that prefix family and retain it only when registry lookup and exact conversion confirm
   the expected scale and dimension. Exact-name collisions must not be mistaken for valid prefixed units.
6. Convert the magnitude to the unprefixed base exactly, use its absolute value for selection, and choose the candidate
   whose absolute magnitude lies in the half-open interval `[1, 1000)`.
7. At the available prefix limits, select the smallest or largest candidate rather than failing or using approximate
   notation.
8. Convert to the selected candidate with the existing exact conversion path.

If a rejected exact-name collision leaves an internal gap, selection uses the greatest available scale not exceeding the
absolute magnitude. This preserves the lower bound and may leave the output at or above `1000` rather than admitting a
false candidate.

No binary floating-point logarithm is necessary. Prefix scales and interval comparisons can use `Rational`, preserving
determinism for very large and very small values.

For duplicate prefix spellings with the same scale, the registry's canonical prefix name is used. The result carries a
canonical unit name; `FormatOptions` may subsequently render its symbol. This keeps semantic selection independent of
presentation.

Zero has no meaningful scale. `toCompact()` converts zero to the unprefixed base selected by the caller. Negative values
use their absolute magnitude for prefix selection and retain their sign. Exact boundaries are intentional:

```text
999 meter  -> 999 meter
1000 meter -> 1 kilometer
1/1000 meter -> 1 millimeter
0 meter -> 0 meter
```

If no eligible engineering prefix exists, the unprefixed base remains a valid one-candidate family. Structurally invalid
base expressions fail explicitly with `UnsupportedUnitCompactionException` rather than returning an unchanged quantity
and concealing a caller mistake.

## Custom Registries

Profiles work with custom registries without special handling because their targets are parsed by the bound `Units`
context. A profile may therefore select application-defined dimensions and compound custom units.

Compaction likewise uses prefixes from the quantity's registry, not a hard-coded SI table. A default-derived custom
registry inherits the generated UDUNITS2 prefixes, so application-defined units can participate when the synthesized
candidate names resolve correctly. An empty custom registry currently exposes no prefixes and therefore produces only
the unprefixed candidate.

The implementation does not add prefix mutation to `UnitRegistryBuilder`. A later `prefix()` builder method is
compatible with this design because the compactor consumes the registry's existing prefix interface. It should be added
only when a custom-registry use case needs independently authored prefixes.

## PHPStan Boundary

Both operations choose a unit from runtime state:

- `toPreferred()` depends on the contents of a profile object;
- `toCompact()` depends on the quantity's runtime magnitude.

PHPStan therefore cannot generally name one resulting `Quantity<'...'>` brand. The quantity method return extension must
explicitly return unbranded `Quantity` for these methods rather than incorrectly preserving the receiver's brand. This
is a deliberate loss of static unit identity at a presentation boundary, not a union of every possible same-carrier
unit.

Public guidance recommends these operations near output boundaries. Code that needs continued statically known unit
arithmetic uses explicit `to('target')` conversion instead. A future static refinement is appropriate only if an API
makes one target expression statically recoverable; the initial profile and magnitude-based compactor do not.

## Point Quantities And Other Non-Goals

`PointQuantity` is outside the initial design. Compacting affine coordinates is usually semantically inappropriate, and
preferred coordinate scales should remain explicit `to()` conversions until a concrete application demonstrates a
coherent policy.

The initial implementation also excludes:

- automatic choice among metric, imperial, US customary, nautical, or domain-specific systems;
- decomposition into a preferred basis or a Pint-style optimization solver;
- compaction of structural products, quotients, or powered expressions;
- binary prefixes, unless a registry supplies them and a future policy explicitly defines their selection;
- localization or locale-dependent preferences;
- automatic compaction of branded native scalars; and
- presentation methods that combine conversion, decimal rounding, and formatting in one call.

These exclusions preserve the existing distinction among conversion, numeric output, and formatting.

## Alternatives Considered

### Put selection in `FormatOptions`

Rejected. Formatting currently receives an expression, not a magnitude, and does not change values. Adding selection
would make a presentation object perform exact conversion and blur the documented runtime boundary.

### Select a catalog-wide best unit

Rejected. Dimensional compatibility cannot say whether length should be expressed in meters, feet, nautical miles, or a
custom business unit. Downloading more catalog metadata does not supply application intent.

### Accept a list of preferred basis units

Deferred. General decomposition requires a deterministic optimizer and collision policy, especially when several units
span the same dimensions. Explicit complete target expressions solve the common application-policy case without adding
an integer or numerical optimization dependency.

### Combine preference and compaction

Rejected. A profile may select `meter` while compaction subsequently selects `kilometer`; either operation remains
useful without the other. Keeping them composable makes their contracts and tests substantially clearer.

### Infer the compaction family from the current unit

Deferred. Exact catalog names win before prefix decomposition, so `kilogram` and custom exact names cannot always reveal
an unambiguous prefix root. Requiring the root avoids hidden catalog heuristics in the first release.

## Implementation Slices

Implement and review this feature in separate working commits:

1. **Preferred targets (implemented):** add `PreferredUnitProfile`, the `Units` factory, `Quantity::toPreferred()`,
   context and validation tests, unbranded PHPStan fallback, public documentation, changelog, and conformance cases.
2. **Named-unit compaction (implemented):** add the internal exact prefix selector, `Quantity::toCompact()`, the
   dedicated exception, boundary and custom-registry tests, unbranded PHPStan fallback, public documentation, changelog,
   and conformance cases.
3. **Evidence-driven extensions:** consider compound compaction, authored custom prefixes, or static refinements only
   after the first two slices have real consumers.

The preferred-profile slice is the lower-risk first implementation because it delegates conversion to existing exact
semantics and does not require a new selection algorithm.

## Required Verification For Implementation

Preferred-profile coverage includes exact compound conversion, custom dimensions, dimensionless targets,
duplicate-dimension rejection, unsupported semantics, context mismatch, a missing-profile no-op, and source spelling.

Compaction coverage includes positive, negative, and zero values; exact lower and upper interval boundaries;
available-prefix saturation; canonical prefix aliases; exact-name collisions; kilogram through an explicit `gram` base;
named derived and custom units; registries without prefixes; unsupported compound and affine targets; and exact rational
preservation.

Both slices add PHPStan tests proving that the result becomes an unbranded `Quantity` and that no same-carrier union is
invented. Runtime behavior is part of the conformance corpus because target selection and exact output are observable
public semantics.

## External Reference

Pint keeps preferred conversion and compact conversion separate, which supports this boundary, but Yumemi should not
copy Pint's general preferred-basis optimizer or floating-point compaction implementation. See Pint's
[`Quantity.to_preferred()` and `Quantity.to_compact()` documentation](https://pint.readthedocs.io/en/latest/api/base.html)
and its [conversion implementation](https://github.com/hgrecco/pint/blob/master/pint/facets/plain/qto.py).
