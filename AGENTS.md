# Agent guidelines

Guidance for automated agents (and humans) working in this repository.

For structural, compatibility, generation, and long-term maintenance decisions, preserve the project's useful knowledge
and replacement boundaries according to [Ruinenwert](docs/development/ruinenwert.md). Apply that principle in proportion
to the change; it does not require new abstractions or documents for routine work.

The project-specific [architecture](docs/development/architecture.md) defines component ownership, inward dependency
direction, generated artifacts, and expected replacement boundaries. Consult it before moving responsibilities or adding
cross-layer dependencies.

The [compatibility policy](docs/development/compatibility.md) classifies supported application and integration APIs,
provisional surfaces, internal details, persistent formats, and breaking changes. Consult it before changing observable
behavior or publicly visible declarations.

The [generated-artifact inventory](docs/development/generated-artifacts.md) records editing authorities, pinned tools,
provenance, licensing, consumer requirements, and exact checks for the parser and UDUNITS2 catalog. Never hand-edit
those outputs; regenerate and commit them with their authoritative inputs.

The [release and succession runbook](docs/development/release-and-succession.md) defines manual release verification,
signed tags, publication services, fork-first succession, exceptional direct transfer, and intentional freezing. Follow
it when preparing a release or changing release, package-ownership, or stewardship procedures.

Use `composer test` for the complete PHPUnit suite, `composer analyse` for PHPStan, and `composer check` for the
ordinary local review gate. Prefer these shared entry points over reproducing their underlying commands in new
automation. Mutation testing, Xdebug branch coverage, the parser “probator,” and the Nix-backed UDUNITS2 differential
remain specialist checks documented separately.

Before changing parser, unit, conversion, registry, serialization, numeric-output, or PHPStan inference semantics, read
the project-specific [semantic invariants](docs/development/invariants.md). Treat disagreement among those invariants,
public documentation, tests, and implementation as an issue to investigate rather than silently resolving it in favor of
the easiest artifact to edit.

For deliberate runtime semantic changes, review the versioned [conformance corpus](tests/Conformance/README.md) and
update affected cases in the same change. Preserve public inputs and observable outputs rather than encoding incidental
class structure in the fixtures.

## Documentation standards

Documentation is part of the public API. Treat inaccurate, untested, stale, or poorly organized documentation as a
defect. Write for a PHP developer who understands Composer and basic PHPStan usage but does not yet understand Yumemi's
terminology or architecture.

When writing documentation, optimize for the reader's next decision, not for mirroring the source-tree or type-system
architecture.

Public mdBook sources live under `docs/pages/`, with chapter order defined by `docs/pages/SUMMARY.md`. Build the book
with `composer docs`, validate generated internal links with `composer docs:check`, and preview it with
`composer docs:serve`.

The persistent sidebar outline in `docs/theme/yumemi.js` mirrors the `h2` and `h3` headings in each public page. When
adding, removing, renaming, or reparenting those headings, update `headingsByChapter` in the same change. Keep cohesive
reference material on its existing page; do not split short sections into separate chapters solely to produce sidebar
nesting.

### Start with the user's goal

Introduce practical behavior before formal terminology. Prefer this progression where the page's purpose permits it:

1. Show the problem Yumemi solves.
2. Show a small working example.
3. Show the diagnostic or result.
4. Explain the rule behind it.
5. Introduce the formal name for that rule.
6. Link to exhaustive reference material.

For example, demonstrate why native `meter + foot` is rejected before introducing “definitional equivalence” and
“dimensional compatibility.” Do not require readers to understand the implementation architecture before they can
install or use the library.

### Explain the runtime boundary

Keep the distinction between native and runtime-object models explicit:

- Branded native values remain ordinary PHP `int` or `float` values at runtime. Their unit exists only during static
  analysis.
- `Quantity` retains an exact rational magnitude and unit at runtime for conversion and unit-aware arithmetic.
- `PointQuantity` retains an exact coordinate and named affine scale at runtime; it is the specialized coordinate model,
  not a third native-brand mechanism.

Do not describe branded values as wrappers, objects, runtime types, or values that carry their unit at runtime. When
documenting an API such as `unit_to($value, 'foot', 'meter')`, explain where relevant that the source unit is explicit
because runtime PHP cannot recover a PHPStan brand from an ordinary scalar.

### README scope

The root `README.md` is a concise project landing page, not the complete manual. It should normally contain the banner
and title, a short description and status statement, basic installation, one compact static-analysis example, one
compact runtime example, documentation links, and a concise license summary.

Move advanced semantics, complete API behavior, configuration, custom registries, formatting policies, optional
annotations, catalog details, and limitations into the documentation source. Do not substantially expand the README
without a strong reason.

### Organization and terminology

Organize documentation by user task before implementation subsystem. Preserve the current progression from Introduction
and Getting Started through guides and recipes, reference material, and contributor documentation. Avoid categories
containing only one page unless the category creates a useful audience boundary or has a clear reason to grow.

Use task-oriented titles where possible, such as “Choose an API,” “Convert request data,” “Define custom units,” or
“Extract a decimal value.” Use implementation-oriented titles for genuine reference material.

Long reference pages are acceptable when they have a short orientation paragraph, a task table near the beginning, clear
headings, links to relevant guides, and an honest limitations section. Do not fragment cohesive material into tiny pages
solely to reduce page length.

Use established terminology consistently:

- branded native value
- PHPStan extension
- runtime unit engine
- definitionally equivalent
- dimensionally compatible
- exact rational value
- normalize
- simplify
- affine conversion

Define unfamiliar terms at first use. Prefer plain language before formal language. For example:

> A branded native value is still an ordinary PHP float. PHPStan tracks the unit during analysis, but runtime PHP does
> not receive that unit.

After this explanation, “branded value” may be used as shorthand. Do not invent synonyms for established concepts unless
the distinction is intentional and documented.

### Examples

Examples must be small, realistic, technically correct, focused on one behavior, consistent with the current public API,
and executable or statically verifiable where the repository supports it.

Prefer natural application-oriented names such as `saveDistance()`, `calculateSpeed()`, or `shipPackage()` over names
that read like test fixtures. Declarations in extracted examples must nevertheless remain distinct across the tested
documentation corpus because PHPStan verification loads relevant blocks into one process.

Do not repeat `require 'vendor/autoload.php';` on every page. State the convention once and include it only in
standalone examples where it helps. When an example contains `//!`, explain that it marks an expected PHPStan diagnostic
used by documentation testing and is not Yumemi syntax.

Never include an expected output, diagnostic, inferred type, or conversion result without verifying it against the
implementation.

### Semantic accuracy

Be exact when describing numeric output:

- `valueIn()` preserves the exact rational result after conversion.
- `exactDecimalValueIn()` returns a minimal exact terminating decimal and throws for a non-terminating expansion.
- `decimalValueIn()` returns rounded decimal output with an explicit scale and rounding mode.
- `floatValueIn()` returns a native binary floating-point result.

Do not imply that every rational number has a finite decimal representation. Do not use “exact,” “lossless,” “safe,” or
similar words unless the documented behavior provides that guarantee.

Do not imply that dimensional compatibility permits native arithmetic or assignment when Yumemi requires definitional
equivalence. Explain the reason concretely: PHP cannot add native meters to native feet without converting one operand,
so Yumemi rejects that operation when no runtime conversion occurs; `Quantity::add()` can perform the conversion and may
therefore accept compatible units.

Document important limitations prominently enough that users can form accurate expectations instead of burying them only
at the bottom of a long page.

When documenting unit syntax, show common examples before the full grammar, make precedence visible, and distinguish
accepted syntax from aliases and display formatting. The shared precedence of adjacency, multiplication, and division
must remain a visible warning with explicit grouping examples. Do not assume Unicode renders identically in every
environment.

### Links, preservation, and tests

Use relative links for repository documentation. After moving or renaming content, update inbound links, the mdBook
summary, README links, sidebar heading metadata, and references to removed headings. Check anchors and generated browser
titles. Do not edit generated HTML directly; edit the Markdown, mdBook configuration, templates, or theme source.

When shortening or reorganizing documentation, move useful material rather than silently deleting it. Before removing a
section, preserve any behavioral guarantee, limitation, edge case, compatibility or configuration requirement, precision
policy, or tested example in the appropriate guide or reference page.

Before editing examples or moving documentation, inspect the relevant machinery under `tests/Documentation/`. Preserve
fenced-PHP execution, PHPStan analysis, `//!` expectations, and the explicit Markdown manifest. Search for CI jobs or
scripts tied to affected filenames. Do not claim examples are tested unless the applicable checks include them, and do
not weaken verification merely to make a refactor pass.

### Required workflow

For nontrivial documentation work:

1. Inspect the affected sources, navigation, example tests, and build configuration.
2. Identify the intended user and task for each affected page.
3. Make the smallest coherent change that solves the usability problem.
4. Build the documentation and run the documentation example tests.
5. Check affected links, anchors, generated titles, navigation, and sidebar headings.
6. Run normal formatting and repository checks where applicable.
7. Report exactly which commands ran, which succeeded, and which were skipped or unavailable.

For onboarding or information-architecture changes, also verify that a new user can identify Yumemi's purpose, install
it, observe a deliberate unit error, choose between branded native values and runtime quantities, and find advanced
material without first learning the implementation architecture.

### Editing boundaries and style

Documentation tasks are not permission to redesign the library, rename public concepts, change examples to a different
API, or simplify semantics. If documentation exposes an apparent API problem, report it separately rather than silently
changing behavior.

Match the existing voice: technically precise, confident, restrained, concrete, and slightly distinctive without
becoming theatrical. Avoid marketing filler, repeated introductions, unexplained jargon, unnecessary warnings, fake
quotations, excessive callouts, duplicated explanations, and implementation details that do not help users make a
decision. Prefer one clear example and one precise explanation over several paragraphs of abstract prose.

## Composer and Nix

Whenever `composer.json` or `composer.lock` changes, update the `vendorHash` used by the `generated-artifacts`
`buildComposerProject2` check in `flake.nix`. Temporarily set it to `pkgs.lib.fakeHash` before running
`nix flake check`, because an existing fixed-output store path can otherwise hide a stale hash. Replace the fake hash
with Nix's reported `got` value and rerun the complete check.

Refer to coverage-guided randomized-input testing as the “probator” throughout first-party code, scripts, documentation,
and conversation. Required upstream package, executable, and PHP namespace identifiers may retain their published names;
do not repeat those names otherwise.

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

Corpus statistics are diagnostic rather than prescriptive. Never choose a book merely to improve distribution, change a
canonically suitable book because nearby logia use it frequently, pad a complete short passage, or compress a
substantial passage merely to approach a word-count range. A streak or outlier may prompt review, but it is not an
automatic defect.

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

### Canonical independence

Write each quotation as a passage capable of standing within the canon without its declaration. Do not derive its
subject, motif, doctrinal pressure, or conclusion from the declaration's name, kind, responsibility, domain, signature,
or implementation. Its attachment to a declaration is marginal placement, not semantic annotation.

Apply these priorities in order:

1. convincing scripture within the shared canon;
2. originality and variation among nearby logia;
3. concrete signs, controlled cadence, and doctrinal consequence.

Perform a detached-canon test: the quotation should remain convincing when read without seeing the declaration. Perform
a reverse-engineering test: a reader should not be able to reconstruct the declaration's behavior by decoding systematic
substitutions in its imagery.

The custom agents under `.codex/agents/` are optional workflow optimizations, not requirements. For an ordinary new
declaration, an isolated writer plus parent or human selection is sufficient; reserve the canon reviewer for batches,
doctrine passes, or uncertain candidates. When custom agents are unavailable, fix the declaration-to-opaque-ID mapping
before generation and use a fresh isolated context when possible. If isolation is unavailable, follow the same staged
contract in the main context, disclose that limitation, and apply the detached-canon and reverse-engineering checks
manually. Never remap candidates according to their apparent relevance to code. The detailed fallback procedure is in
[`docs/development/doctrine-quality-plan.md`](docs/development/doctrine-quality-plan.md).

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
