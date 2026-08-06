# Yumemi Runtime Conformance Corpus

This directory records a small, implementation-independent sample of the runtime behavior that defines Yumemi. The
fixtures use public inputs and observable outputs so another implementation can consume them without reproducing the
current PHP class structure.

The corpus complements the detailed PHPUnit, PHPStan, differential, generative, and property tests. It does not replace
those tests, and implementation-specific behavior remains in the implementation-language suites.

## Versioning

Fixtures under `v1/` use the schema marker `yumemi.conformance/v1`. A schema change belongs in a new versioned
directory. Cases may be added to an existing version. Changing or removing an expected result requires an explicit
semantic decision and corresponding updates to the project's invariants or compatibility policy where applicable.

The schema version identifies the fixture format. It does not independently make every represented PHP declaration a
stable public API.

## Exact Values

Exact rational values use decimal numerator and denominator strings:

```json
{
  "numerator": "1",
  "denominator": "3"
}
```

Numerators may be negative. Denominators are positive and nonzero. Expected values are reduced to their canonical form.
Dimension results use the named seven-axis object returned by `Dimension::jsonSerialize()`.

## Errors

Error fixtures use semantic categories such as `unknown-unit` and `incompatible-unit`. The PHP runner maps those keys to
the current exception hierarchy but deliberately does not compare human-readable messages. Optional spans are half-open
byte ranges into the fixture input.

## Running The Corpus

From the repository root:

```shell
vendor/bin/phpunit --no-coverage tests/Conformance
```

The normal PHPUnit suite discovers these tests automatically.
