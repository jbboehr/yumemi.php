# Documentation detail audit: 2026-09-06

The public documentation mostly explains behavior that application developers need. The worthwhile changes are targeted:
move internal expression and catalog mechanics into contributor references, shorten a few adapter explanations, and make
ordinary quantity methods easier to find. Exactness, compatibility, limits, and operational caveats should remain
public.

This audit covers the 43 tracked Markdown documents at `514e888dab59eb11cb266443d35c566925eb213c`. Public pages were
read for application-facing detail. Contributor material was reviewed for audience, organization, duplication, and the
purpose of its technical sections. Legal and governance documents were inventoried as authoritative material, not
reviewed for legal correctness or proposed for simplification. Generated HTML, dependency documentation, copied license
texts, and literary quotations are outside the editorial scope. This is not a review of every source PHPDoc or comment.

Locations below refer to that snapshot. These are editorial findings, not newly reproduced runtime defects. All eight
findings have been addressed in the working tree for review. The [resolution record](#resolution-record) maps each
finding to its changes. The separate 0.2 release-preparation work is recorded in [planning.md](planning.md).

## Findings

### 1. Remove internal expression admission from application construction guidance

In [Contexts And Construction](../pages/reference/runtime.md#contexts-and-construction), lines 104–108 explain unbound
unit leaves, stamping an immutable copy, independent admission into another context, and definition-less unit names. The
paragraph itself identifies this construction path as internal. It asks application readers to understand a path they
are advised not to use, directly before their first quantity-construction example.

Keep the surrounding observable rules: use the same `Units` instance, keep an expression's context alive, expect the
named context exception, and distinguish structural equality and formatting from semantic operations. Replace only the
internal-construction explanation with:

> Obtain named expressions through `Units::parse()` or `Units::unit()` so they use that context's catalog definitions.

The copy/admission mechanism already has a home in [Runtime Data Flow](architecture.md#runtime-data-flow) and the
[registry-context invariant](invariants.md#registry-snapshots-define-a-semantic-context). Preserve any additional useful
qualification there. Similarly, “ordinary fast path” and the proposed “quantity-kind model rather than another dimension
subclass” in runtime lines 622–642 can be removed without removing the SI axis order, extension-axis API, or the
gray/sievert limitation.

### 2. Move catalog storage and capability-cache mechanics out of the catalog reference

[Catalog Semantic Support](../pages/reference/catalog.md#catalog-semantic-support), especially lines 208–216, explains
direct and inherited record markers, results that are not eagerly materialized, delta-record generation timing, and
avoiding full-catalog resolution. Lines 154–158 also explain lazy resolution while introducing `describe()`.

Application callers need to know which name a descriptor describes and which operations it supports. They do not need
the storage or evaluation strategy. The detailed paragraph can also make an internal catalog representation look like an
integration contract, although the [compatibility policy](compatibility.md#internal-and-generated-details) excludes that
representation.

Keep the enum meanings, capability methods, prefix decomposition, alias precedence, custom-definition failures, and the
distinction between affine conversion and multiplicative algebra. A sufficient explanation is:

> Capabilities describe the complete spelling in the configured registry, including its aliases, prefixes, and dependent
> definitions. Use `supportsMultiplicativeAlgebra()` and `supportsConversion()` to choose an operation.

Preserve storage, synthesis, and caching rationale in the
[generated-catalog inventory](generated-artifacts.md#generated-udunits2-catalog) or
[semantic-core architecture](architecture.md#semantic-core). The public builder's immutable-snapshot and duplicate-name
rules remain useful and should stay.

### 3. Keep parser controls and error contracts, trim the internal parser tour

[Native Parser Selection](../pages/reference/runtime.md#native-parser-selection), lines 72–84, mixes deployment guidance
with the generated parser's authority, AST equivalence, Unicode implementation sources, and backend-neutral cached ASTs.
The last paragraph of [Errors And Source Locations](../pages/reference/unit-syntax.md#errors-and-source-locations),
lines 197–198, additionally advertises `errorSpan()` on internal PHPStan parse-result objects. Application readers have
no reason to call that adapter API. Line 189's explanation of rejection before the cache is also an enforcement detail.

Retain automatic selection of a compatible extension, PHP fallback, every supported environment value, startup-time
configuration, and the fact that the setting affects PHPStan. Keep the warning that rare Unicode identifiers can differ
between backends. Keep byte offsets, exception properties, nested-definition attribution, and the parser-budget table.
Those details let callers configure the process and interpret errors correctly.

Move AST, cache, and lexer implementation explanations to [Foundational Model](architecture.md#foundational-model) or
the [bounded-work invariant](invariants.md#unit-expression-work-is-bounded). Replace the `errorSpan()` reference with a
link to the user-facing [PHPStan diagnostics](../pages/reference/phpstan.md#diagnostics). Do not remove supported
runtime `SourceSpan` behavior along with an internal adapter method.

### 4. Describe PHPStan outcomes without explaining incidental adapter mechanics

Several short passages in the [PHPStan reference](../pages/reference/phpstan.md) can be reduced without losing a caller
decision:

| Location                                | Excess detail                                                                         | Preserve publicly                                                                                                           | Existing destination for implementation rationale                            |
| --------------------------------------- | ------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| Lines 471–476, angle functions          | Semantic record comparison, descriptive fields, and record-key order                  | Canonical angle definitions and their dependencies must retain bundled meanings; custom redefinitions can disable inference | [Custom Registries](native-angle-functions.md#custom-registries)             |
| Lines 685–686, optional operators       | Unary signs delegate through `mul(1)` and `mul(-1)`                                   | Unary signs retain the quantity type, symbolic unit, and registry context                                                   | [Handler Behavior](operator-overloading.md#handler-behavior)                 |
| Lines 787–790, registry configuration   | Definition and primitive-dimension contributions to a fingerprint                     | Changes to registry semantics invalidate analysis results                                                                   | [PHPStan Adapter](architecture.md#phpstan-adapter)                           |
| Lines 853–856, third-party integrations | Package ownership of matrices and fixtures and the reason for separating repositories | Where to find Apocrypha's supported integrations and configuration                                                          | [External Integration Package](architecture.md#external-integration-package) |

The optional-tag warning at lines 846–849 is worth keeping. Replacing PHPStan parser services explains a real extension
conflict and upgrade risk that affects whether a library should opt in. The numeric inference rules, finite-alternative
limits, PHP-version differences, and benevolent-versus-explicit-union distinction also describe actual diagnostics and
accepted types. They should not be removed merely because they use technical terminology.

### 5. Keep the landing pages focused on application decisions

The root [README](../../README.md), lines 101–104, summarizes reduction, substitution, normalization, simplification,
and affine translation immediately after a simple conversion example. These are useful reference semantics, but the
landing page has not introduced the reader's need for most of them. One sentence directing readers to quantity
arithmetic and temperature conversion would provide a clearer next step.

Lines 120–122 then send readers to architecture/status planning and the engineering-oriented Pint comparison. The book
[Introduction](../pages/README.md), lines 53–56, also ends with a planning link. Put maintainer-roadmap pointers in
[CONTRIBUTING.md](../../CONTRIBUTING.md), which already links the engineering documents. Preserve the release-status
statement, installation guidance, useful public-reference links, and licensing information. The brief explanation that
runtime and PHPStan share unit semantics is useful reassurance and does not need removal.

### 6. Restore a clear boundary between quantity methods and optional operators

This is a related organization issue. In [Quantity Types](../pages/reference/phpstan.md#quantity-types), the
`### Optional Quantity Operators` heading at line 638 remains in effect through line 745. The ordinary method-inference
list begins at line 688, followed by conversion, extraction, comparison, and point-method rules. The next heading is
`## Registry Configuration`. The sidebar mirrors that hierarchy.

This makes always-available method inference appear to belong to the optional native-extension section. Move the
ordinary method and point material before the optional-operator subsection, leaving the operator-specific configuration,
operand table, and caveats together. A brief shared comparison caveat can link back to the method guidance. Preserve
every example and inference limitation. Update `headingsByChapter` in [yumemi.js](../theme/yumemi.js) if the solution
introduces or changes headings; moving material under existing headings may suffice.

### 7. Reduce duplication in the active roadmap, preserve the experimental records

[planning.md](planning.md) is intended to identify status, rationale, risks, and future work. Its verification roadmap
(snapshot lines 570–660) repeats detailed branch and mutation results already recorded in
[Recorded Audits](branch-coverage.md#recorded-audits). Its known-limitations section, lines 661–799, includes a long
inventory of implemented PHPStan functions and multiple profiling narratives. Current contributors must separate those
completed investigations from work that still needs doing.

Keep the current decision, remaining limitation, evidence link, and condition for reconsideration in the roadmap. Keep
full experiments in the relevant engineering record. Move unique measurements before shortening anything, including the
memoization experiment that did not materially improve performance and the evidence against additional caches. The
[Pint comparison's performance section](pint-parity.md#29-performance-and-caching) also repeats exact internal cache
budgets; link to their engineering record instead of maintaining another copy of those numbers.

The long [project review](project-review-2026-09-04.md) is an appropriate place for reproduction steps, baseline
comparisons, review corrections, measured costs, and verification limits. Its length is not a reason to discard that
evidence. Benchmark and coverage guides likewise need enough detail to distinguish what their measurements establish.

### 8. Label superseded implementation plans as history

The [operator-overloading plan](operator-overloading.md) says its separate extension and library integration are
implemented, but still presents a “Spike Checklist” and “Likely Package Shape” as choices to make next (lines 265–320).
Those include hypothetical repository layouts. The [doctrine quality plan](doctrine-quality-plan.md) records completed
validation and corpus work while retaining original phase estimates and pilot instructions.

Keep current integration rules, replacement constraints, accepted decisions, and verified failure cases. Clearly group
the original proposals under a historical heading, and put remaining maintenance or release tasks near the current
status. Preserve rejected alternatives as rationale. This is a status/placement cleanup, not a request to reopen either
design or change the Doctrine's literary content.

## Detail worth retaining

- Exact rational versus binary-float behavior, rounding modes, overflow/underflow policy, exponent limits, and
  non-terminating-decimal errors determine which API a caller should choose.
- Registry identity, expression lifetime, Fiber bootstrap/restoration rules, JSON's receiving-registry semantics, and
  native serialization options prevent plausible misuse. `Dimension` in a deserialization allow-list is operationally
  necessary even though it reveals a class inside the serialized graph.
- Prefix precedence, spelling collisions, compact-selection boundaries, and source-text versus resolved-expression
  formatting explain visible results. Retain those rules and the concrete examples.
- Parser limits and byte-span conventions support input validation and error display. Documented exception metadata,
  including context-ID ordering, is a contract even when it is too specialized for the construction introduction.
- Native-brand erasure, exact roots, nominal angle requirements, optional-tag fallback matching, and incomplete
  unpacking/callable inference establish the static checker's practical limits.
- Generated-artifact provenance, tool pins, handler allocation/delegation constraints, experimental controls, and
  persistence-producer instructions belong in contributor documentation. Their technical depth serves that audience.

## Coverage and editing boundaries

The inventory below accounts for all 43 Markdown files at the audited snapshot. Grouped engineering documents received
an audience and section-level review, with detailed reading of relevant implementation passages and duplicate records.

| Documents                                                                                                  | Assessment                                                                                          |
| ---------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| Root README; book Introduction and Summary                                                                 | Landing-page pointers in finding 5; chapter sequence is useful                                      |
| Getting Started; Core Concepts; Recipes                                                                    | Practical API choices, migration rules, examples, and performance advice are useful                 |
| Runtime; Catalog; Unit Syntax; PHPStan references                                                          | Targeted changes in findings 1–4 and 6; preserve observable behavior                                |
| Public catalog-generation guide                                                                            | Correct contributor boundary; input order and generation steps are necessary                        |
| CONTRIBUTING; pull-request template; AGENTS                                                                | Contributor and agent instructions are in appropriate locations                                     |
| CHANGELOG                                                                                                  | User-visible release history; migration completeness belongs to the planned 0.2 review              |
| Architecture; compatibility policy; semantic invariants                                                    | Appropriate owners for component mechanics and contract rationale                                   |
| Generated artifacts; release and succession                                                                | Required reproducibility, provenance, and release instructions                                      |
| Benchmarking; branch coverage; mutation testing                                                            | Required measurement controls and interpretation; avoid copying their full records into the roadmap |
| Planning; Pint parity                                                                                      | Reduce repeated implementation/status detail as described in finding 7                              |
| Native angle functions; preferred/compact selection; rational backend evaluation; PHPStan repetition audit | Focused design and experimental records; retain reasoning, rejected options, and verification       |
| Operator overloading; doctrine quality plan                                                                | Keep engineering evidence, distinguish original plans from current work (finding 8)                 |
| Project review dated 2026-09-04                                                                            | Preserve the requested experimental audit trail                                                     |
| Heliogenesis; logion plates                                                                                | Asset ownership, accessibility, copying, and provenance details are relevant to theme maintenance   |
| Probator README; conformance README; compatibility-fixture README; API-compatibility-tool README           | Focused contributor procedures; keep their constraints and replay instructions                      |
| LICENSE; CLA; license exception; Project Steward; Code of Conduct                                          | Authoritative legal/governance material, outside simplification recommendations                     |

The original audit suggested public prose, quantity-section placement, and contributor organization as separate slices.
The requested cleanup addresses all eight findings together. The next planned work remains 0.2 preparation.

## Resolution Record

1. Runtime construction now recommends `Units::parse()` and `Units::unit()` while retaining the context, lifetime, and
   exception rules. [Runtime Data Flow](architecture.md#runtime-data-flow) preserves immutable admission and
   definition-less-name behavior. The dimensions reference retains the SI order, extension axes, and gray/sievert
   limitation without fast-path or speculative model commentary.
2. Catalog introspection now describes observable capabilities. The
   [generated-catalog inventory](generated-artifacts.md#generated-udunits2-catalog) owns record classification, delta
   synthesis, lazy resolution, and caching rationale. Public builder rules and capability examples remain.
3. Parser guidance retains selection values, startup configuration, PHPStan applicability, Unicode caveats, limits, and
   error locations. [Foundational Model](architecture.md#foundational-model) preserves AST, cache, and lexer mechanics.
   Unit syntax now links to PHPStan diagnostics instead of advertising the internal parse-result API.
4. The PHPStan reference states angle prerequisites, unary-sign results, registry cache invalidation, and where to find
   third-party integration instructions. Adapter mechanics remain in the angle design, operator plan, and architecture.
   Optional-tag conflict warnings and inference limits remain public.
5. The root README links to quantity arithmetic and affine conversion after its runtime example. Maintainer roadmap and
   feature-comparison links now live in `CONTRIBUTING.md`. Both landing pages retain installation or release-status
   guidance, public documentation links, and their existing examples.
6. Ordinary quantity and point method inference now precedes Optional Quantity Operators. Existing headings and anchors
   remain unchanged, so the sidebar metadata needs no edit.
7. The roadmap now links to [recorded benchmarks](benchmarking.md#recorded-investigations),
   [coverage and mutation audits](branch-coverage.md#recorded-audits), and one
   [cache-retention policy](architecture.md#cache-retention). Unique measurements, including rejected memoization and
   helper-cache experiments, were moved before shortening the roadmap. Pint parity links to those records. The original
   project review remains unchanged.
8. Operator proposals and dated release assessments are grouped as history, with current release coordination near the
   status. The Doctrine plan places maintenance, generation, review, and audit rules before its historical estimates,
   pilot choices, and corpus record. Literary passages and citations remain unchanged.

## Verification

Evidence for the findings comes from the Markdown, the sidebar heading map, and the compatibility/architecture
documents. No user study or new runtime experiment was conducted. Recommendations about reader effort are editorial
judgments.

`DocumentationCorpus` includes the root README, `docs/pages/` except Summary, and the selected builder PHPDoc. It does
not include development reports. The cleanup preserves PHP fences, diagnostic expectations, separate-process directives,
and distinct example declarations in that corpus. This report introduces no executable PHP example.

### Original Audit Validation

- `composer test -- tests/Documentation` passed: 63 tests and 1,946 assertions.
- `composer check:full` passed: 2,431 tests, 28,811 assertions, and five expected skips, including the documentation
  build and generated-link check, PHPStan, formatting, benchmark smoke checks, and archive consumers.
- An HTML-parser check against the built PHPStan page confirmed that the ordinary method list follows the “Optional
  Quantity Operators” heading. This verifies finding 6's heading placement, not its effect on reader comprehension.
- A local path/heading check resolved all 64 relative Markdown links in this report and the updated planning page. The
  public book's link check does not include these development documents.
- `nix fmt -- docs/development/documentation-detail-audit-2026-09-06.md docs/development/planning.md` and
  `git diff --check` passed. Only this report's verification record and a wording clarification changed after the full
  gate; formatting and link checks were repeated afterward.

The Nix/PHP-version matrix, separate committed-revision compatibility check, dependency audit, external-link checks, and
browser visual review were not run for this documentation-only audit. Those release checks remain part of the planned
0.2 preparation where required by the runbook.

### Cleanup Validation

After applying all eight findings on 2026-09-06:

- `composer test -- tests/Documentation` passed: 63 tests and 1,946 assertions.
- `composer check:full` passed: 2,431 tests, 28,811 assertions, and five expected skips. Its documentation build and
  generated-link check reported 523 links, 187 unique, 492 successful, 31 excluded, and no errors. PHPStan, formatting,
  benchmark smoke checks, and archive consumers also passed.
- A Python snapshot comparison preserved all 166 fenced blocks across the 44 Markdown sources present before cleanup.
  Public headings, example directives, literary figures, README license prose, and the original project review were
  unchanged. Eleven unique performance/cache paragraphs and both unique mutation campaign records were preserved apart
  from wrapping. The historical experiments were relocated, not rerun.
- A local Markdown check resolved 279 links in changed documents or targeting them, including 44 inbound links from
  unchanged documents. This covers contributor documents that the public book's link check does not include.
- An HTML-parser check matched all 61 public `h2`/`h3` headings to the sidebar IDs, titles, and hierarchy across nine
  chapters. Browser titles matched the mdBook Summary or explicit title override, and every chapter remained linked from
  the shared table of contents. The rendered quantity-method and point-method guidance belongs directly to Quantity
  Types; extension-specific guidance belongs to Optional Quantity Operators.
- `nix fmt --` with all 16 changed Markdown paths and `git diff --check` passed. Only this verification record changed
  after the full gate; formatting and the snapshot, local-link, and rendered-HTML checks were repeated afterward.

The Nix/PHP-version matrix, separate committed-revision compatibility check, dependency audit, external-link checks, and
browser visual review were not run for this editorial cleanup. Runtime code, configuration, dependencies, navigation
metadata, and executable examples are unchanged.
