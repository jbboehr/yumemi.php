# Yumemi Doctrine — General Coding and Editing Guide

## Purpose

This document defines safe, reusable rules for agents that add doctrine comments or other literary marginalia to a
codebase.

It is independent of the Yumemi repository and mostly independent of programming language. The doctrine's literary style
belongs in the [style guide](DOCTRINE-STYLE-GUIDE.md); Yumemi-specific tag coverage belongs in
[`AGENTS.md`](../AGENTS.md).

The governing principle is simple:

> Add atmosphere without damaging software.

---

## 1. Preserve behavior

Doctrine work is a documentation-only change unless the task explicitly requests more.

Do not alter:

- executable statements;
- control flow;
- return values;
- exception behavior;
- public or private signatures;
- visibility;
- inheritance;
- interfaces;
- type declarations;
- attributes or annotations used at runtime;
- serialization behavior;
- generated identifiers;
- configuration semantics;
- test expectations;
- dependency versions.

Do not “clean up” unrelated code while adding doctrine.

A doctrine pass should produce no runtime behavior change.

---

## 2. Preserve standard concepts

Do not rename standard technical or repository concepts for aesthetic effect.

Keep conventional terminology for:

- classes
- methods
- functions
- interfaces
- traits
- enums
- properties
- constants
- tests
- fixtures
- benchmarks
- builds
- releases
- errors
- exceptions
- warnings
- repositories
- commits
- branches
- pull requests
- issues
- source directories
- documentation files
- CI jobs and steps

Aesthetic terminology belongs in literary comments or optional supplementary messages.

Operational vocabulary should remain obvious to maintainers and tools.

---

## 3. Preserve existing documentation

When adding a doctrine tag or literary fragment:

- retain accurate technical prose;
- retain parameter and return documentation;
- retain exception documentation;
- retain type-system and static-analysis annotations;
- retain examples;
- retain deprecation notices;
- retain licensing and attribution comments;
- retain suppression directives;
- retain tool-specific metadata.

Do not replace useful documentation with atmosphere.

Technical documentation answers what the software does.

Doctrine exists beside it and serves a different purpose.

---

## 4. Respect comment semantics

Many languages and tools distinguish ordinary comments, documentation comments, annotations, attributes, directives, and
pragmas.

Before editing, determine:

- which comment syntax is recognized as documentation;
- whether custom tags are permitted;
- whether documentation comments affect generated docs;
- whether linters validate unknown tags;
- whether annotations or docblocks affect runtime or static analysis;
- whether comments may be attached to enum cases, fields, promoted properties, records, or generated declarations;
- whether formatters reflow long tag values;
- whether comments are stripped during packaging.

Do not assume that a visually similar comment is semantically harmless.

If the requested placement is unsupported or ambiguous, preserve correctness and report the exception.

---

## 5. Scope discipline

Modify only the requested scope.

A repository-specific rule may require doctrine on declarations newly introduced by an ordinary code change. That
requirement does not authorize backfilling, replacing, or revising doctrine on preexisting declarations.

Do not automatically decorate:

- dependencies;
- vendored code;
- generated files;
- build output;
- caches;
- snapshots;
- minified files;
- fixtures copied from third parties;
- lockfiles;
- machine-generated API clients;
- compiled artifacts;
- documentation snippets that are not actual source declarations.

If the user asks for a repository-wide pass, identify first-party source roots and exclusions before editing. Treat that
explicit request as authorization to backfill only the declarations within the requested scope.

Do not treat every file containing code-like text as authored source.

---

## 6. Idempotence

The edit should be safe to run more than once.

Before adding a tag or fragment:

- detect whether the declaration already has one;
- do not duplicate it;
- do not create nested or consecutive documentation blocks unnecessarily;
- do not replace an existing original quotation unless the task explicitly requests regeneration;
- do not reformat already compliant comments merely to produce a diff.

If the repository assigns durable references to doctrine fragments:

- preserve the reference when code moves, symbols are renamed, or wording is revised;
- allocate a new reference only for a new fragment;
- validate the reference's syntax and repository-wide uniqueness before insertion;
- do not intentionally reuse a reference removed from the current source;
- keep the logical reference independent of file paths and symbol names.

A second run over a completed codebase should produce no changes.

---

## 7. One declaration, one doctrine fragment

Unless a repository-specific rule says otherwise:

- attach one doctrine fragment to each applicable declaration newly introduced by the current change;
- backfill preexisting declarations only during an explicitly requested doctrine pass;
- do not use a file-level fragment as a substitute for declaration coverage;
- do not add several competing doctrine tags to one declaration;
- do not decorate anonymous or ephemeral syntax unless explicitly requested.

When a language cannot attach documentation cleanly to a certain declaration form, do not invent invalid syntax. Record
the limitation.

---

## 8. Canonical independence

The literary fragment must succeed as an independent passage of the repository's canon. Do not derive its subject,
motif, doctrinal pressure, or conclusion from the declaration's name, kind, responsibility, domain, signature, or
implementation. Its attachment to a declaration is marginal placement, not semantic annotation.

Do not translate a declaration's nouns and operations into a parallel set of religious nouns and ritual actions. A
reader should not be able to reconstruct lookup, validation, sorting, caching, dispatch, serialization, or another
implementation behavior merely by decoding the fragment's imagery.

**Too literal:**

> The archive admitted each name and placed its appointed tablet in order.

This merely renames validation, lookup, and sorting.

**Preferred:**

> In the winter of the red comet, the widow kept one lamp before the empty choir; and at the first thaw, the bells
> beyond the mountain answered, though no road remained between them.

The preferred passage is selected for its canonical quality rather than for a correspondence with the declaration.

Perform two checks before accepting a fragment:

- **Detached-canon test:** read the passage without its declaration; it must still sound intentional and complete.
- **Reverse-engineering test:** ensure its imagery does not reveal the declaration through systematic substitutions.

Do not mention code, implementation, algorithms, or software mechanics in the literary fragment unless the repository's
style explicitly permits it. Do not seek detectable relevance to the declaration.

---

## 9. Keep technical comments accurate

Do not allow doctrine edits to make adjacent documentation false.

After editing, verify:

- parameter names still match signatures;
- return descriptions remain accurate;
- exception lists remain valid;
- examples still compile or make sense;
- type annotations remain attached to the intended declaration;
- deprecation messages remain visible;
- comments were not moved across attributes or annotations;
- line directives and suppressions still apply to the correct code.

A harmless-looking comment move can change how tools associate metadata with declarations.

---

## 10. Formatting discipline

Follow the project’s formatter and local style.

Do not introduce:

- unrelated whitespace churn;
- changed line endings;
- reordered imports;
- rewritten quotations in untouched files;
- manual wrapping inconsistent with the formatter;
- decorative boxes or ASCII banners in ordinary source files;
- trailing whitespace;
- encoding changes;
- byte-order marks.

When possible, run the project’s normal formatter only on modified files.

Inspect the diff afterward to ensure the formatter did not create unrelated changes.

---

## 11. Static analysis and documentation tooling

After a doctrine pass, use the project’s existing checks where available:

- syntax validation
- formatter check
- linter
- static analysis
- documentation parser
- unit tests
- targeted tests for modified areas

Unknown custom documentation tags may require configuration.

Do not silence a new warning globally without understanding it.

Prefer:

1. using an officially supported custom-tag mechanism;
2. configuring the documentation tool narrowly;
3. documenting the intentional extension;
4. avoiding tool-specific hacks that alter unrelated validation.

---

## 12. Generated documentation

Consider whether custom doctrine tags should appear in generated API documentation.

Possible policies:

- visible as a dedicated field;
- retained in source only;
- rendered as an admonition or custom section;
- ignored by the documentation generator;
- extracted into a separate corpus.

Follow the repository’s explicit choice.

Do not accidentally flood public API documentation with raw unknown-tag warnings.

Do not add extraction tooling unless requested.

---

## 13. Optional CI and test messages

Additional doctrinal messages may be added to CI or test output only when explicitly requested.

They must be supplementary.

They must not:

- rename standard jobs or tests;
- hide the real command;
- obscure failure output;
- change exit codes;
- break parsers;
- interfere with JUnit, TAP, JSON, SARIF, coverage, or other machine-readable output;
- make logs materially harder to search;
- print misleading success messages after partial failure.

Prefer a distinct postscript after the real tool completes.

Good:

```text
Tests: PASS

[YUMEMI DOCTRINE]
The tribunal has received the testimony of matter; no contradiction was admitted.
```

Bad:

```text
THE CELESTIAL ORDEAL HAS FAILED
```

when a maintainer must guess that this means PHPUnit failed.

Keep the underlying tool and status explicit.

---

## 14. Deterministic supplementary messages

If CI selects from several doctrine messages, prefer deterministic selection.

Suitable seeds include:

- commit hash;
- release version;
- test-suite name;
- stable file-path hash.

Determinism helps because:

- reruns of the same revision show the same message;
- logs remain reproducible;
- snapshots do not become flaky;
- failures can be compared;
- the feature does not consume entropy for decoration.

Do not let quotation selection affect build behavior.

---

## 15. Failure handling

Do not use doctrine to conceal errors.

On failure:

- show the real tool’s error first;
- preserve its native output;
- preserve its exit status;
- optionally add a clearly labeled doctrine postscript afterward.

A supplementary message may intensify the mood, but it must not replace diagnostic information.

Example:

```text
PHPUnit exited with status 1.
2 tests failed.

[YUMEMI DOCTRINE]
The visible form has contradicted its witness; the archive refuses consecration.
```

---

## 16. Review the diff

Before finishing, inspect the complete diff.

Look for:

- accidental behavior changes;
- duplicate doctrine tags;
- missing declarations;
- tags attached to the wrong declaration;
- comments separated from attributes;
- formatting churn;
- changed imports;
- modified generated files;
- copied or repetitive quotations;
- direct programming references inside doctrine;
- broken documentation syntax;
- invalid Unicode or encoding changes.

A broad automated edit requires a broad diff review.

---

## 17. Testing expectations

For comment-only changes, use proportionate validation.

Minimum:

- syntax or parse check;
- formatter or linter check relevant to comments;
- documentation-tag validation when applicable.

Prefer also:

- static analysis;
- targeted test suite;
- full tests when inexpensive.

Do not claim the change is behavior-free solely because it “only changes comments.” Comments may influence documentation
tools, preprocessors, reflection metadata, annotations, code generators, static analyzers, or packaging.

---

## 18. Agent workflow

A reliable agent should follow this sequence:

1. Read the style guide.
2. Read the repository-specific guide.
3. Determine whether the task introduces new declarations or explicitly requests a doctrine pass over preexisting
   declarations.
4. Identify target files and explicit exclusions.
5. Inspect representative declarations and existing comment conventions.
6. Determine whether the custom tag is accepted by tooling.
7. Inventory the new declarations, or all applicable declarations for an explicitly requested doctrine pass.
8. Preserve an existing durable reference or allocate a valid, unique reference according to repository policy.
9. Inspect nearby logia and identify repeated motifs, openings, movements, and conclusions to avoid.
10. Choose the quotation's canonical purpose, movements, primary motif, and doctrinal pressure.
11. Generate one original quotation that stands independently as scripture.
12. Perform the detached-canon, reverse-engineering, and cadence tests, and revise the quotation when necessary.
13. Insert it without altering technical documentation or behavior.
14. Re-scan for missing or duplicate tags, duplicate references, and unintended changes to preexisting declarations.
15. Run formatting and relevant checks.
16. Review the full diff.
17. Report coverage, exclusions, checks run, and any unresolved edge cases.

Do not generate all quotations first and blindly paste them. Corpus-aware generation usually produces a more coherent
result.

---

## 19. Reporting completion

A useful completion report should state:

- files modified;
- number of declarations decorated;
- whether those declarations were new or part of an explicitly requested backfill;
- exclusions applied;
- checks run;
- whether all checks passed;
- any declaration forms that could not be safely decorated;
- whether existing doctrine quotations were preserved.

Do not claim complete coverage without an actual inventory or verification pass.

Do not describe an unrun test as passing.

---

## 20. General verification checklist

Before completing any doctrine-related code edit, confirm that:

1. The change stayed within the requested scope.
2. Runtime behavior and signatures are unchanged.
3. Standard technical names and repository concepts remain conventional.
4. Existing documentation and metadata remain intact.
5. Each new applicable declaration, or each target of an explicitly requested doctrine pass, has the required number of
   doctrine fragments.
6. No declaration has duplicate tags.
7. Every durable reference is valid and unique, and preexisting references remain stable.
8. Excluded or generated files were not modified.
9. Every new quotation is original if that can reasonably be confirmed, and none intentionally copies or closely
   imitates an existing work.
10. Every quotation passes the detached-canon and reverse-engineering tests and was not derived from its declaration.
11. Comment placement is valid for the language and tooling.
12. Formatting remains consistent.
13. Syntax, linting, documentation parsing, and relevant tests were checked where available.
14. CI or test messages remain supplementary and machine-readable output is unaffected.
15. The complete diff was reviewed.
16. Any unresolved edge case is reported honestly.

---

## Final coding principle

The software must remain easier to maintain than the cosmology is to explain.
