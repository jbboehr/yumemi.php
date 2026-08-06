# Ruinenwert: Designing Software for a Useful Afterlife

## Purpose

Software rarely remains actively maintained forever.

Maintainers leave. Organizations change direction. Dependencies become obsolete. Toolchains disappear. Frameworks
replace their internal APIs. A project may stop receiving releases even though its central idea remains valuable.

This document describes a design principle for that eventuality:

> Build software so that, when active maintenance ends, its useful knowledge remains understandable, testable,
> recoverable, and easy to continue elsewhere.

This is called **Ruinenwert**, or "ruin value": the value a structure retains after its original period of use has
ended.

The objective is not to create immortal software. That is generally impossible. The objective is to produce **good
ruins**.

A project with good Ruinenwert can be studied, repaired, forked, reimplemented, or used as prior art without requiring
its future maintainers to reconstruct the original authors' entire environment and thought process.

## The Central Question

For every important component, ask:

> When this piece eventually rots, what recognizable and independently testable structure will remain?

A project has strong Ruinenwert when the answer includes more than source code.

The surviving structure may include:

- a clearly stated public contract;
- an executable conformance suite;
- stable data formats or diagnostic identifiers;
- documented invariants;
- representative fixtures;
- a small semantic core;
- a reproducible generation process; and
- a documented succession and fork procedure.

A project has weak Ruinenwert when its behavior can only be inferred from a large, entangled implementation tied to a
particular version of a framework or toolchain.

## Human Understanding Comes First

Documentation for future maintainers must first be readable by humans.

It may also serve as context for language models and other automated tools, but it should not be written as an opaque
agent prompt or a collection of instructions that only make sense to a particular model.

Prefer documentation that explains:

1. what the system is trying to accomplish;
2. which behavior is intentional;
3. which constraints preserve correctness;
4. where replacement boundaries exist;
5. which parts are expected to decay first; and
6. how a successor can verify that a change remains compatible.

A language model can work effectively from clear human documentation. Humans cannot reliably work from compressed,
machine-oriented context that omits rationale.

Documentation should therefore preserve both:

- **rules**, which describe what must remain true; and
- **reasons**, which explain why those rules exist.

Rules without reasons become cargo cults. Reasons without rules become suggestions.

## Design for Unequal Rates of Decay

Not every part of a system ages at the same rate.

Framework adapters, build scripts, CI configuration, editor integrations, and tool-specific extension points often decay
quickly. Domain models, parsers, algorithms, formats, and behavioral examples usually decay more slowly.

A Ruinenwert-oriented design separates these according to their expected rate of decay.

### The Stone

The durable core should contain the project's essential meaning:

- domain concepts;
- parsers and grammars;
- normalization rules;
- algorithms;
- comparison or evaluation semantics;
- stable error categories;
- data models;
- language-independent specifications; and
- public behavioral fixtures.

### The Timber

Replaceable outer layers commonly include:

- framework service providers;
- static-analysis integration APIs;
- build-system plugins;
- command-line presentation;
- editor integrations;
- storage adapters;
- network clients;
- translation plumbing; and
- CI and release automation.

Dependencies should point inward:

```text
Frameworks and external tools
              |
              v
      Integration adapters
              |
              v
       Application logic
              |
              v
    Semantic model and rules
```

The semantic core should not need to understand the framework adapter surrounding it.

When an external API changes, a future maintainer should be able to replace the corresponding adapter without
rediscovering or rewriting the project's central semantics.

## Do Not Confuse Modularity With Package Count

Separating responsibilities does not require publishing every responsibility as a separate package.

Excessive package splitting can reduce Ruinenwert by introducing:

- additional release processes;
- cross-package version constraints;
- more repositories or package metadata;
- circular compatibility problems;
- fragmented documentation; and
- unclear ownership boundaries.

Begin with logical modules and enforced dependency direction inside one repository.

Split a module into a separate package only when it has a genuinely independent:

- consumer base;
- dependency set;
- release cadence;
- compatibility policy; or
- stewardship boundary.

A coherent repository with several replaceable modules is often easier to resurrect than a constellation of abandoned
micro-packages.

## Treat Tests as an Executable Constitution

Ordinary unit tests often describe the current implementation. A durable project also needs tests that describe the
project's identity.

Create an explicit **conformance suite** that answers:

> If the implementation were replaced, what behavior would the replacement need to preserve?

A useful test structure may look like:

```text
tests/
|-- Unit/
|-- Integration/
`-- Conformance/
```

Conformance tests should prefer public inputs and outputs over internal implementation details.

They should capture behavior such as:

- accepted and rejected inputs;
- canonical output forms;
- error categories;
- boundary behavior;
- compatibility guarantees;
- representative real-world examples;
- previously fixed regressions; and
- interactions among public features.

Names and paths in this document are illustrative. Preserve a project's conventional structure and consolidate related
material where that is clearer.

Where the public behavior can be represented faithfully as data, store cases as language-neutral fixtures:

```text
tests/Conformance/
|-- valid/
|-- invalid/
|-- expected/
`-- compatibility/
```

A future implementation should be able to consume these fixtures even if it uses different internal abstractions, or a
different programming language. Behavior that depends essentially on language semantics, framework lifecycles, object
identity, or callbacks may instead require black-box conformance tests in the implementation language. Independence from
current internals matters more than independence from every programming language.

### Stable Identifiers Over Stable Prose

Human-readable error messages may improve over time. Localization may change them completely.

When errors form part of the observable behavior, assign stable semantic identifiers:

```text
project.invalid_expression
project.unsupported_operation
project.incompatible_types
```

Tests and integrations should depend on the identifier where possible, not the exact English wording.

The identifier preserves meaning. The message explains it to the current user.

## Document Invariants Separately From Implementation

An invariant is something that must remain true regardless of how the implementation is organized.

The relevant invariants depend on the system. Examples from parsers, analyzers, exact arithmetic, and generated-data
projects may include:

- input expressions must be statically knowable;
- normalization must be deterministic;
- equivalent values must produce the same canonical representation;
- operations must not silently discard precision;
- unknown inputs must not be accepted as known-safe values;
- error paths must retain their original source location; and
- generated artifacts must be reproducible from committed sources.

These constraints are often distributed across code comments, tests, issue discussions, and the original maintainer's
memory.

Collect them in a dedicated document.

A useful `docs/invariants.md` should state:

- the invariant;
- why it matters;
- where it is currently enforced;
- what a tempting but invalid alternative might look like; and
- whether violating it is a compatibility break, correctness bug, or accepted tradeoff.

This document is particularly valuable to automated contributors. It tells them which apparent simplifications would
quietly destroy the design.

## Preserve the Specification Independently of the Implementation

The most durable artifact may not be the current source code. It may be the system's specification.

Where possible, describe the project's core semantics independently of:

- class names;
- framework terminology;
- directory structure;
- dependency injection containers;
- a specific programming language; and
- a specific build system.

This does not require writing a formal standard.

A useful specification may consist of:

- a grammar;
- a set of normalization rules;
- tables of valid and invalid cases;
- input/output examples;
- error classifications;
- algebraic laws;
- ordering or precedence rules;
- compatibility expectations; and
- conformance fixtures.

The specification should be precise enough that a future maintainer could produce a compatible implementation without
copying the existing source line by line.

## Commit Generated Artifacts and Their Sources

Generated files create two distinct preservation needs.

The committed generated output allows users to continue consuming the project even when the original generator no longer
runs.

The generator and its source data allow future maintainers to modify or reconstruct that output.

When generated artifacts are valuable independently, preserve both their sources and their output when doing so
materially improves continued use or recovery and their size, provenance, licensing, and sensitivity permit it:

```text
spec/
generator/
generated/
tests/
```

Document:

- which files are generated;
- which files are authoritative;
- how generation is invoked;
- whether generated output should be committed;
- how reproducibility is checked; and
- which tool versions are known to work.

Do not commit generated output that contains secrets, personal data, or material that cannot be redistributed. Large or
cheaply reproducible output may be better preserved through documented generation and durable source data than through
version control.

Do not require the generator merely to use the latest released artifact unless regeneration is fundamental to normal
operation.

The last valid generated output may outlive the environment that produced it.

## Keep the Project Entrance Conventional

A future maintainer will begin at the repository root.

Use familiar ecosystem landmarks wherever practical:

```text
README.md
CHANGELOG.md
CONTRIBUTING.md
SECURITY.md
LICENSE
docs/
src/
tests/
tools/
```

Project-specific ornamentation is welcome. Distinctive naming, artwork, voice, and philosophy can make a project
memorable.

The load-bearing structure, however, should remain unsurprising.

A successor should not need to discover:

- a custom task runner before running tests;
- a hidden configuration location before reading the project;
- an undocumented wrapper before invoking the standard package manager;
- a CI-only process before producing a release;
- a private service before rebuilding documentation; or
- a maintainer's shell aliases before executing checks.

Prefer obvious local commands such as:

```text
composer test
composer analyse
composer check
```

CI should call these commands rather than contain the only authoritative version of their logic.

## Separate the Public Contract From Internal Convenience

A future maintainer must be able to determine which parts of the project users are entitled to depend upon.

Document the public surface explicitly:

- public classes and functions;
- supported configuration keys;
- stable error identifiers;
- file or wire formats;
- extension points;
- environment variables;
- command-line behavior; and
- compatibility commitments.

Also state what is internal.

Visibility modifiers alone are not always sufficient. Public language visibility may be necessary for technical reasons
without implying a long-term compatibility promise.

A project may distinguish:

- stable public API;
- provisionally public API;
- integration API;
- internal API; and
- generated implementation detail.

The narrower and clearer the supported surface, the easier the project is to preserve.

## Design Explicit Replacement Boundaries

Abstraction is valuable when it marks a place where change is expected.

It is not valuable merely because an interface can be created.

Good replacement boundaries often exist around:

- framework integration;
- storage;
- transport;
- external parsers;
- rendering;
- clocks and randomness;
- tool-specific APIs;
- data acquisition; and
- generated output backends.

Avoid introducing interfaces for every class. Excessive indirection obscures the semantic structure future maintainers
need to understand.

An interface should answer a real question:

> What implementation might reasonably need to be replaced while preserving the surrounding system?

Forkability is primarily a property of source clarity, licensing, tests, and compatibility, not universal
subclassability.

Classes may remain `final` where doing so protects meaningful invariants.

## Make the Project Forkable in Practice

A permissive or copyleft license may grant the legal right to fork, but structural forkability requires more.

A practical fork should be able to:

1. build and test without private infrastructure;
2. identify the public compatibility surface;
3. replace obsolete integrations;
4. publish under a new package identity;
5. preserve existing application-level namespaces when appropriate;
6. explain its relationship to the original project; and
7. continue releases without access to the original maintainer's accounts.

Document a succession procedure.

A useful `docs/succession.md` may include:

- how releases are produced;
- which artifacts are published;
- which credentials are required;
- which services are optional;
- how package ownership may be transferred;
- how a successor package should declare compatibility;
- whether application namespaces should remain unchanged;
- how users should migrate;
- how the original package should nominate a successor; and
- what to do if the project is intentionally frozen rather than continued.

Document which accounts, permissions, and recovery or transfer procedures are required, but never record secret values
in the repository. Succession documentation should identify where credentials are managed, not contain the credentials.

Do not assume that changing the package owner requires renaming every source-level namespace. Preserving namespaces may
be essential to making a successor a practical drop-in replacement.

## Preserve Representative History, Not Every Accident

A project's history contains important knowledge, but not every implementation detail deserves preservation.

Prioritize artifacts that explain:

- why a design was chosen;
- which alternatives were rejected;
- which bugs revealed important assumptions;
- which compatibility promises users rely upon;
- which external dependencies are unusually brittle; and
- which compromises were accepted intentionally.

Architecture decision records can help:

```text
docs/decisions/
|-- 0001-use-canonical-normalization.md
|-- 0002-keep-framework-adapter-thin.md
`-- 0003-stable-error-identifiers.md
```

Each decision should be concise:

- context;
- decision;
- consequences;
- rejected alternatives; and
- conditions under which the decision should be revisited.

Do not turn the decision log into a diary. Preserve reasoning that changes how future work should be performed.

## State the Compatibility Policy Honestly

False claims of future compatibility do not improve longevity.

Avoid dependency constraints that claim support for unknown future major versions merely to reduce maintenance.

Instead, document:

- tested versions;
- expected compatibility range;
- known dependency on unstable APIs;
- what failures are likely when dependencies change;
- which adapter would need replacement; and
- which conformance tests demonstrate continued correctness.

A narrow but truthful constraint produces a visible maintenance task.

An unrealistically broad constraint produces invisible breakage in users' applications.

## Preserve Local Reproducibility

A future maintainer should be able to perform the essential work from a local checkout.

At minimum, document how to:

- install dependencies;
- run tests;
- run static analysis;
- generate committed artifacts;
- build documentation;
- create a release artifact; and
- verify package contents.

Hosted automation may assist these tasks, but it should not be their only implementation.

Where external services are unavoidable, distinguish:

- required services;
- optional conveniences;
- publication-only services; and
- historical services that are no longer necessary.

If a service disappears, the project should fail in an understandable location rather than become archaeologically
opaque.

## Prefer Data That Can Outlive Its Current Code

When a project depends on a body of knowledge -- rules, stubs, compatibility mappings, benchmark history, protocol
cases, translations, or classifications -- represent that knowledge as inspectable data where practical.

Durable data should be:

- versioned;
- documented;
- validated;
- usable independently of one internal class hierarchy;
- accompanied by representative examples;
- clear about provenance; and
- clear about licensing.

A future tool can reinterpret structured data more easily than it can extract intent from thousands of conditional
branches.

Do not force inherently behavioral logic into data merely for appearance. Preserve data as data and algorithms as
algorithms.

## Avoid False Forms of Longevity

### Abstraction Everywhere

Indirection without a replacement scenario creates sediment rather than structure.

### Excessive Package Splitting

More packages create more independent failure points, release processes, and compatibility relationships.

### Broad Dependency Ranges

Unknown future versions are not supported merely because the package manager permits their installation.

### Generated Documentation Alone

API reference documentation describes available symbols. It rarely explains architectural intent or invariants.

### CI as Institutional Memory

A green workflow is not a substitute for a documented local process.

### Clever Repository Layouts

Novel layouts impose an archaeological tax on every future contributor.

### Exact-Message Compatibility

Depending on prose rather than semantic identifiers makes localization and improvement unnecessarily dangerous.

### Comments as the Only Specification

Comments near an implementation often explain how the current implementation works, not what all valid implementations
must do.

### "The Code Is Self-Documenting"

Code can describe operations. It rarely describes rejected alternatives, compatibility promises, or the boundary between
accidental and intentional behavior.

## Recommended Project Documents

A project does not need extensive bureaucracy. A small set of deliberate artifacts provides most of the value. Their
names, paths, and boundaries should follow the host project's conventions.

| Artifact                | Purpose                                                                                     |
| ----------------------- | ------------------------------------------------------------------------------------------- |
| `README.md`             | Explain the project, its audience, basic use, maintenance status, and deeper documentation. |
| `docs/architecture.md`  | Record components, dependency direction, public surfaces, and expected replacement points.  |
| `docs/invariants.md`    | Record durable rules, their reasons, enforcement, and common invalid alternatives.          |
| `docs/compatibility.md` | State tested versions, compatibility promises, unstable integrations, and breaking changes. |
| `docs/succession.md`    | Explain release, transfer, compatible forks, successor announcements, and project freezing. |
| `docs/decisions/`       | Preserve significant architectural decisions and their rationale.                           |
| `tests/Conformance/`    | Preserve behavior that defines the project independently of its present implementation.     |

Equivalent material may be consolidated into existing documents or test structures when separate artifacts would add
more maintenance than clarity.

## Guidance for Maintainers

When adding a feature, consider:

1. Is this part of the semantic core or an integration layer?
2. What is the smallest stable public contract?
3. Which invariant does the implementation rely upon?
4. Can the behavior be expressed as a conformance case?
5. Is any new dependency allowed to leak into the core?
6. What happens when that dependency becomes obsolete?
7. Does the feature introduce knowledge that should be represented as data?
8. Would a successor know which parts are safe to replace?
9. Can the essential checks still be run locally?
10. Does the documentation explain why the design exists?

Not every feature requires new architectural machinery. The purpose of these questions is to preserve important
boundaries before they become invisible.

## Guidance for Automated Contributors

Automated tools and language models should treat the repository's human documentation as authoritative context.

Before making structural changes, inspect, in roughly this order:

1. public documentation;
2. architecture documentation;
3. invariants;
4. compatibility policy;
5. conformance tests;
6. relevant decision records;
7. implementation tests; and
8. current implementation.

Automated contributors should not infer that existing code is necessarily the intended specification. Existing code may
contain accidents, obsolete workarounds, or incomplete migrations.

When documentation, tests, and implementation disagree:

- do not silently select whichever is easiest to modify;
- identify the disagreement;
- preserve existing public behavior unless a deliberate breaking change is being made;
- prefer conformance tests and explicit invariants over incidental internal structure;
- update documentation when the project's intended contract changes; and
- avoid deleting apparently redundant code until its compatibility role is understood.

Automated contributors should optimize for human reviewability:

- keep changes conceptually narrow;
- explain altered invariants;
- add conformance cases for changed behavior;
- avoid gratuitous renaming;
- avoid introducing novel abstractions without a replacement scenario; and
- leave the project easier for the next human to understand.

The objective is not merely to produce code that passes the current test suite. It is to preserve or improve the
structure by which future maintainers can determine what correctness means.

## A Practical Review Rubric

A project with strong Ruinenwert should allow a competent successor to answer the following questions without access to
the original maintainer.

- **Meaning:** What problem does the project solve, what is its central idea, and which behavior is intentional?
- **Structure:** Where is the semantic core, which parts are tool-specific, and in which direction may dependencies
  flow?
- **Correctness:** What invariants must remain true, which tests define compatibility, and how are failures classified?
- **Operation:** How are checks run, generated artifacts rebuilt, and releases produced locally?
- **Succession:** Can a compatible successor be published, and which names, formats, and identifiers should it preserve?
- **Recovery:** If the implementation became unusable, could its core behavior be reconstructed from the remaining
  documentation, fixtures, and tests?

The last question is the ultimate Ruinenwert test.

## Minimal Adoption Plan

A project can begin applying this doctrine without a large refactor.

1. Write a one-page architecture document.
2. List the five most important invariants.
3. Identify the fastest-decaying external integration.
4. Add several public black-box conformance cases.
5. Create standard local check commands.
6. Document the release process.
7. Document how a compatible fork would be published.
8. Preserve the source and output of important generators.
9. Record future architectural decisions as they occur.
10. Review new work by asking what will remain when its dependencies rot.

The purpose is not to anticipate every future environment.

It is to leave enough structure that future maintainers do not have to begin from myth.

## Final Principle

Ruinenwert does not mean resisting all change.

It means arranging change so that the project's accumulated knowledge is not destroyed when one implementation,
dependency, maintainer, or institution disappears.

> Do not attempt to build immortal software. Build software whose death does not destroy its knowledge.
