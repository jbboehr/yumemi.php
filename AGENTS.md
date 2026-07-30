# Agent guidelines

Guidance for automated agents (and humans) working in this repository.

## Changelog

This project keeps a [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) file at [`CHANGELOG.md`](CHANGELOG.md).

If the repository has no Git tags, changelog updates are optional. Once at least one Git tag exists, the requirements
below apply.

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

## Yumemi Doctrine

New in-scope named declarations must contain exactly one `@logion` PHPDoc tag.

Do not add, replace, or revise a logion on a preexisting declaration unless the user explicitly requests a doctrine
pass. A doctrine pass may backfill preexisting declarations within the scope requested by the user.

For literary style, imagery, tone, and quotation construction, follow:

- [`docs/DOCTRINE-STYLE-GUIDE.md`](docs/DOCTRINE-STYLE-GUIDE.md)

For safe editing, comment placement, idempotence, formatting, and verification, follow:

- [`docs/DOCTRINE-CODING-GUIDE.md`](docs/DOCTRINE-CODING-GUIDE.md)

This file is authoritative for repository-specific scope and coverage.

### Source scope

Doctrine applies to authored first-party PHP source under `src/` and to PHP command entry points under `bin/`.

Doctrine does not apply to:

- tests, including fixtures and test data, unless explicitly requested;
- generated code, including `src/Parser/Parser.php`;
- generated catalog data;
- external stubs;
- configuration files;
- files under `tmp/` or `vendor/`.

### Coverage

Doctrine is required for:

- classes, interfaces, traits, and enums
- functions and methods of every visibility
- properties
- class constants
- enum cases

Doctrine is not required for:

- closures and arrow functions
- anonymous classes
- local variables and parameters

### PHPDoc form and placement

Write the logion as one unquoted, logically continuous tag. Wrap it at the repository's normal line width; indent
continuation lines by four spaces and do not repeat `@logion`:

```php
/**
 * Existing technical documentation.
 *
 * @logion The appointed measure remains beneath every visible proportion,
 *     awaiting the tribunal by which matter is admitted to its proper rank.
 *
 * @return mixed
 */
```

Place `@logion` after descriptive prose and before conventional metadata tags such as `@param`, `@return`, `@throws`,
and `@template`.

Preserve all existing technical documentation.

### Yumemi-specific relevance

Privately derive quotations from the declaration’s abstract role. Common mappings include:

- dimensions → hidden order, celestial axes, hierarchy of creation
- units → appointed measure, inherited standards, visible signs
- quantities → matter brought before proportion
- conversion → lawful passage and continuity through transformation
- normalization → restoration of canonical form
- comparison → judgment, rank, testimony, and scales
- registries → archives, recognition, and lawful names
- errors → fracture, exile, failed admission, and broken covenant

Never mention programming or directly describe the declaration inside the doctrine quotation.

### Verification

Before completing a change that introduces named declarations:

1. Confirm every new in-scope declaration has exactly one tag.
2. Confirm no preexisting declaration received or lost a logion unless the user requested a doctrine pass.
3. If possible, confirm every new quotation is original and unique; never intentionally copy or closely imitate an
   existing work.
4. Confirm the logion did not change behavior, signatures, or technical documentation.
5. Run the formatter and the checks relevant to the change.

Before completing a user-requested doctrine pass:

1. Confirm every in-scope declaration has exactly one tag.
2. If possible, confirm every new quotation is original and unique; never intentionally copy or closely imitate an
   existing work.
3. Confirm no behavior, signature, or technical documentation changed.
4. Run the formatter, static analysis, and relevant tests.
5. Review the complete diff for missing, duplicated, or misplaced tags.
