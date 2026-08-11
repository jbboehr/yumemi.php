# Heliogenesis Integration

Yumemi's mdBook mounts the optional Heliogenesis browser integration from Doctrine of the Second Sun. The toolbar
control starts a temporary **Dawning of the Second Sun** event; ordinary documentation behavior remains the default.

The upstream runtime is copied verbatim into `docs/pages/assets/heliogenesis/`. Committing those assets keeps mdBook,
GitHub Pages, and the Nix documentation derivation independent of a populated `vendor/` directory. The copied notice
records the Doctrine revision and preserves both the Doctrine and Three.js license texts. Yumemi-specific mounting and
shell selection remain in `docs/theme/yumemi.js` and `docs/theme/yumemi.css` rather than modifying the upstream runtime.
The integration lights the page wrapper and navigation chrome but deliberately leaves the article unmarked, preserving
the reader's selected mdBook content colors and omitting Heliogenesis's optional document-tomography treatment. The
solid article plane and its surrounding gutters are an intentional permanent part of the Yumemi theme: they preserve a
readable line length and reliable contrast before, during, and after the event while allowing environmental light to
remain visible around the content.

The integration preserves the upstream lifecycle boundaries:

- mounting does not allocate a WebGL context;
- hover, keyboard focus, or activation prepares the renderer;
- reduced-motion users receive the upstream static treatment;
- stylesheet, module, or controller mount failure removes the injected assets, shell hooks, and hidden trigger;
- renderer initialization failure disables the optional effect without disabling the documentation;
- the controller restores the normal document state after the event.

`HeliogenesisAssetsTest` verifies that the public runtime remains byte-for-byte identical to the Composer-installed copy
and that the provenance notice names the locked Doctrine revision. When the Doctrine pin advances, review upstream
Heliogenesis changes before copying the runtime again; do not modify a copied file to hide drift. If Yumemi later
replaces Heliogenesis, remove the public runtime, toolbar mount, shell-lighting hooks, provenance notice, and drift test
together.

The runtime is a documentation-build asset, not part of Yumemi's PHP package contract. `.gitattributes` and
`composer.json` exclude the copied directory from release archives, while `flake.nix` excludes the upstream files from
repository formatting.
