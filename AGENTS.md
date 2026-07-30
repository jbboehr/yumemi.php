# Agent guidelines

Guidance for automated agents (and humans) working in this repository.

## Public documentation

Public mdBook sources live under `docs/pages/`, with chapter order defined by `docs/pages/SUMMARY.md`.

The persistent sidebar outline in `docs/theme/yumemi.js` mirrors the `h2` and `h3` headings in each public page. When
adding, removing, renaming, or reparenting those headings, update `headingsByChapter` in the same change. Keep cohesive
reference material on its existing page; do not split short sections into separate chapters solely to produce sidebar
nesting.

Build the book with `composer docs` and preview it with `composer docs:serve`.

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

Write the logion as one unquoted, logically continuous tag beginning with a bracketed reference in the form `[OSD C:V]`,
where `C` is the chapter number and `V` is the verse number. Wrap the text at the repository's normal line width; indent
continuation lines by four spaces and do not repeat `@logion` or the reference:

```php
/**
 * Existing technical documentation.
 *
 * @logion [OSD 7:12] The appointed measure remains beneath every visible proportion,
 *     awaiting the tribunal by which matter is admitted to its proper rank.
 *
 * @return mixed
 */
```

Logion references follow these rules:

- Always use the fixed book code `OSD`.
- The bracketed reference must match `\[OSD [1-9][0-9]*:[1-9][0-9]*\]`.
- Chapter and verse are randomly chosen positive decimal integers without leading zeroes. Prefer values from `1` through
  `99`, but larger values are valid.
- The complete `OSD C:V` reference must be unique among all logions attached to declarations anywhere in the repository.
  Illustrative references in documentation examples do not reserve an identifier.
- Preserve an assigned reference when its declaration moves or is renamed, or when the quotation's wording is revised.
- Assign a new reference only when creating a new logion. Do not intentionally reuse a deleted reference.
- Check the repository for a collision before assigning a reference.

The bracketed form is source syntax; the logical reference is `OSD C:V`. A future image for `OSD 7:12` may be stored at
a portable path such as `assets/logia/OSD/7/12.webp`.

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
2. Confirm every new reference has valid syntax, is repository-unique, and does not replace a reference already assigned
   to that logion.
3. Confirm no preexisting declaration received or lost a logion unless the user requested a doctrine pass.
4. If possible, confirm every new quotation is original and unique; never intentionally copy or closely imitate an
   existing work.
5. Confirm the logion did not change behavior, signatures, or technical documentation.
6. Run the formatter and the checks relevant to the change.

Before completing a user-requested doctrine pass:

1. Confirm every in-scope declaration has exactly one tag.
2. Confirm every reference has valid syntax and is repository-unique, and that existing references remain stable.
3. If possible, confirm every new quotation is original and unique; never intentionally copy or closely imitate an
   existing work.
4. Confirm no behavior, signature, or technical documentation changed.
5. Run the formatter, static analysis, and relevant tests.
6. Review the complete diff for missing, duplicated, or misplaced tags.

### Doctrine images

For visual interpretation, composition, and rendering, follow
[`docs/DOCTRINE-IMAGE-GUIDE.md`](docs/DOCTRINE-IMAGE-GUIDE.md).

Unless explicitly requested otherwise:

- generate doctrine images in a landscape `16:9` aspect ratio;
- target a master resolution of at least `1600x900`;
- do not embed the citation or logion in the image;
- compose for a side-by-side documentation layout with the quotation on the left and the image on the right;
- keep the principal subject within a center-safe region for responsive cropping;
- derive prospective asset paths from the reference using `assets/logia/OSD-<chapter>_<verse>.webp`, such as
  `assets/logia/OSD-54_64.webp`.
