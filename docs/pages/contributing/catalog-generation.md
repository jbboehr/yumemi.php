# Regenerating the UDUNITS2 Catalog

The checked-in `data/udunits2.php` file is generated from the UDUNITS2 XML distribution. This procedure is for
contributors changing the importer, exporter, source package, or generated catalog; applications using Yumemi do not
need to run it.

## Rebuild

Do not edit `data/udunits2.php` manually. In the Nix development shell, run either command:

```shell
composer generate-catalog
```

```shell
make generate-catalog
```

The flake sets `UDUNITS_XML_DIR` to the installed UDUNITS2 XML directory. Outside the development shell, specify an
equivalent directory explicitly:

```shell
UDUNITS_XML_DIR=/path/to/share/udunits make generate-catalog
```

## Source Inputs

The Make target supplies these files in the order declared by the upstream `udunits2.xml` manifest:

1. `udunits2-prefixes.xml`
2. `udunits2-base.xml`
3. `udunits2-derived.xml`
4. `udunits2-accepted.xml`
5. `udunits2-common.xml`

The generator imports the XML, materializes aliases and plural metadata, and exports deterministic PHP through
`brick/varexporter`. A successful rebuild should leave no diff unless an input listed above has changed.

## Verify

Run the full test suite after regeneration. The catalog smoke tests resolve every supported definition and pin the known
unsupported affine and logarithmic sets, making source-data drift explicit.

Return to [Built-in and Custom Units](../reference/catalog.md) for the user-facing catalog behavior.
