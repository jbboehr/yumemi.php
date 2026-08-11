# Built-In and Custom Units

<figure class="logion" data-logion="SFA 84:36">
<div class="logion-text">
<blockquote>
<p>The painted eclipse upon the archive ceiling darkeneth no field, yet it preserveth the hour when the proud astronomers confessed their limit. Condemn not every likeness; ask whether it kneels before the event it remembers, or would supplant the heaven.</p>
</blockquote>
<p class="logion-citation">— <cite>Scholia of the Fifth Archive 84:36</cite></p>
</div>
<img src="../images/logia/SFA-84_36.webp" alt="Astronomers beneath a painted eclipse and an open rose-gold heaven in an imperial archive" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Yumemi combines a generated unit catalog derived from UDUNITS2 with a small authored supplement. The same composed
catalog drives runtime name resolution, conversion, formatting, and PHPStan analysis.

The imported UDUNITS2 material is distributed under the terms in
[UDUNITS-COPYRIGHT](https://github.com/jbboehr/yumemi.php/blob/master/docs/UDUNITS-COPYRIGHT). Yumemi's own code remains
under the project license described in the root README and license files.

## Default Catalog

`Units::default()` layers the checked-in `data/yumemi.php` supplement over the generated `data/udunits2.php` catalog.
The generated UDUNITS2 data includes:

- base, dimensionless, and derived units;
- canonical names, aliases, symbols, explicit plurals, and unambiguous generated plurals;
- multiplicative difference units synthesized from affine definitions, including `delta_celsius`, `delta_fahrenheit`,
  `Δ°C`, and `Δ°F`;
- decimal and scientific prefix definitions;
- source definitions, comments, and documentation when present upstream.

Common accepted spellings include long names such as `meter`, `foot`, `second`, `kilogram`, and `celsius`; symbols such
as `m`, `ft`, `s`, `kg`, and `Pa`; and composed expressions such as `kilometer / hour`. These examples are not
exhaustive. Names and symbols remain case-sensitive, and aliases such as `foot` may resolve to a more specific canonical
name such as `international_foot`.

The authored supplement provides exact units needed by image and document APIs:

| Unit                  | Meaning                                                 |
| --------------------- | ------------------------------------------------------- |
| `pixel`               | Base unit of the nominal `image_sample` dimension       |
| `css_pixel`           | CSS reference length equal to exactly `inch / 96`       |
| `typographic_point`   | Modern publishing point equal to exactly `inch / 72`    |
| `twip`                | Twentieth of a typographic point, exactly `inch / 1440` |
| `english_metric_unit` | Office Open XML EMU, equal to exactly `inch / 914400`   |

The corresponding plurals are accepted, and `EMU` is a symbol for `english_metric_unit`. The CSS relationships follow
the [W3C absolute-length definitions](https://www.w3.org/TR/css-values-4/#absolute-lengths). The EMU relationship
follows the
[Microsoft Office Drawing specification](https://learn.microsoft.com/en-us/openspecs/office_standards/ms-odrawxml/f1ca887b-11d5-4cf6-acb1-acc0b4fb5dca).

Raster `pixel` is deliberately not a length. A conversion from pixels to inches requires a resolution, represented by an
expression such as `pixel / inch`; `pixel` is therefore incompatible with `css_pixel`, `inch`, and `meter`. `css_pixel`
represents the separate CSS reference length. The ambiguous abbreviation `px` is not defined.

Existing UDUNITS2 spellings retain their meanings: `pt` is the US liquid-pint symbol, and `pica` is the historical
printer's pica based on `printers_point`. Likewise, `dpi` and `ppi` remain prefix decompositions of `pi`, not density
units. Use `typographic_point` and explicit density expressions rather than relying on those abbreviations.

The supported application path for customization is `UnitRegistryBuilder`. Its optional alternate generated-catalog file
parameters and the concrete registry implementations are lower-level generation and testing boundaries, not stable
application APIs. The generated PHP array layout is likewise not an application data format. Use builder definitions and
aliases when an application needs custom units.

Lookup is case-sensitive. Exact names win before dynamic prefix decomposition, and prefixes apply only when the
remaining suffix is an exact unit name. See the [unit syntax reference](unit-syntax.md#unit-names) for examples.

## Custom Registries

Use `UnitRegistryBuilder::default()` to layer custom definitions and aliases over Yumemi's supplement and UDUNITS2. Use
`UnitRegistryBuilder::empty()` for an isolated catalog. Calling `includeUdunits2()` on an empty builder adds only the
upstream catalog, without the Yumemi supplement.

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

Use `baseUnit()` when an application needs a genuinely independent primitive dimension rather than another unit derived
from the seven SI axes. The call declares the canonical base unit and its lower-snake-case dimension name atomically;
ordinary `define()` calls then establish exact relationships to that base:

```php
<?php

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;

$currencyRegistry = UnitRegistryBuilder::default()
    ->baseUnit('USD', Dimension::CURRENCY)
    ->define('EUR = 100 / 107 * USD')
    ->build();
$currencyUnits = new Units($currencyRegistry);
$currencyTotal = $currencyUnits->quantity(100, 'USD')
    ->add($currencyUnits->quantity(107, 'EUR'));

assert($currencyUnits->dimension('EUR')->toString() === 'currency');
assert($currencyUnits->convert(107, 'EUR', 'USD')->toString() === '100');
assert($currencyTotal->valueToString() === '200');
assert($currencyTotal->unitToString() === 'USD');
```

`Dimension::CURRENCY` is a conventional extension name, not an eighth fixed dimension. Yumemi neither ships nor fetches
exchange rates. The application owns the snapshot's source, effective time, bid/ask and fee policy, and monetary
rounding. Choose one primitive currency per immutable registry snapshot and express every other currency through an
exact declared rate. Quantities from different snapshots retain different `Units` contexts and cannot be combined.

`baseUnit()` rejects the seven fixed SI dimension names. Define another length, mass, time, current, temperature,
substance, or luminous-intensity unit relative to its corresponding SI base with `define()` so its scale remains
explicit.

An overlay definition wins over a base UDUNITS2 record with the same name. Aliases resolve through the composed
registry, so an overlay alias may target either another custom definition or a base catalog unit. Affine delta synthesis
runs when `build()` creates the immutable snapshot, after all overlay definitions and aliases are known. An explicit
overlay name that conflicts with one of its generated `delta_*` or `Δ` names is rejected rather than silently replaced.
Reusing a bundled unit name as a custom base deliberately re-roots that name and catalog definitions that depend upon
it. Avoid doing so unless replacing that part of the effective catalog is intentional.

For PHPStan, configure one `UnitRegistryFactory` that returns the complete registry. Runtime code should construct its
`Units` context from the same registry. PHPStan assumes one authoritative registry for an analysis run and does not
track a separate catalog identity for each value.

## Introspection

`Units::describe()` first describes an exact catalog spelling and follows aliases to its canonical entry. If no exact
entry exists, it applies the same one-prefix-plus-exact-unit decomposition used by ordinary resolution.
`Units::describePrefix()` describes one exact prefix name or symbol. Descriptors preserve whether the matched spelling
was canonical, an alias, a symbol, an explicit plural, a generated plural, or dynamically prefixed.

`describe()` does not accept compound expressions as lookup names, normalize definitions, or materialize dynamically
prefixed spellings as exact catalog entries. Generated affine-difference units are exact catalog entries. To report
truthful capabilities, introspection lazily resolves the complete canonical or dynamically prefixed spelling against the
effective registry. A dynamically prefixed descriptor exposes its prefix and exact residual unit through
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

Internal catalog records store direct or exact-name-inherited affine and logarithmic markers, but do not eagerly
materialize `UnsupportedExpression` or transitive composite results. Generated delta records are ordinary multiplicative
declarations materialized during catalog import or immutable-registry build. This keeps catalog generation deterministic
and avoids resolving the full catalog merely for introspection.

Affine classification means "unsupported by multiplicative `Expr` algebra," not "unsupported everywhere." See
[Affine Conversion](runtime.md#affine-conversion) for executable boundaries and [Limitations](phpstan.md#limitations)
for static-analysis behavior. Logarithmic definitions remain unsupported at every execution boundary; their descriptors
nevertheless distinguish them from unknown names and preserve the canonical unit, semantics, and original definition for
diagnostics.

Contributors changing the imported data or generator should follow
[Regenerating the UDUNITS2 Catalog](../contributing/catalog-generation.md).
