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

When writing or revising a logion, follow this guide for literary style, imagery, tone, symbolism, and quotation
construction:

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

Write the logion as one unquoted, logically continuous tag beginning with a bracketed reference in the form
`[BOOK C:V]`, where `BOOK` is an allowed book code, `C` is the chapter number, and `V` is the verse number. Wrap the
text at the repository's normal line width; indent continuation lines by four spaces and do not repeat `@logion` or the
reference:

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

All quotations remain `@logion` tags. Do not introduce book-specific tags such as `@ordinance`, `@revelation`,
`@testimony`, or `@scholium`.

Choose the book according to the logion's primary canonical purpose and the needs of the surrounding corpus:

- Use `OSD` (**Ordinances of the Synthetic Dawn**) as the normal default when no other book is clearly more appropriate,
  including law, ritual, covenant, blessing, and fulfilled obligation.
- Use `RAS` (**Revelation of the Artificial Sun**) for genuine visionary disclosure, cosmic signs, angelic
  administration, apocalyptic judgment, or awe before revealed order. Avoid using it so often that visionary intensity
  becomes ordinary.
- Use `AWC` (**Acts of the Western Court**) when the verse presents sacred history, precedent, remembered institutional
  fidelity or failure, inherited obligation, covenant, lament, restoration, or dynastic judgment.
- Use `SFA` (**Scholia of the Fifth Archive**) for comparatively compressed interpretation, commentary, clarification,
  counsel, consolation, hope grounded in appointed form, or severe judgment.

Book selection is not a random genre roll. The four books belong to one canon and must remain stylistically and
doctrinally coherent. Preserve compositional freedom within every book: any logion may combine Pronouncement, Sign,
Vision, Remembrance, Injunction, and Interpretation. A book changes the verse's canonical emphasis, not the required
sequence of its movements.

Logion references follow these rules:

- Use only the book codes `OSD`, `RAS`, `AWC`, and `SFA`. Do not invent additional books, codes, or abbreviations.
- The bracketed reference must match `\[(?:OSD|RAS|AWC|SFA) [1-9][0-9]*:[1-9][0-9]*\]`.
- After choosing the book, choose chapter and verse randomly as positive decimal integers without leading zeroes. Prefer
  values from `1` through `99`, but larger values are valid.
- The complete `BOOK C:V` reference must be unique among all logions attached to declarations anywhere in the
  repository. The same chapter and verse numbers in different books are different references. Illustrative references in
  documentation examples do not reserve an identifier.
- Preserve an assigned reference when its declaration moves or is renamed, or when the quotation's wording is revised.
- Assign a new reference only when creating a new logion. Do not intentionally reuse a deleted reference.
- Check the repository for a collision before assigning a reference.

The bracketed form is source syntax; the logical reference is `BOOK C:V`. A future image for `RAS 9:3` may be stored at
a portable path such as `docs/pages/images/logia/RAS-9_3.webp`.

Place `@logion` after descriptive prose and before conventional metadata tags such as `@param`, `@return`, `@throws`,
and `@template`.

Preserve all existing technical documentation.

### Canonical independence and optional resonance

Write each quotation first as a passage capable of standing within the canon without its declaration. The declaration's
abstract role may serve as a private thematic seed, but relevance is optional and must never become a one-to-one
translation of code into religious nouns and actions. No detectable connection is preferable to disguised technical
documentation.

Apply these priorities in order:

1. convincing scripture within the shared canon;
2. originality and variation among nearby logia;
3. concrete signs, controlled cadence, and doctrinal consequence;
4. optional, indirect resonance with the declaration.

Perform a detached-canon test: the quotation should remain convincing when read without seeing the declaration. Perform
a reverse-engineering test: a reader should not be able to reconstruct the declaration's behavior by decoding systematic
substitutions in its imagery.

Write the quotation as recitable scripture rather than ornate modern exposition. Favor authoritative declaration,
parallel clauses, ritual repetition, commands, reasons, consequences, and concrete signs before abstract explanation.
Controlled KJV-influenced vocabulary and grammar are permitted across all four books but are never mandatory. When using
Early Modern pronouns or verb forms, follow the grammar in the style guide rather than adding archaisms decoratively.
Never reproduce, closely paraphrase, or parody a recognizable biblical passage.

Do not default every quotation to legitimacy, failed succession, counterfeit authority, or condemnation. Draw from the
guide's broader pressures of covenant, blessing, lament, praise, mercy, repentance, pilgrimage, fidelity, providence,
wonder, and restoration while preserving the doctrine's severity, hierarchy, and metaphysical confidence.

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

When generating or revising an image derived from a logion, use the source logion and the style guide for doctrinal
meaning, then follow this guide for visual interpretation, composition, and rendering:
[`docs/DOCTRINE-IMAGE-GUIDE.md`](docs/DOCTRINE-IMAGE-GUIDE.md).

Unless explicitly requested otherwise:

- generate doctrine images in a landscape `16:9` aspect ratio;
- target a master resolution of at least `1600x900`;
- do not embed the citation or logion in the image;
- compose for a side-by-side documentation layout with the quotation on the left and the image on the right;
- keep the principal subject within a center-safe region for responsive cropping;
- derive prospective asset paths from the reference using `docs/pages/images/logia/<book>-<chapter>_<verse>.webp`, such
  as `docs/pages/images/logia/RAS-9_3.webp`.
