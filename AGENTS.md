# Agent guidelines

Guidance for automated agents (and humans) working in this repository.

## Changelog

This project keeps a [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) file at [`CHANGELOG.md`](CHANGELOG.md).

**Any user-facing change — primarily anything at the public API level — must be recorded there** as part of the same
change that introduces it. That includes:

- new or removed public classes, methods, functions, or constants;
- changed public signatures, return types, or behavior;
- new, changed, or removed PHPStan types, extension diagnostics, or `@yumemi-*` annotations;
- deprecations;
- bug fixes visible to users;
- security fixes.

Purely internal work (refactors, tests, tooling, CI, formatting) that does not alter observable behavior does not need
an entry.

### How to add an entry

- Add the entry under the `## [Unreleased]` section, in the correct category: **Added**, **Changed**, **Deprecated**,
  **Removed**, **Fixed**, or **Security**.
- Write it from the user's perspective — describe the observable change, not the implementation.
- Keep it to one concise line per change; add more only when a user would need the detail.
- Follow the existing format exactly (the categories and their order come from Keep a Changelog).
