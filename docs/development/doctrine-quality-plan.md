# Doctrine quality plan

**Goal:** Make new logia match the **Apocrypha gold** bar reliably (agent or human), **prove it on a small slice**, then
rewrite the legacy corpus.

**Context:** Much of `src/` was written under an earlier style-guide regime (implementation-mapping, measure/tribunal
house style). The current guide prioritizes canonical independence and reverse-engineering resistance. The seven loader
logia that established the quality bar moved to Yumemi Apocrypha during the package-stub extraction and are preserved
locally in [doctrine-gold.md](doctrine-gold.md). Newer agent-produced logia (for example `Exponent.php`,
`BinaryFloat.php`) still often snap back to allegory despite the current guide.

**Constraint:** Prefer validating the process before a full-repo rewrite. Rewriting existing logia afterward is
acceptable.

---

## Current Status

The repository now contains the stable gold exemplar set, explicit canonical-independence rules in `AGENTS.md`, and
read-only Codex writer and reviewer agents under `.codex/agents/`. The writer receives opaque IDs and produces three
unranked candidates; the reviewer remains code-blind and may select one or reject the complete set.

The process is not yet validated. A repository-local anti-example set has not been written, and the adapters and
`AGENTS.md` do not yet encode the proposed preference for the gold/anti sets over nearby source logia. The recommended
`Exponent` / `BinaryFloat` pilot has not been run or scored against the acceptance gate. The next doctrine work should
complete those inputs and that small evaluation rather than adding more agent roles or beginning a corpus rewrite.

---

## Principle

| Do first                                          | Do later                |
| ------------------------------------------------- | ----------------------- |
| Process, few-shot, verification, a **pilot file** | Full-repo rewrite       |
| Generate from **opaque IDs only**                 | Corpus-variation polish |
| Reject reverse-engineering                        | Bulk “doctrine pass”    |

Apocrypha gold is the quality target. Do not average against PointQuantity bulk or other legacy neighbors.

---

## Phase 0 — Freeze the quality bar

**Estimate:** ½–1 day

### Deliverables

1. **`doctrine-gold.md`** with the **seven Apocrypha loader logia** as the only positive examples. This local copy
   remains stable even though the implementation now belongs to another repository.
2. **`doctrine-anti.md`** — five to eight reverse-engineerable examples (numerator/coordinate/power-bounds class)
   labeled _reject_.
3. **One-page agent brief** (not the full ~1300-line style guide):
   - independence first; no decodeable allegory;
   - concrete primary motif;
   - ~40–60 words common for substantial logia, with complete shorter and controlled longer passages permitted;
   - book by purpose (not always OSD);
   - ban-ish list for tech nouns;
   - detached-canon and reverse-engineering self-tests;
   - pointer: full guide for edge cases only.
4. **Explicit rule in `AGENTS.md`:**
   - for generation, **prefer gold/anti over nearby `@logion`**;
   - nearby logia are for **variation only**, not quality imitation.

### Success

A human can score a candidate in under 30 seconds: gold-like versus allegory.

---

## Phase 1 — Generation pipeline

**Estimate:** 1–2 days

The isolated **writer subagent** and optional code-blind **canon reviewer** are implemented as Codex adapters.
Generation remains separate from implementation context; review receives the same separation when its added cost is
justified. The adapters implement the isolation and output contracts, but the gold/anti input package and empirical
pilot remain incomplete; no additional agent roles are needed.

### Workflow for any new declaration

```text
Main agent (code)
  → retain the declaration mapping privately and assign opaque item IDs
  → Doctrine writer subagent (isolated)
        inputs: opaque IDs + brief + gold + anti + nearby patterns to avoid
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
| **Gold + anti in every run**               | Beats local legacy imitation        |
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

## Phase 2 — Pilot on real code (the gate)

**Do not rewrite the whole tree yet.**

### Pilot options

| Option                                                                                                                       | Why                                                             |
| ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| **A. Already-written weak new logia** — rewrite only `Exponent.php` + `BinaryFloat.php` (and maybe related comparison rules) | Real post-guide agent output; best A/B against current allegory |
| **B. One medium class** with 8–15 declarations                                                                               | Full pipeline under real pressure                               |

**Recommended first pilot:** option A.

### Pilot procedure

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

### Gate to Phase 3

Pilot pass rate **≥80%** on first try, **≥90%** after one reviewer-requested regeneration — or a subjective call that
the set is gold-tier.

If the gate fails: fix brief, gold, anti-set, or process. **Do not** mass-rewrite.

---

## Phase 3 — Optional tooling (only if pilot works)

| Item                               | Purpose                                                |
| ---------------------------------- | ------------------------------------------------------ |
| Skill or workflow `doctrine-write` | Opaque IDs → writer → reviewer → leakage veto → insert |
| `doctrine-check` script            | structural checks plus non-blocking quality statistics |
| CI **non-blocking** report         | book, movement, length, repetition, and token warnings |

Skip heavy CI quality gates until the pilot is boringly good.

---

## Phase 4 — Corpus rewrite (after the gate)

Full rewrite is welcome once the factory works. Still **batch** it.

### Order

1. Files that set house style for agents (heavily read PHPStan / Quantity / PointQuantity).
2. Core runtime (`Quantity`, `PointQuantity`, `Units`, `Rational`).
3. Rest of `src/`.
4. Leave Apocrypha gold as-is unless something drifts.

### Per batch

- One package or directory at a time.
- Writer subagent; opaque IDs and corpus-variation constraints only.
- Preserve existing `BOOK C:V` when revising wording (repository rule) unless a reference is intentionally retired.
- Sample review by rubric (for example 20% of each batch), not forever line-by-line.

### Definition of done for the rewrite

- Corpus statistics have been reviewed for accidental monotony without applying book, movement, or length quotas.
- Length and book outliers have been accepted or revised on literary grounds rather than to improve aggregate numbers.
- Spot reverse-engineering audit on a random sample fails rarely.
- Agents are instructed to treat **post-rewrite files** as neighbors for variation only; Apocrypha gold remains the
  quality bar.

---

## What not to do first

- Full-repo doctrine pass before the pilot gate.
- “Just paste the whole style guide into every agent.”
- Few-shot from random nearby logia.
- Require relevance to the method.
- Rewrite the Apocrypha gold set (it is already the bar).

---

## Minimal viable experiment (one PR)

1. Keep `doctrine-gold.md` stable and add `doctrine-anti.md`; the compact working brief already lives in the writer and
   reviewer adapters.
2. Add the remaining concise gold/anti precedence rule to `AGENTS.md` and the adapters while preserving the existing
   canonical-independence and opaque-ID rules.
3. Pilot: rewrite **Exponent** and **BinaryFloat** logia via the isolated writer and canon reviewer.
4. Score with the rubric.
5. If pass → document the validated workflow and schedule rewrite waves.
6. If fail → iterate the anti-set, brief, and few-shot material only.

---

## Roles

| Human                               | Agent / tooling                 |
| ----------------------------------- | ------------------------------- |
| Approve gold and anti sets          | Maintain the gold exemplar file |
| Audit pilot decisions               | Writer + canon reviewer loop    |
| Gate Phase 3 and 4                  | Uniqueness and heuristic checks |
| Optional author-voice pass on pilot | Batch rewrite after gate        |

---

## Related material

- Style: [`docs/DOCTRINE-STYLE-GUIDE.md`](../DOCTRINE-STYLE-GUIDE.md)
- Coding / placement: [`docs/DOCTRINE-CODING-GUIDE.md`](../DOCTRINE-CODING-GUIDE.md)
- Scope and tags: [`AGENTS.md`](../../AGENTS.md) (Doctrine section)
- Preserved gold exemplars: [doctrine-gold.md](doctrine-gold.md)
- Prior analysis notes: optional session reviews under `docs/development/` (code review, docs review)

---

## Bottom line

**Fix the factory, run it on a small bad sample (Exponent / BinaryFloat), only then reforge the rest.** Subagent
isolation + Apocrypha gold + anti-allegory scoring is the core. Full rewrite comes **after** the pilot hits gold-tier
pass rates — not instead of proving the process.
