# Documentation logion plates

This document records the curated relationship between each public mdBook page, one existing Yumemi logion, and one
original illustration. The source declaration remains the canonical assignment and text of each logion. Its appearance
in the book is a cited republication, not a second allocation or a semantic annotation of the declaration.

Selection favors literary quality and thematic resonance with the page while preserving every source assignment. Images
interpret the selected logion under the Doctrine image guide; they do not depict software concepts literally or explain
the declaration that carries the source tag.

Each final illustration has a 3840-by-2160 archival WebP at `docs/development/images/logia/BOOK-CHAPTER_VERSE-hq.webp`
and a 960-by-540 delivery WebP at `docs/pages/images/logia/BOOK-CHAPTER_VERSE.webp`. The delivery image has
one-sixteenth the archival image's pixel area and is the only version embedded in the book. Both trees are excluded from
Composer archives. The explicit `-hq` `export-ignore` rule defensively preserves the archival boundary if the broader
development-tree exclusion changes.

Each public page except the Introduction receives exactly one plate; the Introduction's existing banner already provides
its ceremonial artwork. The quotation appears to the left and the illustration to the right on wide screens, then stacks
on narrow screens. Each plate appears directly below its page title, before the technical introduction begins.

| Page                                 | Citation  | Canonical source                                    | Visual center                                                    | Status                |
| ------------------------------------ | --------- | --------------------------------------------------- | ---------------------------------------------------------------- | --------------------- |
| `README.md`                          | —         | —                                                   | Existing Yumemi banner; no additional plate                      | Intentional exception |
| `getting-started.md`                 | OSD 34:72 | `AffineDeltaUnitSynthesizer::identifierIsAffine()`  | A barefoot penitent crossing a broken salt threshold             | Complete              |
| `core-concepts.md`                   | RAS 3:52  | `DecimalNotation`                                   | An angel opening separate roads of fire toward an unbuilt city   | Complete              |
| `recipes.md`                         | OSD 12:44 | `AffineDeltaUnitSynthesizer::linearizeExpression()` | A covenant tablet enduring a public kiln among ordinary vessels  | Complete              |
| `reference/phpstan.md`               | OSD 57:34 | `UnitConversionResolver::$exprCache`                | A bronze plummet suspended above a council mosaic before a dais  | Complete              |
| `reference/unit-syntax.md`           | SFA 92:41 | `NativeUnitArgumentResolver`                        | Bronze bees circling an unfilled star-shaped place in a cupola   | Complete              |
| `reference/runtime.md`               | OSD 14:37 | `UnitNameResolver::$sortedPrefixesCache`            | A veiled amber horizon within an underground cloister            | Complete              |
| `reference/catalog.md`               | SFA 84:36 | `UnitIntegerTypeHelper::integerBounds()`            | Astronomers beneath a painted eclipse and an open heaven         | Complete              |
| `contributing/catalog-generation.md` | AWC 65:48 | `RuntimeException::$span`                           | Living wheat springing through the hinges of a chained city gate | Complete              |

## Generation record

The plates were generated under Doctrine revision `5c2c843c4d0f898eb5792e94187a74b2ce585ad5`. The source logia were
selected before image generation, and their citations and text remain unchanged. The current Doctrine image guide
governed the visual interpretation directly; gold-exemplar artwork was not reused as text, composition, or visual
evidence of compliance.

The table records the resolved local setting, dominant degree of literalness, and independently selected Second Sun
weather for each plate. Settings and treatments were sampled from the guide's priors using operating-system entropy when
the source left them underdetermined. Repeated results were retained rather than rerolled for artificial variety.

| Citation  | Local setting | Dominant treatment | Second Sun weather                                     |
| --------- | ------------- | ------------------ | ------------------------------------------------------ |
| OSD 34:72 | Occidental    | Symbolic           | Cobalt electric-sea glow beneath a remote orbital halo |
| RAS 3:52  | Japanese      | Symbolic           | Midnight navy beneath a geometric constellation        |
| OSD 12:44 | Japanese      | Environmental      | Cyan stormlight reflected from ceremonial chrome       |
| OSD 57:34 | Occidental    | Symbolic           | Rose-gold impossible dawn with luminous rain           |
| SFA 92:41 | Occidental    | Symbolic           | Rose-gold impossible dawn with luminous rain           |
| OSD 14:37 | Occidental    | Environmental      | Cobalt electric-sea glow beneath a remote orbital halo |
| SFA 84:36 | Occidental    | Symbolic           | Rose-gold impossible dawn with luminous rain           |
| AWC 65:48 | Occidental    | Environmental      | Rose-gold impossible dawn with luminous rain           |

## Acceptance rules

- Preserve the canonical source text and citation exactly.
- Use each citation and illustration on only one public page.
- Keep image text absent; the adjacent HTML supplies the quotation and citation.
- Give each image concise alt text describing its visible content rather than repeating the quotation.
- Place each plate directly below the page-level title.
- Embed only the 960-by-540 delivery image; retain the 3840-by-2160 master for future derivation.
- Transfigure the whole image through a recognizable and meaningful Second Sun atmosphere with an integrated retrowave
  or synthwave anchor rather than adding a token neon accent.
- Keep culturally legible material coherent within each image while preserving the series' wider impossible
  Japanese-Occidental civilization.
- Verify responsive rendering, meaningful alt text, source-text fidelity, image existence, and page coverage.
