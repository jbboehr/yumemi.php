# Catalog Reference

Yumemi ships a generated unit catalog derived from UDUNITS2. The same catalog drives runtime name resolution,
conversion, formatting, and PHPStan analysis.

The imported UDUNITS2 material is distributed under the terms in [UDUNITS-COPYRIGHT](UDUNITS-COPYRIGHT). Yumemi's own
code remains under the project license described in the root README and license files.

## Default Catalog

`Units::default()` uses `Udunits2UnitRegistry` and the checked-in `data/udunits2.php` catalog. The generated data
includes:

- base, dimensionless, and derived units;
- canonical names, aliases, symbols, explicit plurals, and unambiguous generated plurals;
- decimal and scientific prefix definitions;
- source definitions, comments, and documentation when present upstream.

Lookup is case-sensitive. Exact names win before dynamic prefix decomposition, and prefixes apply only when the
remaining suffix is an exact unit name. See the [unit syntax reference](unit-syntax.md#unit-names) for examples.

## Introspection

`Units::describe()` first describes an exact catalog spelling and follows aliases to its canonical entry. If no exact
entry exists, it applies the same one-prefix-plus-exact-unit decomposition used by ordinary resolution.
`Units::describePrefix()` describes one exact prefix name or symbol. Descriptors preserve whether the matched spelling
was canonical, an alias, a symbol, an explicit plural, a generated plural, or dynamically prefixed.

Introspection does not parse compound expressions, normalize definitions, or add synthesized spellings to `names()`. A
dynamically prefixed descriptor exposes its prefix and exact residual unit through `prefixDecomposition`:

```php
<?php

require 'vendor/autoload.php';

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

Prefixed affine and logarithmic names also receive synthesized descriptors with the residual unit's `UnitSemantics`. Use
`supportsMultiplicativeAlgebra()` and `supportsConversion()` to inspect concrete capabilities. Ordinary affine units
support conversion, dynamically prefixed affine units do not, and logarithmic units support neither operation.

## Custom Registries

Use `UnitRegistryBuilder::default()` to layer custom definitions and aliases over UDUNITS2. Use
`UnitRegistryBuilder::empty()` for an isolated catalog.

Definitions use the normal unit language and are parsed against the completed registry on first use. Multiplicative
definitions work throughout the runtime. An affine definition such as `degree_widget = kelvin @ 100` works at explicit
conversion boundaries but remains unavailable to expression and quantity algebra. The builder is mutable: each fluent
method updates and returns the same builder. Every `build()` call creates an immutable registry snapshot that is
unaffected by later builder changes.

```php
<?php

require 'vendor/autoload.php';

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
```

An overlay definition wins over a base UDUNITS2 record with the same name. Aliases resolve through the composed
registry, so an overlay alias may target either another custom definition or a base catalog unit.

For PHPStan, configure one `UnitRegistryFactory` that returns the complete registry. Runtime code should construct its
`Units` context from the same registry. PHPStan assumes one authoritative registry for an analysis run and does not
track a separate catalog identity for each value.

## Catalog Semantic Support

The expression and quantity models intentionally support only multiplicative unit algebra with integer powers. Known
affine and logarithmic UDUNITS2 definitions remain in the generated catalog with a `UnitSemantics` value exposed by
`describe()`. Aliases inherit the semantics of their canonical entry, and direct custom `@` or `lg(...)` definitions
receive the same classification. Multiplicative descriptors expose `UnitSemantics::Multiplicative` rather than `null`.

Affine classification currently means "unsupported by multiplicative `Expr` algebra," not "unsupported everywhere."
`convert()`, `convertFloat()`, `areCompatible()`, `dimension()`, and `unit_to()` evaluate affine definitions exactly.
`conversionFactor()` works only when the composed conversion has no offset. Affine units remain invalid in `parse()`,
`unit()`, quantities, multiplication, division, powers, and prefixes.

Logarithmic definitions remain unsupported at every execution boundary. Attempting to evaluate one throws
`UnsupportedUnitAlgebraException` from expression APIs or `UnsupportedUnitConversionException` from conversion APIs.
Both carry the canonical unit name, semantics, and original definition. PHPStan reports the same operation-specific
message for constant unit strings.

## Regenerating The Catalog

Do not edit `data/udunits2.php` manually. Rebuild it from the UDUNITS2 XML source in the Nix development shell:

```shell
composer generate-catalog
```

The equivalent Make target is:

```shell
make generate-catalog
```

The flake sets `UDUNITS_XML_DIR` to the installed UDUNITS2 XML directory. Outside the development shell, specify an
equivalent directory explicitly:

```shell
UDUNITS_XML_DIR=/path/to/share/udunits make generate-catalog
```

The Make target supplies these files in the order declared by the upstream `udunits2.xml` manifest:

1. `udunits2-prefixes.xml`
2. `udunits2-base.xml`
3. `udunits2-derived.xml`
4. `udunits2-accepted.xml`
5. `udunits2-common.xml`

The generator imports the XML, materializes aliases and plural metadata, and exports deterministic PHP through
`brick/varexporter`. A successful rebuild should leave no diff unless the importer, exporter, source package, or
generated header changed.

After regeneration, run the full test suite. The catalog smoke tests resolve every supported definition and pin the
known unsupported affine and logarithmic sets, making source-data drift explicit.
