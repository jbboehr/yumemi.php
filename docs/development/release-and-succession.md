# Release and Succession

This runbook records how Yumemi is released, how publication access is verified, and how the project can continue or be
preserved when the original steward no longer maintains it. It supplements the [compatibility policy](compatibility.md),
[generated-artifact inventory](generated-artifacts.md), and [Project Steward](../STEWARD.md) record. It contains no
credentials or account-recovery data.

Releases are manual. The ordinary path prepares changes on `develop`, merges the tested result into `master`, and tags
that exact `master` commit. A compatible fork under a new package owner is the normal succession path; transfer of the
original GitHub repository or Packagist package is exceptional.

## Release Roles and Services

The releaser must verify access before beginning. Do not assume that access held by a previous releaser, an expired
token, or a checked-in workflow remains usable.

- **GitHub repository:** merges the release, accepts the tag, and hosts the GitHub Release. The releaser needs
  permission to push `master` and tags and create releases.
- **GitHub Actions:** verifies the exact release commit, including the Nix checks. The releaser needs to view runs and
  rerun failed jobs when appropriate.
- **GitHub Pages:** publishes the mdBook site from `master`. Verify the repository Pages configuration and documentation
  workflow.
- **Packagist:** publishes the Composer version. Verify maintainer access and the GitHub update mechanism.
- **OpenPGP signing key:** signs and verifies the annotated tag. The private key must be available locally and its
  public key should be discoverable by users.

GitHub Actions uses the repository-provided `GITHUB_TOKEN`; no custom release token is declared in the checked-in
workflows. Account recovery, hardware tokens, private signing keys, and Packagist credentials belong in the maintainer's
secure credential system, never in this repository. Confirm current GitHub permissions, branch protection, Pages
settings, Packagist maintainers, and update hooks in their respective services before relying on them.

## Prepare a Release

1. Choose a Semantic Versioning release number and decide which changes on `develop` belong in it.
2. Complete the corresponding `CHANGELOG.md` section. Move the applicable entries out of `Unreleased`, add the release
   date, and update comparison links without discarding an empty `Unreleased` section for future work.
3. For the first tagged release, remove `:dev-master` from the README and public installation instructions. Verify that
   every status statement accurately describes the release state.
4. Review the [compatibility policy](compatibility.md) against the intended release. Do not imply stability for a
   provisional or internal surface merely because it is publicly visible in PHP.
5. Review dependency and platform constraints, licensing notices, generated-artifact provenance, and the contents of
   `composer archive`. Generated outputs and their authoritative inputs must be committed together.
6. Commit the release preparation on `develop`, obtain review when appropriate, and merge it into `master` without
   introducing untested changes.

The release commit must be a clean, synchronized `master` checkout. Record its full commit SHA before testing and use
that SHA when comparing local results, GitHub Actions, the tag, the GitHub Release, and Packagist.

## Coordinate the Optional Native Extension

Yumemi releases do not require an `ext-yumemi` release. The method API and generated PHP parser remain the supported
baseline, and the primary PHPUnit and consumer gates must continue to run without the extension.

When a release changes `InternalQuantity`, operator delegation, native parser selection, ABI expectations, Unicode
classification, or fallback behavior:

1. implement and review the corresponding change in its owning repository;
2. update the locked `php-yumemi` flake input to the exact extension release candidate;
3. run the real extension integration checks on PHP 8.2 through 8.5 and the complete Nix gate;
4. name the compatible extension version or commit in both repositories' release notes; and
5. preserve automatic PHP fallback for an absent, incompatible, or explicitly disabled native parser.

The extension repository owns its native implementation, build and platform support, PIE installation, and ABI release
notes. Yumemi owns application semantics, PHPStan configuration, parser fallback, and migration guidance. The
extension's
[current status and platform envelope](https://github.com/jbboehr/php-yumemi/blob/develop/docs/RELEASE.md#platform-envelope)
distinguish the published Linux PIE target from native source-build qualifications. Do not broaden Yumemi's own platform
promise merely because an extension source-build job passes elsewhere.

## Verify the Release Commit

Install the locked development dependencies and run the ordinary local gate:

```shell
composer install
composer audit --locked --abandoned=report
composer check:full
composer check:bc
```

`composer check:full` covers Composer validation, whitespace, formatting, PHPStan, PHPUnit, documentation, benchmark
discovery, and a release-style Composer archive consumer. It requires mdBook, Lychee 0.24.1, and network access for the
isolated consumer installation, but not Nix. `composer check:bc` installs its isolated checker dependencies and compares
the committed release candidate with the latest stable tag; it does not inspect uncommitted changes. The audit fails on
security advisories while reporting, rather than failing on, abandoned development tooling; review every reported
abandonment before release.

When Nix is available, run `nix flake check --keep-going -L`, especially after changes to Nix, dependencies, parser or
catalog generation, generated artifacts, or the UDUNITS2 integration. The authoritative release gate is a successful
GitHub Actions run for the exact release commit, including the conventional baseline, exhaustive Nix matrix, mutation
packages, dependency-bound jobs, and API compatibility check. Branch protection may use the fixed-name `Nix` aggregate
job rather than individual generated matrix names. Do not tag a different commit merely because a nearby commit passed.

Before publication, also inspect the archive directly:

```shell
composer archive --format=tar --dir=/tmp
```

Confirm that the archive contains the runtime and PHPStan entry points, generated parser, generated catalog, public
documentation and legal notices required by the package, and excludes development-only material according to
`.gitattributes`. The consumer archive test verifies loadability and representative behavior; manual inspection guards
against an accidentally incomplete or unexpectedly large package.

## Publish

Create a signed annotated tag only after the release commit and its CI run have passed:

```shell
git tag -s vX.Y.Z -m "Yumemi X.Y.Z" <release-commit-sha>
git verify-tag vX.Y.Z
git show --no-patch --show-signature vX.Y.Z
git push origin vX.Y.Z
```

The tag name is `vX.Y.Z`; the Composer version is `X.Y.Z`. Never move, replace, or delete a published release tag to
correct a mistake. Prepare a subsequent patch release instead.

After pushing the tag:

1. Create a GitHub Release for the tag and use the matching changelog section as its notes. Do not attach a custom
   Composer archive; the immutable tag and Composer package are the release artifacts.
2. Verify that GitHub Pages successfully deploys from the release state on `master` and that the published site has the
   expected version-independent content and working links.
3. Verify that Packagist indexes `X.Y.Z` at the exact tagged commit. If automatic updating failed, use the Packagist
   maintainer controls rather than creating another tag.
4. In a clean temporary project, require the tagged package without a development constraint and run a minimal runtime
   and PHPStan smoke test.
5. Verify the GitHub Release, tag signature, Packagist metadata, and installation from a machine or checkout that does
   not depend on the releaser's working tree.
6. Capture the tagged package's supported persistent values in a new immutable directory under
   [`tests/Compatibility/fixtures/`](../../tests/Compatibility/fixtures/). Run its guarded producer against a clean
   Composer installation of the published version, verify the recorded source commit, register its directory and exact
   case inventory in the compatibility test, and commit the resulting native serialization, JSON, and manifest evidence
   on `develop`. Never regenerate an older release directory from a newer checkout.

If publication fails partway through, preserve the tag and repair the missing service state. Publish a new version only
when released code itself must change.

## Fork-First Succession

The practical continuation path is a public fork or mirror under a new owner. This follows the
[Code of Sovereignty](../../CODE_OF_CONDUCT.md): authority in a fork belongs to its own sovereign, while the original
repository remains historical evidence.

A successor should:

1. fork or mirror the complete repository, including branches and signed tags, under an account it controls;
2. publish under a new Composer package identity rather than assuming control of `jbboehr/yumemi`;
3. normally preserve the `jbboehr\Yumemi` PHP namespace so existing applications can adopt a compatible successor
   without a source-wide namespace migration;
4. run the documented local checks, the full CI matrix, the conformance corpus, and generated-artifact verification;
5. document provenance, the last compatible upstream release, intentional divergences, migration instructions, and its
   own compatibility and stewardship policies; and
6. establish independent GitHub, Pages, Packagist, and signing access without depending on the original maintainer's
   credentials.

A successor package may declare Composer `replace` for `jbboehr/yumemi` only when it intentionally provides the same
supported contract for the replaced versions. Passing the conformance corpus is necessary evidence, not sufficient by
itself: PHPStan integration, diagnostics, persistent formats, generated data, licensing, and the complete
[compatibility policy](compatibility.md) must also be considered. An incompatible continuation should use a new package
identity without claiming drop-in replacement.

When the original steward is available and accepts the successor, the steward should make discovery explicit by:

- naming and linking the successor in the original README or an archival notice;
- marking the original Packagist package abandoned with the successor package as its replacement;
- linking the successor from the final GitHub Release or repository notice; and
- preserving the original repository, tags, releases, documentation, and issue history rather than deleting them.

Technical succession does not automatically transfer copyright ownership, CLA rights, trademarks, commercial licensing
authority, signing identity, or private service credentials. The project licenses already permit compliant forks; any
additional rights or assignments require separate, explicit legal arrangements.

## Exceptional Direct Transfer

Direct transfer of the existing GitHub repository or Packagist package may be appropriate when the original steward and
successor expressly prefer continuity under the same package identity. It is not required for a viable successor and
must not be assumed.

Before a direct transfer:

- identify exactly which GitHub repository, organization, Pages, Packagist, signing, domain, and publication controls
  are transferring;
- verify the successor can operate every required service before revoking the previous access;
- preserve at least two authorized maintainers during the transition where the service permits it;
- document whether the Project Steward designation changes;
- distinguish service ownership from copyright, CLA, trademark, and commercial-licensing rights; and
- perform a non-release dry run of checks, documentation deployment, and package update mechanisms.

Never commit credentials or recovery codes as part of a transfer. Rotate or revoke old credentials after the successor
has independently verified access.

## Intentional Freezing

If maintenance ends without a successor, freeze the project deliberately rather than allowing its status to become
ambiguous:

1. publish a final status notice stating the last supported release and whether security or compatibility fixes remain
   possible;
2. finish or clearly label the final changelog and compatibility state;
3. preserve the repository, tags, releases, public documentation, conformance fixtures, generated artifacts, licensing
   notices, and generation instructions;
4. mark the Packagist package abandoned without naming a replacement unless a compatible successor actually exists;
5. archive the GitHub repository after notices and links are in place; and
6. revoke publication credentials and unnecessary service access without deleting public historical artifacts.

An intentionally frozen package remains forkable under its licenses. Its final documentation should point prospective
successors to the [architecture](architecture.md), [invariants](invariants.md),
[compatibility policy](compatibility.md), [conformance corpus](../../tests/Conformance/README.md), and
[generated-artifact inventory](generated-artifacts.md).

## Runbook Maintenance

Review this runbook before every release and whenever the default branch, CI jobs, Pages source, package identity,
Packagist ownership, signing policy, or stewardship arrangement changes. Verify service facts from the services rather
than treating this document as proof that current permissions or hooks still exist.
