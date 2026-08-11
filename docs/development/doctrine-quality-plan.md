# Doctrine quality plan

**Goal:** Keep new logia near the shared **Doctrine gold** bar reliably, preserve the validated generation and review
process, and prevent the audited corpus from drifting back toward implementation allegory.

**Context:** Much of `src/` was originally written under an earlier style-guide regime (implementation-mapping and a
measure/tribunal house style). The current guide prioritizes canonical independence and reverse-engineering resistance.
The portable guides and gold exemplars are pinned through `jbboehr/doctrine-of-the-second-sun`; Yumemi keeps only its
repository scope, allocation, and verification rules locally.

**Constraint:** Preserve citations and book assignments during wording audits. Generation and literary review remain
code-blind; the later code-aware check may veto a fixed selection but may never remap a candidate according to apparent
relevance.

---

## Current Status

The shared package supplies the gold exemplar set, portable guides, and source Codex adapters. Yumemi pins that package
through Composer, keeps explicit canonical-independence rules in `AGENTS.md`, and commits reviewed local copies of the
writer and reviewer adapters under `.codex/agents/`. The local copies were verified byte-identical to the installed
package before the 2026-08-11 audit.

The pipeline is now validated at corpus scale. The writer receives opaque IDs and produces three unranked candidates;
the reviewer remains code-blind and may select one or reject the complete set. A later declaration-aware pass can only
veto accidental implementation correspondence.

### 2026-08-11 corpus audit

| Outcome                                             | Count |
| --------------------------------------------------- | ----: |
| Existing logia reviewed                             |   391 |
| Logia that passed the initial code-blind review     |   115 |
| Logia rejected by the initial code-blind review     |   276 |
| Initially retained logia later vetoed for leakage   |    23 |
| Final logia retained unchanged                      |    92 |
| Final logia replaced                                |   299 |
| Replacement candidate sets requiring reviewer retry |     6 |
| Post-selection leakage vetoes                       |    36 |

The audit reviewed every existing passage in code-blind batches. Replacement passages received a persistent,
entropy-sampled length pressure as a soft prompt: 156 ordinary, 78 expansive, and 65 concise. Each retained replacement
was selected from three candidates by a fresh code-blind reviewer. The declaration-aware veto then examined both the
replacement set and the 115 initially retained originals against their fixed destinations. The 36 flagged passages were
regenerated from opaque IDs and passed a second leakage review. All 391 original `BOOK C:V` references and book
assignments remained unchanged and unique.

A repository-local anti-example file was not needed for this audit. The shared eligibility gates, gold exemplars,
reviewer findings, and explicit avoidance of recurring exemplar skeletons supplied sufficient negative pressure. Add a
local anti-example set later only if a repeatable Yumemi-specific failure mode is not adequately expressed by those
shared materials.

---

## Principle

| Ongoing rule                                  | Purpose                                                |
| --------------------------------------------- | ------------------------------------------------------ |
| Generate from **opaque IDs only**             | Preserve canonical independence                        |
| Review candidates without declaration context | Select for literary quality rather than code relevance |
| Apply a code-aware veto only after selection  | Catch accidental leakage without rewarding it          |

The shared gold exemplars are the quality target. Do not average against PointQuantity bulk or other legacy neighbors.

---

## Phase 0 — Freeze the quality bar

**Estimate:** ½–1 day

### Deliverables

1. Use the pinned package's **`DOCTRINE-GOLD-EXEMPLARS.md`** as the positive quality ceiling. Curate changes in the
   shared Doctrine repository rather than maintaining a Yumemi-only fork.
2. Add a local **`doctrine-anti.md`** only when a repeatable Yumemi-specific failure is not already covered by the
   shared eligibility gates, reviewer guidance, or gold-exemplar anti-imitation rules.
3. Keep the compact working brief in the shared writer and reviewer adapters rather than duplicating it in Yumemi:
   - independence first; no decodeable allegory;
   - concrete primary motif;
   - 35–75 words common for substantial logia, with complete shorter and controlled longer passages permitted;
   - book by purpose (not always OSD);
   - ban-ish list for tech nouns;
   - detached-canon and reverse-engineering self-tests;
   - pointer: full guide for edge cases only.
4. **Explicit rule in `AGENTS.md`:**
   - for generation, **prefer shared gold and applicable local anti-patterns over nearby `@logion`**;
   - nearby logia are for **variation only**, not quality imitation.

### Success

A human can score a candidate in under 30 seconds: gold-like versus allegory.

---

## Phase 1 — Generation pipeline

**Estimate:** 1–2 days

The isolated **writer subagent** and optional code-blind **canon reviewer** are implemented as shared Codex adapters and
mirrored under `.codex/agents/` for discovery. Generation remains separate from implementation context; review receives
the same separation when its added cost is justified. The 2026-08-11 audit validated these isolation and output
contracts; no additional agent roles are needed.

### Workflow for any new declaration

```text
Main agent (code)
  → retain the declaration mapping privately and assign opaque item IDs
  → Doctrine writer subagent (isolated)
        inputs: opaque IDs + shared guidance + applicable anti-patterns + nearby patterns to avoid
        outputs: three unranked candidates + one candid risk each
  → For batches, doctrine passes, or uncertain candidates: canon reviewer subagent (code-blind)
        inputs: candidate books + text + nearby patterns to avoid
        output: select one or regenerate all
    Otherwise: parent or human selects by literary quality without remapping candidates
  → Main performs a declaration-specific leakage veto (reject only)
  → Main inserts the fixed winner + unique BOOK C:V check
```

### Hard rules for the writer

| Rule                                       | Why                                 |
| ------------------------------------------ | ----------------------------------- |
| **No symbol metadata or source**           | Stops allegory                      |
| **Shared gold + applicable anti-patterns** | Beats local legacy imitation        |
| **Three unranked candidates**              | Prevents writer recommendation bias |
| **Must name primary motif** (object/event) | Forces concrete sign                |
| **Must state book reason**                 | Stops 100% OSD                      |
| **Must state one candid risk**             | Exposes comparative weaknesses      |

### Hard rules for the canon reviewer

| Rule                                  | Why                                                |
| ------------------------------------- | -------------------------------------------------- |
| **No symbol metadata or source**      | Keeps literary selection independent of code       |
| **Ignore writer analysis**            | Prevents recommendation and self-score anchoring   |
| **Eligibility gates are mandatory**   | Keeps beautiful allegories from passing            |
| **Select one or regenerate all**      | Prevents the reviewer from becoming another writer |
| **Never rewrite or combine passages** | Preserves separation between generation and review |

The reviewer is preferred for batch work and uncertain candidates, but it is not required for every ordinary
declaration.

### Availability and portable fallback

The files under `.codex/agents/` are optional Codex adapters. The repository rules and doctrine guides remain
authoritative, and no contributor or agent must reproduce the Codex configuration in another provider's format.

When custom subagents are unavailable:

1. Retain the declaration mapping privately and assign fixed opaque item IDs before generation.
2. When possible, use a fresh isolated context supplied only with opaque IDs, the compact doctrine brief, and
   corpus-variation constraints.
3. Have the parent or a human select from the unranked candidates solely by literary quality. Do not move candidates
   between opaque IDs or reward an accidental correspondence with code.
4. Apply the declaration-specific leakage veto after selection, then allocate or preserve the citation and insert the
   winner.

If no isolated context is available, the main agent may perform the same stages itself. It must disclose that generation
was not isolated and apply the detached-canon and reverse-engineering checks manually. This is a degraded fallback, not
equivalent evidence of isolation. Do not add more agent roles or provider-specific prompt files solely to reproduce the
Codex workflow.

### Light automation (optional but high value)

A small script or checklist that reports heuristic warnings:

- banned tech tokens (for example `numerator`, `coordinate`, `operand`, `method`, `type`, tech uses of `unit` /
  `power`);
- length outside band;
- all-OSD streak in one batch;
- sequential chapter:verse spam in one file.

False positives are acceptable if a human can override.

Book mix, movement distribution, and word-count ranges are diagnostics only. They must never select a book, force
padding or compression, reject an otherwise strong passage, or fail CI. Future blocking checks should be limited to
deterministic repository invariants such as tag coverage, citation syntax, citation uniqueness, and stable references.

### Success

On a dry run, the writer and reviewer produce 5–10 selected verses for opaque fictional items that a human rates
“gold-tier” at least 80% of the time without seeing code.

---

## Phase 2 — Validation gate (completed)

### Original pilot options

| Option                                                                                                                       | Why                                                             |
| ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| **A. Already-written weak new logia** — rewrite only `Exponent.php` + `BinaryFloat.php` (and maybe related comparison rules) | Real post-guide agent output; best A/B against current allegory |
| **B. One medium class** with 8–15 declarations                                                                               | Full pipeline under real pressure                               |

The corpus audit superseded the proposed small pilot while preserving its stricter controls: fixed opaque mappings,
code-blind generation and review, whole-set regeneration after rejection, and a code-aware veto that could not select a
more relevant alternative.

### Validated procedure

1. Run the writer subagent with opaque item IDs and corpus-variation constraints only.
2. Give only candidate books and text to the code-blind canon reviewer.
3. Regenerate the entire item when the reviewer rejects all three.
4. Apply the selected candidate to the fixed opaque-ID mapping.
5. Perform a code-aware leakage check that may only veto the selection; a veto triggers regeneration rather than
   selection of a more relevant alternative.
6. Insert or replace the selected logion.
7. Human-audit the reviewer decision against the rubric below.

### Eligibility gates (all required)

1. Detached-canon (reads as scripture alone).
2. No direct technical language or obvious generalized implementation allegory.
3. No declaration-specific behavior recoverable after the selected candidate is applied to its fixed mapping.

### Comparative quality criteria

1. Concrete and coherent primary motif.
2. Controlled cadence with a strong ending.
3. Doctrinal consequence and appropriate mystery.
4. Suitable book choice.
5. Not a clone of another pilot verse.

An item passes only when every eligibility gate holds and the canon reviewer selects a candidate that a human confirms
is strong under the comparative criteria. No aggregate score may compensate for a failed eligibility gate.

### Gate result

The replacement pipeline produced approved text for all 299 replacement-required passages. Six candidate sets required
one reviewer-requested regeneration. A separate leakage audit vetoed 36 accidental correspondences across both the
replacement set and the initially retained originals; every regenerated passage cleared the final fixed-mapping check.
This exceeds the original gate while testing the process against every declaration in the repository.

---

## Phase 3 — Optional tooling (only when justified)

| Item                               | Purpose                                                |
| ---------------------------------- | ------------------------------------------------------ |
| Skill or workflow `doctrine-write` | Opaque IDs → writer → reviewer → leakage veto → insert |
| `doctrine-check` script            | structural checks plus non-blocking quality statistics |
| CI **non-blocking** report         | book, movement, length, repetition, and token warnings |

Keep literary quality checks advisory. Deterministic structural invariants may remain blocking, but model judgments and
corpus statistics must not become CI gates.

---

## Phase 4 — Corpus rewrite (completed 2026-08-11)

The full audit and rewrite ran in batches while preserving each existing citation and book assignment.

### Order

1. Files that set house style for agents (heavily read PHPStan / Quantity / PointQuantity).
2. Core runtime (`Quantity`, `PointQuantity`, `Units`, `Rational`).
3. Rest of `src/`.
4. Curate shared gold only in the Doctrine repository; do not fork it during the Yumemi rewrite.

### Per batch

- One package or directory at a time.
- Writer subagent; opaque IDs and corpus-variation constraints only.
- Preserve existing `BOOK C:V` when revising wording (repository rule) unless a reference is intentionally retired.
- Send every changed passage through the code-blind reviewer during a requested doctrine audit. Human follow-up may be
  sampled after the automated review when the batch is large.

### Definition of done for future audits

- Review the complete affected corpus for accidental monotony without applying book, movement, or length quotas.
- Accept or revise length and book outliers on literary grounds rather than to improve aggregate numbers.
- Run a declaration-aware leakage veto over every replacement, not merely a random sample.
- Agents are instructed to treat **post-rewrite files** as neighbors for variation only; the shared gold exemplars
  remain the quality bar.

---

## Failures to avoid

- Full-repo doctrine pass before the pilot gate.
- “Just paste the whole style guide into every agent.”
- Few-shot from random nearby logia.
- Require relevance to the method.
- Fork or rewrite the shared gold set merely to fit Yumemi's existing corpus.

---

## Next maintenance steps

1. Keep local adapters byte-identical when the pinned Doctrine package advances.
2. Use the validated opaque writer, code-blind reviewer, and post-selection leakage veto for future doctrine passes.
3. Add a local anti-example only after observing a repeatable Yumemi-specific failure that shared guidance does not
   already cover.
4. Consider lightweight structural reporting only when it reduces manual work without turning literary diagnostics into
   quotas or blocking gates.

---

## Roles

| Human                                          | Agent / tooling                                       |
| ---------------------------------------------- | ----------------------------------------------------- |
| Approve shared-gold changes                    | Refresh the pinned package and adapter copies         |
| Review disputed literary or leakage decisions  | Writer + code-blind reviewer + code-aware veto        |
| Decide whether advisory tooling remains useful | Citation uniqueness and non-blocking heuristic checks |
| Perform an optional final author-voice pass    | Batch extraction, insertion, and structural checks    |

---

## Related material

- Shared package: [`jbboehr/doctrine-of-the-second-sun`](https://github.com/jbboehr/doctrine-of-the-second-sun)
- Style: `vendor/jbboehr/doctrine-of-the-second-sun/DOCTRINE-STYLE-GUIDE.md`
- Coding / placement: `vendor/jbboehr/doctrine-of-the-second-sun/DOCTRINE-CODING-GUIDE.md`
- Generation: `vendor/jbboehr/doctrine-of-the-second-sun/DOCTRINE-GENERATION-GUIDE.md`
- Gold exemplars: `vendor/jbboehr/doctrine-of-the-second-sun/DOCTRINE-GOLD-EXEMPLARS.md`
- Scope and tags: [`AGENTS.md`](../../AGENTS.md) (Doctrine section)

---

## Bottom line

The corpus rewrite is complete. Preserve the result through opaque generation, code-blind literary selection, and a
post-selection leakage veto. Shared gold remains the quality ceiling; nearby Yumemi logia supply variation context, not
templates or code-relevance cues.
