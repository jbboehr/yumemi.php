# Contributing

Thank you for considering a contribution to this project.

Bug reports, feature suggestions, documentation improvements, tests, and code changes are welcome. For substantial
changes, consider opening an issue first so that the proposed design can be discussed.

## Pull requests

A pull request should:

- explain what it changes and why;
- include or update relevant tests;
- pass the project’s formatting, static-analysis, and test checks;
- avoid unrelated changes; and
- identify any third-party code, assets, or other material it contains.

AI-assisted contributions are permitted, but you remain responsible for reviewing the submitted material and ensuring
that you have the right to license it under these terms.

Install Composer dependencies and use the conventional local checks:

```shell
composer install
composer check
```

`composer check` validates Composer metadata and whitespace, formatting, PHPStan, and PHPUnit without requiring mdBook,
Nix, benchmark discovery, or a network-dependent consumer installation. During focused work, run only the relevant
underlying command. Use `composer check:full` for release preparation or a change that affects documentation,
benchmarks, packaging, or extension registration; it adds the documentation build and link check, benchmark discovery,
and release-style consumer archive.

### Choosing checks

| Change or task                    | Command                                                      | Additional requirements                  |
| --------------------------------- | ------------------------------------------------------------ | ---------------------------------------- |
| Ordinary code or test change      | `composer check`                                             | PHP, required extensions, and Composer   |
| Focused PHPUnit iteration         | `composer test -- tests/Parser`                              | None beyond installed dependencies       |
| One PHPStan rule test             | `composer test -- tests/PHPStan/InvalidUnitCallRuleTest.php` | None beyond installed dependencies       |
| Static-analysis-only iteration    | `composer analyse`                                           | None beyond installed dependencies       |
| Public documentation              | `composer docs:check`                                        | mdBook                                   |
| Benchmarks                        | `composer benchmark:smoke`                                   | None beyond installed dependencies       |
| Package or extension registration | `composer test:consumer:archive`                             | Network access for the isolated consumer |
| Complete non-Nix verification     | `composer check:full`                                        | mdBook and consumer network access       |
| Nix, dependencies, or generation  | `nix flake check --print-build-logs`                         | Nix                                      |

`nix flake check` additionally verifies the reproducible environment, generated artifacts, and UDUNITS2 differential
suite. Nix is not required for ordinary local work; `nix develop` provides the pinned toolchain when reproducibility,
documentation, or generation work requires it, and CI supplies the authoritative Nix signal for reviewed changes.

Mutation testing, Xdebug branch coverage, alternate property-test seeds, and the parser “probator” are intentionally
separate investigative workflows rather than requirements for every local edit.

See [`docs/development/mutation-testing.md`](docs/development/mutation-testing.md) for the optional mutation-testing
workflow and guidance on interpreting escaped mutants.

For architectural, compatibility, generation, and long-term maintenance decisions, see
[`docs/development/ruinenwert.md`](docs/development/ruinenwert.md). It describes how to leave the project's behavior and
rationale recoverable without requiring speculative abstraction or package splitting.

The project-specific [`docs/development/invariants.md`](docs/development/invariants.md) identifies the semantic rules
that architectural and behavior changes must preserve or deliberately revise.

[`docs/development/architecture.md`](docs/development/architecture.md) maps the semantic core, adapters, generated
artifacts, dependency direction, and expected replacement boundaries.

[`docs/development/compatibility.md`](docs/development/compatibility.md) distinguishes supported application and
integration contracts from provisional, internal, and generated details.

[`docs/development/generated-artifacts.md`](docs/development/generated-artifacts.md) records how the committed parser
and UDUNITS2 catalog are regenerated, licensed, verified, and preserved for consumers.

[`docs/development/release-and-succession.md`](docs/development/release-and-succession.md) records the manual release
procedure, publication-service checks, signed-tag policy, compatible-fork path, and intentional freezing procedure.

The versioned [`tests/Conformance`](tests/Conformance/README.md) corpus records representative runtime behavior as
language-neutral public inputs and outputs. Deliberate semantic changes should update affected cases and their
rationale.

## Documentation

The public mdBook sources live under [`docs/pages`](docs/pages). Internal engineering documents live under
[`docs/development`](docs/development). Doctrine, legal, and contributor documents remain directly under [`docs`](docs).
Internal documents are not included in the generated site.

With Composer dependencies installed, build or preview the public documentation with:

```shell
make docs
make docs-serve
```

The generated site is written to `build/docs`. [Akashi](https://github.com/jbboehr/akashi.php) discovers PHP examples in
the public documentation, executes them through PHPUnit, and verifies PHPStan-relevant examples and `//!` diagnostics.

## Definitions

The project as a whole is distributed under:

```text
AGPL-3.0-only WITH romic-exception
```

This is the **Project License**.

The default license for contributor-authored material is:

```text
AGPL-3.0-only WITH romic-exception OR Apache-2.0
```

This is the **Default Contribution License**.

A **Contribution** is copyrightable material intentionally submitted for inclusion in the project, including code,
documentation, tests, configuration, and artwork.

Issue reports, feature requests, general discussion, and material conspicuously marked **“Not a Contribution”** are not
Contributions under these terms.

The **Project Steward** is the individual or legal entity identified in [`docs/STEWARD.md`](docs/STEWARD.md).

## Default contribution terms

By intentionally submitting a Contribution to this repository through a pull request or another contribution mechanism
that provides notice of these terms, you license the Contribution to every recipient under the Default Contribution
License unless you validly elect the CLA route described below.

Under the default route:

- you retain copyright in your Contribution;
- each recipient may use your Contribution under either listed license;
- the public project may incorporate your Contribution under the Project License;
- the Project Steward may use your Contribution under Apache-2.0, including in separately licensed or proprietary
  versions;
- every other recipient receives the same Apache-2.0 option;
- the Apache-2.0 option applies only to material that you have the right to license; and
- your Contribution does not cause the remainder of the project to become licensed under Apache-2.0.

The default route is intentionally symmetric. The Project Steward receives no Apache-2.0 permission that is withheld
from the public.

No checkbox is required to use the default route.

## Optional CLA route

A contributor who prefers their Contribution to remain publicly available only under the Project License may instead
affirmatively elect the project’s Contributor License Agreement in the applicable pull request.

Under the CLA route:

- the Contribution is publicly licensed under the Project License;
- the Project Steward receives the additional rights specified in the CLA; and
- the Contribution is not intentionally offered to the public under Apache-2.0 by these contribution terms.

The pull-request checkbox must identify and link to the applicable version of the CLA.

By checking that box, the person making the election:

1. confirms that they have read and agree to the identified CLA;
2. elects the CLA route for the Contribution;
3. represents that they own all rights necessary to make that election or are authorized to act for every other
   applicable copyright holder; and
4. accepts the CLA for themselves and, to the extent of their authority, on behalf of those other copyright holders.

The CLA becomes effective for a Contribution when the Project Steward merges or otherwise incorporates that Contribution
into the project. No separate countersignature or signing process is required unless the applicable CLA expressly
provides otherwise.

## Multiple authors and partial elections

The person electing the CLA route may make that election for material owned by another person or organization only when
authorized to act on that copyright holder’s behalf.

A CLA election applies only to portions of a Contribution for which the person making the election owns the necessary
rights or possesses the necessary authority.

To the extent that a CLA election is invalid, unauthorized, incomplete, or otherwise ineffective for any portion of a
Contribution, that portion remains subject to the Default Contribution License, provided that it was otherwise validly
submitted under these contribution terms.

Accordingly, a single Contribution may contain:

- material validly governed by the CLA route; and
- material governed by the Default Contribution License.

An ineffective CLA election does not invalidate the Default Contribution License for material otherwise validly
submitted under these terms.

## Unauthorized material

Neither the Default Contribution License nor a CLA election grants rights in material that the submitter had no
authority to license.

A false representation of ownership or authority does not bind the actual copyright holder or cure an unauthorized
submission.

If a Contribution contains material that was not validly submitted or licensed, the Project Steward may remove, replace,
or seek separate permission for that material.

## Your authority to contribute

By submitting a Contribution, you represent that:

1. you created the Contribution or otherwise have sufficient rights to submit it under the applicable terms;
2. if another person or organization owns any portion of the Contribution, you are authorized to submit and license that
   portion;
3. you have obtained any necessary permission from your employer or another rights holder;
4. you have identified any third-party material included in the Contribution and its applicable license; and
5. you are not knowingly submitting material that cannot lawfully be incorporated into the project.

Do not submit code copied from another project merely because that project is publicly accessible. Identify its source
and license so that compatibility can be reviewed.

## Copyright

You retain copyright in your Contribution. Submission does not assign copyright to the Project Steward.

Accepted contributions may be acknowledged collectively using a notice such as:

```text
Copyright (c) [YEAR] [PROJECT_STEWARD] & contributors
```

Individual copyright notices may be retained where legally required or reasonably appropriate.

## Acceptance

The Project Steward may accept, reject, request changes to, or decline to incorporate any Contribution.

For unusually large contributions, corporate contributions, or contributions with unclear provenance, the Project
Steward may request additional confirmation of ownership or authority before merging.
