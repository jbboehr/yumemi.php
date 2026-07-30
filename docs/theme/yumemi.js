/* global path_to_root */

(function () {
    "use strict";

    // mdBook only supplies headings for the active page. Keep this outline synchronized with public h2/h3 headings.
    const headingsByChapter = {
        "index.html": [{ id: "start-here", title: "Start Here" }],
        "getting-started.html": [
            { id: "installation", title: "Installation" },
            { id: "static-analysis", title: "Static Analysis" },
            { id: "runtime-conversion", title: "Runtime Conversion" },
        ],
        "reference/unit-syntax.html": [
            { id: "supported-expressions", title: "Supported Expressions" },
            { id: "unit-names", title: "Unit Names" },
            { id: "parsing-resolution-and-formatting", title: "Parsing, Resolution, And Formatting" },
            { id: "unicode-syntax", title: "Unicode Syntax" },
            { id: "semantic-capabilities", title: "Semantic Capabilities" },
            { id: "errors-and-source-locations", title: "Errors And Source Locations" },
        ],
        "reference/runtime.html": [
            { id: "contexts-and-construction", title: "Contexts And Construction" },
            {
                id: "expression-operations",
                title: "Expression Operations",
                children: [{ id: "affine-conversion", title: "Affine Conversion" }],
            },
            { id: "quantity-arithmetic", title: "Quantity Arithmetic" },
            { id: "conversion-and-comparison", title: "Conversion And Comparison" },
            { id: "normalization-and-simplification", title: "Normalization And Simplification" },
            { id: "native-numeric-output", title: "Native Numeric Output" },
            { id: "dimensions", title: "Dimensions" },
            { id: "formatting", title: "Formatting" },
            { id: "string-forms", title: "String Forms" },
        ],
        "reference/catalog.html": [
            { id: "default-catalog", title: "Default Catalog" },
            { id: "introspection", title: "Introspection" },
            { id: "custom-registries", title: "Custom Registries" },
            { id: "catalog-semantic-support", title: "Catalog Semantic Support" },
            { id: "regenerating-the-catalog", title: "Regenerating The Catalog" },
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
    });
})();
