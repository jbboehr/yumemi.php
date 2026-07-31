# Built-In and Custom Units

Yumemi ships a generated unit catalog derived from UDUNITS2. The same catalog drives runtime name resolution,
conversion, formatting, and PHPStan analysis.

The imported UDUNITS2 material is distributed under the terms in
[UDUNITS-COPYRIGHT](https://github.com/jbboehr/yumemi.php/blob/master/docs/UDUNITS-COPYRIGHT). Yumemi's own code remains
under the project license described in the root README and license files.

## Default Catalog

`Units::default()` uses `Udunits2UnitRegistry` and the checked-in `data/udunits2.php` catalog. The generated data
includes:

- base, dimensionless, and derived units;
- canonical names, aliases, symbols, explicit plurals, and unambiguous generated plurals;
- multiplicative difference units synthesized from affine definitions, including `delta_celsius`, `delta_fahrenheit`,
  `Δ°C`, and `Δ°F`;
- decimal and scientific prefix definitions;
- source definitions, comments, and documentation when present upstream.

Lookup is case-sensitive. Exact names win before dynamic prefix decomposition, and prefixes apply only when the
remaining suffix is an exact unit name. See the [unit syntax reference](unit-syntax.md#unit-names) for examples.

## Introspection

`Units::describe()` first describes an exact catalog spelling and follows aliases to its canonical entry. If no exact
entry exists, it applies the same one-prefix-plus-exact-unit decomposition used by ordinary resolution.
`Units::describePrefix()` describes one exact prefix name or symbol. Descriptors preserve whether the matched spelling
was canonical, an alias, a symbol, an explicit plural, a generated plural, or dynamically prefixed.

`describe()` does not accept compound expressions as lookup names, normalize definitions, or add dynamically prefixed
spellings to `names()`. Generated affine-difference units are exact catalog entries and do appear in `names()`. To
report truthful capabilities, introspection lazily resolves the complete canonical or dynamically prefixed spelling
against the effective registry. A dynamically prefixed descriptor exposes its prefix and exact residual unit through
`prefixDecomposition`:

```php
<?php

use jbboehr\Yumemi\Catalog\CatalogNameKind;
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Units;

$units = Units::default();
$kilopascal = $units->describe('kPa');

assert($kilopascal !== null);
assert($kilopascal->canonicalName === 'kilopascal');
assert($kilopascal->matchedAs === CatalogNameKind::Prefixed);
assert($kilopascal->definitionExpression === '1e3 * pascal');
assert($kilopascal->isDynamicallyPrefixed());
assert($kilopascal->semantics === UnitSemantics::Multiplicative);
assert($kilopascal->supportsMultiplicativeAlgebra());
assert($kilopascal->supportsConversion());

assert($kilopascal->prefixDecomposition !== null);
assert($kilopascal->prefixDecomposition->prefix->matchedName === 'k');
assert($kilopascal->prefixDecomposition->prefix->canonicalName === 'kilo');
assert($kilopascal->prefixDecomposition->prefix->matchedAs === CatalogNameKind::Symbol);
assert($kilopascal->prefixDecomposition->unit->matchedName === 'Pa');
assert($kilopascal->prefixDecomposition->unit->canonicalName === 'pascal');
assert($kilopascal->prefixDecomposition->unit->matchedAs === CatalogNameKind::Symbol);
```

The synthesized descriptor's top-level alias, symbol, and plural lists are empty because it is not an exact catalog
entry. The residual descriptor retains the underlying unit's complete metadata. Exact spellings still take precedence:
`Pa` describes the exact pascal symbol rather than decomposing as peta-are.

Prefixed affine and logarithmic names receive synthesized descriptors whose top-level semantics are
`UnitSemantics::UnsupportedExpression`, because the complete prefixed spelling is executable by neither runtime path.
The residual descriptor retains `UnitSemantics::Affine` or `UnitSemantics::Logarithmic`, identifying the underlying
reason. Use `supportsMultiplicativeAlgebra()` and `supportsConversion()` to inspect concrete capabilities.

## Custom Registries

Use `UnitRegistryBuilder::default()` to layer custom definitions and aliases over UDUNITS2. Use
`UnitRegistryBuilder::empty()` for an isolated catalog.

Definitions use the normal unit language and are parsed against the completed registry on first use. Multiplicative
definitions work throughout the runtime. An affine definition such as `degree_widget = kelvin @ 100` works as a
coordinate scale and receives a generated multiplicative `delta_degree_widget` definition. The affine name remains
unavailable to expression and `Quantity` algebra; use `PointQuantity` for coordinates and the generated delta name for
differences. The builder is mutable: each fluent method updates and returns the same builder. Every `build()` call
creates an immutable registry snapshot that is unaffected by later builder changes.

```php
<?php

use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;

$registry = UnitRegistryBuilder::default()
    ->define('widget = 12 * meter')
    ->define('degree_widget = kelvin @ 100')
    ->alias('widgets', 'widget')
    ->build();

$units = new Units($registry);

assert($units->quantity(2, 'widgets')->valueIn('meter')->toString() === '24');
assert($units->describe('widgets')?->canonicalName === 'widget');
assert($units->convert(0, 'degree_widget', 'kelvin')->toString() === '100');
assert($units->point(0, 'degree_widget')->valueIn('kelvin')->toString() === '100');
assert($units->deltaQuantity(2, 'degree_widget')->valueIn('kelvin')->toString() === '2');
```

An overlay definition wins over a base UDUNITS2 record with the same name. Aliases resolve through the composed
registry, so an overlay alias may target either another custom definition or a base catalog unit. Affine delta synthesis
runs when `build()` creates the immutable snapshot, after all overlay definitions and aliases are known. An explicit
overlay name that conflicts with one of its generated `delta_*` or `Δ` names is rejected rather than silently replaced.

For PHPStan, configure one `UnitRegistryFactory` that returns the complete registry. Runtime code should construct its
`Units` context from the same registry. PHPStan assumes one authoritative registry for an analysis run and does not
track a separate catalog identity for each value.

## Catalog Semantic Support

The expression and quantity models intentionally support only multiplicative unit algebra with integer powers.
`UnitSemantics` describes the capabilities of the complete name represented by a descriptor:

- `Multiplicative` supports expression algebra and conversion.
- `Affine` rejects expression algebra but supports explicit conversion.
- `Logarithmic` identifies a direct logarithmic definition and supports neither operation.
- `UnsupportedExpression` identifies a complete expression that supports neither operation, including invalid
  composites, malformed or cyclic custom definitions, missing dependencies, and invalid prefixes.

Known affine and logarithmic UDUNITS2 definitions remain in the generated catalog. Aliases are classified through their
canonical entry, and direct custom `@` or `lg(...)` definitions receive the same classification. Descriptors lazily
resolve and cache capabilities against the effective registry, so transitive definitions and overlays cannot leave
capability methods out of sync with runtime behavior.

Raw rows returned by `findCatalogRecord()` remain declaration metadata: they store direct or exact-name-inherited affine
and logarithmic markers, but do not eagerly materialize `UnsupportedExpression` or transitive composite results.
Generated delta rows are ordinary multiplicative declarations materialized during catalog import or immutable-registry
build. This keeps catalog generation deterministic and avoids resolving the full catalog merely for introspection.

Affine classification means "unsupported by multiplicative `Expr` algebra," not "unsupported everywhere." See
[Affine Conversion](runtime.md#affine-conversion) for executable boundaries and [Limitations](phpstan.md#limitations)
for static-analysis behavior. Logarithmic definitions remain unsupported at every execution boundary; their descriptors
nevertheless distinguish them from unknown names and preserve the canonical unit, semantics, and original definition for
diagnostics.

Contributors changing the imported data or generator should follow
[Regenerating the UDUNITS2 Catalog](../contributing/catalog-generation.md).
