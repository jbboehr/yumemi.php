/* global path_to_root */

(function () {
    "use strict";

    // mdBook only supplies headings for the active page. Keep this outline synchronized with public h2/h3 headings.
    const headingsByChapter = {
        "index.html": [
            { id: "start-here", title: "Start Here" },
            { id: "browse-documentation", title: "Browse Documentation" },
        ],
        "getting-started.html": [
            { id: "installation", title: "Installation" },
            { id: "verify-static-analysis", title: "Verify Static Analysis" },
            { id: "runtime-conversion", title: "Runtime Conversion" },
        ],
        "core-concepts.html": [
            { id: "choose-an-api", title: "Choose An API" },
            { id: "native-values-at-trusted-boundaries", title: "Native Values At Trusted Boundaries" },
            { id: "choose-an-operation", title: "Choose An Operation" },
        ],
        "recipes.html": [
            { id: "protect-an-existing-api", title: "Protect An Existing API" },
            { id: "keep-unit-setup-outside-hot-loops", title: "Keep Unit Setup Outside Hot Loops" },
            { id: "preserve-exact-conversion", title: "Preserve Exact Conversion" },
            { id: "convert-temperatures", title: "Convert Temperatures" },
            { id: "define-application-units", title: "Define Application Units" },
            { id: "format-units-for-display", title: "Format Units For Display" },
        ],
        "reference/phpstan.html": [
            {
                id: "branded-native-types",
                title: "Branded Native Types",
                children: [
                    {
                        id: "integer-constants-and-ranges",
                        title: "Integer Constants And Ranges",
                    },
                    { id: "numeric-strings", title: "Numeric Strings" },
                    {
                        id: "definitional-equivalence-and-compatibility",
                        title: "Definitional Equivalence And Compatibility",
                    },
                ],
            },
            {
                id: "native-operators",
                title: "Native Operators",
                children: [{ id: "casts-and-scalar-functions", title: "Casts And Scalar Functions" }],
            },
            {
                id: "boundary-helpers",
                title: "Boundary Helpers",
                children: [{ id: "constant-unit-expressions", title: "Constant Unit Expressions" }],
            },
            {
                id: "quantity-types",
                title: "Quantity Types",
                children: [{ id: "optional-quantity-operators", title: "Optional Quantity Operators" }],
            },
            { id: "registry-configuration", title: "Registry Configuration" },
            {
                id: "extension-optional-annotations",
                title: "Extension-Optional Annotations",
                children: [{ id: "third-party-integrations", title: "Third-Party Integrations" }],
            },
            { id: "diagnostics", title: "Diagnostics" },
            { id: "limitations", title: "Limitations" },
        ],
        "reference/unit-syntax.html": [
            { id: "supported-expressions", title: "Supported Expressions" },
            { id: "temperatures-and-offset-units", title: "Temperatures And Offset Units" },
            { id: "unit-names", title: "Unit Names" },
            { id: "parsing-resolution-and-formatting", title: "Parsing, Resolution, And Formatting" },
            { id: "unicode-syntax", title: "Unicode Syntax" },
            { id: "semantic-capabilities", title: "Semantic Capabilities" },
            { id: "resource-limits", title: "Resource Limits" },
            { id: "errors-and-source-locations", title: "Errors And Source Locations" },
        ],
        "reference/runtime.html": [
            { id: "common-tasks", title: "Common Tasks" },
            {
                id: "contexts-and-construction",
                title: "Contexts And Construction",
                children: [{ id: "exact-rational-values", title: "Exact Rational Values" }],
            },
            { id: "quantity-arithmetic", title: "Quantity Arithmetic" },
            {
                id: "conversion-and-comparison",
                title: "Conversion And Comparison",
                children: [
                    { id: "preferred-unit-profiles", title: "Preferred Unit Profiles" },
                    { id: "compact-unit-selection", title: "Compact Unit Selection" },
                ],
            },
            { id: "native-numeric-output", title: "Native Numeric Output" },
            { id: "affine-conversion", title: "Affine Conversion" },
            { id: "normalization-and-simplification", title: "Normalization And Simplification" },
            { id: "expression-operations", title: "Expression Operations" },
            { id: "dimensions", title: "Dimensions" },
            { id: "formatting", title: "Formatting" },
            { id: "string-forms", title: "String Forms" },
            { id: "debugging-json-and-serialization", title: "Debugging, JSON, And Serialization" },
        ],
        "reference/catalog.html": [
            { id: "default-catalog", title: "Default Catalog" },
            { id: "custom-registries", title: "Custom Registries" },
            { id: "introspection", title: "Introspection" },
            { id: "catalog-semantic-support", title: "Catalog Semantic Support" },
        ],
        "contributing/catalog-generation.html": [
            { id: "rebuild", title: "Rebuild" },
            { id: "source-inputs", title: "Source Inputs" },
            { id: "verify", title: "Verify" },
        ],
    };

    function createHeadingList(pageUrl, headings) {
        const list = document.createElement("ol");
        list.classList.add("section");

        for (const heading of headings) {
            const item = document.createElement("li");
            item.classList.add("header-item");

            const wrapper = document.createElement("span");
            wrapper.classList.add("chapter-link-wrapper");

            const link = document.createElement("a");
            link.href = `${pageUrl}#${heading.id}`;
            link.textContent = heading.title;

            wrapper.append(link);
            item.append(wrapper);

            if (heading.children) {
                item.classList.add("expanded");
                item.append(createHeadingList(pageUrl, heading.children));
            }

            list.append(item);
        }

        return list;
    }

    function addHeliogenesisStylesheet(assetRoot, filename) {
        const stylesheet = document.createElement("link");
        stylesheet.rel = "stylesheet";
        stylesheet.href = new URL(filename, assetRoot).href;

        const loaded = new Promise(function (resolve, reject) {
            stylesheet.addEventListener("load", resolve, { once: true });
            stylesheet.addEventListener("error", reject, { once: true });
        });

        document.head.append(stylesheet);

        return { element: stylesheet, loaded };
    }

    function markHeliogenesisShell() {
        const addedAttributes = [];
        const mark = function (element, attribute) {
            if (element.hasAttribute(attribute)) {
                return;
            }

            element.setAttribute(attribute, "");
            addedAttributes.push([element, attribute]);
        };

        const world = document.querySelector("#mdbook-page-wrapper") ?? document.body;
        mark(world, "data-heliogenesis-world");

        for (const selector of ["#mdbook-menu-bar", "#mdbook-sidebar"]) {
            const element = document.querySelector(selector);
            if (element) {
                mark(element, "data-heliogenesis-chrome");
            }
        }

        return function () {
            for (const [element, attribute] of addedAttributes) {
                element.removeAttribute(attribute);
            }
        };
    }

    async function mountHeliogenesis() {
        const controls = document.querySelector("#mdbook-menu-bar .right-buttons");
        if (!controls) {
            return;
        }

        const stylesheets = [];
        let trigger = null;
        let heliogenesis = null;
        let unmarkShell = function () {};

        try {
            const assetRoot = new URL(path_to_root + "assets/heliogenesis/", document.location.href);
            stylesheets.push(addHeliogenesisStylesheet(assetRoot, "heliogenesis.css"));
            stylesheets.push(addHeliogenesisStylesheet(assetRoot, "heliogenesis-document.css"));
            unmarkShell = markHeliogenesisShell();

            trigger = document.createElement("button");
            trigger.id = "yumemi-second-sun";
            trigger.type = "button";
            trigger.title = "Dawn the Second Sun";
            trigger.setAttribute("aria-label", "Dawn the Second Sun");
            trigger.hidden = true;
            controls.prepend(trigger);

            const moduleUrl = new URL("heliogenesis.js", assetRoot);
            const [, , module] = await Promise.all([
                stylesheets[0].loaded,
                stylesheets[1].loaded,
                import(moduleUrl.href),
            ]);
            heliogenesis = new module.Heliogenesis({ trigger });
            heliogenesis.mount();
            trigger.hidden = false;
        } catch (error) {
            try {
                heliogenesis?.destroy();
            } catch (disposalError) {
                console.error("Unable to dispose of Heliogenesis after a mount failure.", disposalError);
            }

            trigger?.remove();
            for (const stylesheet of stylesheets) {
                stylesheet.element.remove();
            }
            unmarkShell();
            console.error("Unable to mount Heliogenesis.", error);
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const chapterLinks = document.querySelectorAll("#mdbook-sidebar .chapter-item > .chapter-link-wrapper > a");

        for (const [chapterPath, headings] of Object.entries(headingsByChapter)) {
            const pageUrl = new URL(path_to_root + chapterPath, document.location.href);
            const chapterLink = Array.from(chapterLinks).find(function (link) {
                return new URL(link.href, document.location.href).href === pageUrl.href;
            });

            if (!chapterLink || chapterLink.classList.contains("active")) {
                continue;
            }

            const container = document.createElement("div");
            container.classList.add("yumemi-page-outline");
            container.append(createHeadingList(pageUrl.href, headings));
            chapterLink.parentElement.after(container);
        }

        void mountHeliogenesis();
    });
})();
