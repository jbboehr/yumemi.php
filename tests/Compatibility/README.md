# Release Persistence Compatibility

This directory preserves PHP-specific persistence evidence emitted by tagged Yumemi releases. It complements the
language-neutral runtime corpus under [`tests/Conformance/`](../Conformance/README.md); it does not replace it.

Each immutable release directory contains:

- a manifest recording the release, source commit, producer environment, and case inventory;
- base64-encoded output from PHP's native `serialize()`;
- inspectable JSON output from the same public values; and
- the producer script used with an isolated installation of that exact release.

The current test suite restores the historical native payloads and verifies their behavior against the supported
persistence contract. It compares JSON structurally because object-key order is not part of that contract. Current
re-serialization only needs to produce another valid payload; byte-for-byte equality with historical output is not
required.

## Adding A Tagged Release

Copy the previous release directory, update its release and source-reference constants, review the cases against the
public persistence surface, register the directory and exact case inventory in `ReleasePersistenceCompatibilityTest`,
and generate the new fixtures from a clean Composer project. The test suite compares registered releases with every
`fixtures/v*` directory, so an unregistered capture fails rather than being silently ignored. For example:

```shell
workdir="$(mktemp -d)"
composer --working-dir="$workdir" require jbboehr/yumemi:X.Y.Z --no-interaction --no-progress
php tests/Compatibility/fixtures/vX.Y.Z/generate.php "$workdir/vendor/autoload.php"
```

The producer refuses to run against another package version or source reference. Never regenerate an older release
directory with current source code. Add cases when a tagged release expands the supported persistent surface; do not
delete historical cases merely because a newer representation is preferred.

Native PHP serialization is only appropriate for trusted input. Custom-registry values must continue to be restored
through the matching `Units::deserialize()` context.
