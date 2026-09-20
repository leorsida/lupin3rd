#!/usr/bin/env python3
"""Regenerate the derived llmwiki pages from the theme itself.

Three wiki pages are mechanically derived and must never be edited by hand:

    02-design-tokens.md   <- :root blocks in src/sass/partials/_tokens.scss
    03-components.md      <- selectors in src/sass/partials/*.scss, in @use order
    07-js-contract.md     <- data-* attributes in src/js/ and the PHP templates

Usage:
    python3 docs/llmwiki/tools/sync_from_theme.py

Until 20.09.2026 these pages were generated from the Open Design prototype by
sync_from_od.py. They described what had been designed; this script describes
what the theme does. The difference is the point: a generated page is only
worth generating when it is derived from the thing it claims to document, and
a second source that has to be kept in step is not a source of truth, it is a
second thing to get wrong. See D-035.
"""

import re
from collections import Counter, OrderedDict
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
WIKI = ROOT / "docs" / "llmwiki"
SASS = ROOT / "src" / "sass"
PARTIALS = SASS / "partials"
ENTRY = SASS / "lupin3rd.scss"
JS = ROOT / "src" / "js"

# One partial, one heading. The slug is the file name, so this table only
# supplies the English prose title; a partial missing from it is titled after
# its own slug, which is a reminder to name it here rather than an error.
PARTIAL_TITLES = {
    "tokens": "Design tokens",
    "reset": "Reset and base",
    "layout": "Layout primitives",
    "typography": "Typography",
    "navbar": "Status bar and navigation",
    "buttons": "Buttons",
    "cards": "Cards and surfaces",
    "marks": "Generative marks (used instead of photographs)",
    "tags": "Pills and tags",
    "post-lists": "Post lists",
    "filters": "Taxonomy filters",
    "empty-state": "Empty states",
    "widgets": "Widgets and sidebar",
    "forms": "Forms",
    "tables": "Tables",
    "prose": "Article prose",
    "toc": "Table of contents",
    "search": "Search overlay",
    "pagination": "Pagination",
    "breadcrumb": "Breadcrumb",
    "footer": "Footer",
    "utilities": "Utilities",
    "identity": "Identity: locally generated avatars",
    "badges": "Role badges",
    "members": "Member rows",
    "activity": "Activity feed",
    "comments": "Discussion",
    "tabs": "Tabs",
    "counters": "Counters",
    "account": "Account menu in the header",
    "account-area": "Account area: notifications and devices",
    "account-forms": "Account forms",
    "modal": "Modal dialog",
    "market": "Market quotes",
    "barlist": "Inline bar charts",
    "parity": "Parity refinements (loads last)",
    "cookieconsent": "Cookie notice (vendored)",
}

TOKEN_GROUPS = {
    "tema scuro (default)": "palette",
    "derivati — non modificare": "derived (do not override)",
    "tipografia": "typography",
    "scala": "scale",
}

BANNER = (
    "> **Generated file — do not edit by hand.**\n"
    "> Regenerate with `python3 docs/llmwiki/tools/sync_from_theme.py` "
    "after changing the source it reads.\n"
)


def front_matter(page_id: str, title: str, summary: str, sources: list) -> str:
    """Build the YAML front matter shared by every wiki page."""
    src = ", ".join("`" + s + "`" for s in sources)
    return (
        "---\n"
        "id: " + page_id + "\n"
        "title: " + title + "\n"
        "status: generated\n"
        "updated: " + date.today().isoformat() + "\n"
        "sources: [" + src + "]\n"
        "---\n\n"
        "# " + title + "\n\n"
        + summary + "\n\n"
        + BANNER + "\n"
    )


def read(path: Path) -> str:
    """Read a UTF-8 source file from the theme."""
    return path.read_text(encoding="utf-8")


def use_order() -> list:
    """The partials in cascade order, read from the single entry point.

    The entry file is the authority here, not the directory listing: a partial
    that is never `@use`d is dead code, and one loaded late can win against the
    ones above it. The order in this list is the order in the stylesheet.
    """
    order = []
    for match in re.finditer(r"@use\s+'(?:partials|vendor)/([a-z0-9-]+)'", read(ENTRY)):
        order.append(match.group(1))
    return order


def selectors_in(path: Path) -> list:
    """Every selector a partial introduces, in source order.

    Walks brace depth so that a rule nested in a media query is collected like
    any other, and skips at-rules, which are not selectors.
    """
    css = read(path)
    found = []
    buffer = ""
    depth = 0
    i = 0

    while i < len(css):
        if css.startswith("/*", i):
            close = css.find("*/", i + 2)
            i = (len(css) if close == -1 else close) + 2
            buffer = ""
            continue

        char = css[i]

        if char == "{":
            selector = " ".join(buffer.split())
            if selector and not selector.startswith("@"):
                for part in selector.split(","):
                    part = part.strip()
                    if part and part not in found:
                        found.append(part)
            depth += 1
            buffer = ""
        elif char == "}":
            depth = max(0, depth - 1)
            buffer = ""
        elif char == ";" and depth == 0:
            buffer = ""
        else:
            buffer += char
        i += 1

    return found


# --------------------------------------------------------------------------
# 02 — design tokens
# --------------------------------------------------------------------------
def build_tokens() -> str:
    """Extract every custom property declared in a :root block."""
    css = read(PARTIALS / "_tokens.scss")
    out = [
        front_matter(
            "design-tokens",
            "Design tokens",
            "Every colour, font and measurement the theme uses is declared here "
            "and nowhere else. A component that needs a value it cannot find in "
            "this table is a design bug, not a licence to hard-code.",
            ["src/sass/partials/_tokens.scss"],
        )
    ]

    for match in re.finditer(r"(:root[^{]*|body\.admin-bar)\s*\{(.*?)\n\}", css, re.S):
        selector = match.group(1).strip()
        body = match.group(2)
        theme = {
            ":root": "Dark theme (default)",
            ':root[data-theme="light"]': "Light theme variant",
            "body.admin-bar": "With the WordPress admin bar on the page",
        }.get(selector, selector)
        out.append(
            "\n## " + theme + "\n\n`" + selector + "`\n\n"
            "| Token | Value | Group |\n|---|---|---|\n"
        )
        group = "core"
        for line in body.splitlines():
            comment = re.search(r"/\*\s*(?:─+\s*)?(.*?)\s*(?:─+\s*)?\*/", line)
            if comment and "--" not in line:
                group = comment.group(1).strip().strip("─ ") or group
                continue
            decl = re.match(r"\s*(--[a-z0-9-]+)\s*:\s*([^;]+);", line)
            if decl:
                label = TOKEN_GROUPS.get(group.strip().lower(), group)
                out.append(
                    "| `" + decl.group(1) + "` | `" + decl.group(2).strip()
                    + "` | " + label + " |\n"
                )

    out.append(
        "\n## Rules\n\n"
        "- The six palette slugs (`bg`, `surface`, `fg`, `muted`, `border`, `accent`) "
        "are the same six slugs declared in `theme.json`. They must stay in sync.\n"
        "- Derived tokens (`--accent-soft`, `--fg-soft`, `--dot`, `--scrim`) are computed "
        "with `color-mix()` and must never be overridden per component.\n"
        "- `--radius` and `--radius-lg` are `0px` by deliberate choice: the direction is "
        "brutalist. Rounded corners are a regression, not a refinement.\n"
        "- No web fonts. `--font-display` and `--font-body` resolve to fonts already on "
        "the reader's machine. Adding `@font-face` breaks both the CSP and the weight budget.\n"
        "- The light variant redefines token values only. It never adds or removes a token.\n"
        "- A token that is built from another token must be combined **at the point of "
        "use**, not declared as a third token here: a custom property is substituted on "
        "the element where it is declared, so one written on `:root` captures `:root`'s "
        "values and ignores every override below. See `11-conventions.md`.\n"
    )
    return "".join(out)


# --------------------------------------------------------------------------
# 03 — components
# --------------------------------------------------------------------------
def count_inline_styles() -> int:
    """Count the style attributes the theme's own templates still carry."""
    total = 0
    for pattern in ("*.php", "template-parts/**/*.php", "page-templates/*.php", "blocks/**/*.php"):
        for page in ROOT.glob(pattern):
            total += len(re.findall(r'<\w+[^>]*?style="', read(page)))
    return total


def build_components() -> str:
    """Map every SCSS partial to the selectors it introduces."""
    sections: "OrderedDict[str, list]" = OrderedDict()
    vendored = set()

    for slug in use_order():
        path = PARTIALS / ("_" + slug + ".scss")
        if not path.is_file():
            path = SASS / "vendor" / ("_" + slug + ".scss")
            vendored.add(slug)
        if path.is_file():
            sections[slug] = selectors_in(path)

    out = [
        front_matter(
            "components",
            "Component inventory",
            "Every class the visual system defines, grouped by the SCSS partial "
            "that owns it. A PHP template may only emit classes listed here; a "
            "new class goes into a partial first, and this page is regenerated.",
            ["src/sass/lupin3rd.scss", "src/sass/partials/*.scss"],
        )
    ]

    inline = count_inline_styles()
    verdict = (
        "**none at all**, which is the number to keep"
        if inline == 0
        else "**" + str(inline) + "**, and that number should fall, never rise"
    )
    out.append(
        "\n## Styling in the markup\n\n"
        "Inline `style` attributes in the theme's templates: " + verdict + ". "
        "The prototype this theme was ported from carried several hundred — hero "
        "padding, headline measures, stack gaps, muted notes — and everything "
        "recurring five times or more became a real class in "
        "`src/sass/partials/_parity.scss`, which loads last so it can refine the "
        "components above it. The rest was rewritten rather than carried over. "
        "Each new one is a small argument that a class is missing.\n"
    )

    out.append(
        "\n## Partials, in cascade order\n\n"
        "The order is the order of `@use` in `src/sass/lupin3rd.scss`, and it is not "
        "alphabetical by accident: a partial can only refine the ones above it. "
        "Rearranging this table means rearranging the stylesheet.\n\n"
        "| # | Partial | Section | Selectors |\n|---|---|---|---|\n"
    )
    for order, (slug, selectors) in enumerate(sections.items(), start=1):
        title = PARTIAL_TITLES.get(slug, slug)
        out.append(
            "| " + str(order) + " | `_" + slug + ".scss` | " + title + " | "
            + str(len(selectors)) + " |\n"
        )

    for slug, selectors in sections.items():
        if not selectors:
            continue
        title = PARTIAL_TITLES.get(slug, slug)

        if slug in vendored:
            # Somebody else's stylesheet. It is in the cascade, so it belongs in
            # the table above — but listing its selectors one by one would bury
            # this theme's own classes under a library's, and no template may
            # emit them anyway.
            out.append(
                "\n## " + title + "\n\n`src/sass/vendor/_" + slug + ".scss` — "
                + str(len(selectors)) + " selectors, not listed.\n\n"
                "Vendored, and scoped to its own root, so nothing here is a class a "
                "template may emit. Read the file when you need one.\n"
            )
            continue

        out.append(
            "\n## " + title + "\n\n`src/sass/partials/_" + slug + ".scss` — "
            + str(len(selectors)) + " selectors\n\n"
        )
        for sel in selectors:
            out.append("- `" + sel + "`\n")

    return "".join(out)


# --------------------------------------------------------------------------
# 07 — JS contract
# --------------------------------------------------------------------------
def build_js_contract() -> str:
    """Inventory the data-* attributes that bind markup to behaviour."""
    templates = (
        sorted(ROOT.glob("*.php"))
        + sorted(ROOT.glob("template-parts/**/*.php"))
        + sorted(ROOT.glob("page-templates/*.php"))
        + sorted(ROOT.glob("blocks/**/*.php"))
        + sorted(ROOT.glob("inc/*.php"))
    )
    modules = sorted((JS / "modules").glob("*.js"))

    emitted: Counter = Counter()
    for path in templates:
        for attr in re.findall(r"data-[a-z0-9-]+", read(path)):
            emitted[attr] += 1

    consumed: Counter = Counter()
    for path in modules:
        for attr in re.findall(r"data-[a-z0-9-]+", read(path)):
            consumed[attr] += 1

    out = [
        front_matter(
            "js-contract",
            "JavaScript contract",
            "Markup and behaviour are bound exclusively through `data-*` "
            "attributes. No behaviour may key off a class name, an id or a tag: "
            "classes belong to the visual system and must stay free to change.",
            ["src/js/modules/*.js", "*.php", "template-parts/**/*.php"],
        )
    ]

    out.append(
        "\nThe bundle is built from " + str(len(modules)) + " ES modules under "
        "`src/js/modules/`, compiled into one file. Every module must return early "
        "when its hook is absent from the page.\n"
    )

    out.append(
        "\n## The two columns are the contract\n\n"
        "**Emitted** counts the attribute in the PHP that prints it. **Read** counts "
        "it in the modules that act on it. Both numbers matter, and so does each "
        "being zero:\n\n"
        "- read but never emitted — the module is waiting for markup nobody prints;\n"
        "- emitted but never read — either a CSS or server-side hook, which is fine, "
        "or a behaviour that was removed and left its handle behind.\n\n"
        "**Read `0` is not proof that nothing reads it.** This table counts literal "
        "occurrences, and a module that builds the name at runtime — "
        "`getAttribute('data-level-' + n)`, or `dataset.copiedLabel` for "
        "`data-copied-label` — never spells it out. Check the module before deleting "
        "an attribute on the strength of a zero.\n\n"
        "| Attribute | Emitted | Read |\n|---|---|---|\n"
    )

    for attr in sorted(set(emitted) | set(consumed), key=lambda a: (-(emitted[a] + consumed[a]), a)):
        out.append(
            "| `" + attr + "` | " + str(emitted[attr]) + " | " + str(consumed[attr]) + " |\n"
        )

    out.append(
        "\n## Modules\n\n| Module | Attributes it reads |\n|---|---|\n"
    )
    for path in modules:
        attrs = sorted(set(re.findall(r"data-[a-z0-9-]+", read(path))))
        out.append(
            "| `" + path.name + "` | "
            + (", ".join("`" + a + "`" for a in attrs) if attrs else "—")
            + " |\n"
        )

    out.append(
        "\n## Visibility is the stylesheet's business\n\n"
        "A module must toggle the state the design system defines, not invent one. "
        "The search overlay is `.overlay` / `.overlay.is-open`; the drawer is "
        "`.topnav nav.is-drawer.is-open`, defined inside a media query. Toggling the "
        "`.hidden` utility instead does nothing visible, because it is "
        "`display: none !important` and beats the open state — and nothing errors, so "
        "the feature simply never works.\n\n"
        "The `hidden` attribute is a separate concern and is kept in step: it takes a "
        "closed panel's links out of the tab order. Remove it before calling `focus()`, "
        "because an unrendered element cannot take focus.\n\n"
        "Which of the two an element ships with is not a style choice. A **control** "
        "that needs a script ships `hidden` and the module reveals it, so it is never "
        "an inert button. A **panel** ships visible, so a reader without the bundle "
        "still reaches its contents.\n"
    )

    out.append(
        "\n## Naming\n\n"
        "Every attribute here is English, like every other identifier in this theme. "
        "Public slugs and URLs stay Italian, because they are content. The prototype "
        "this theme was ported from named these attributes in Italian; the translation "
        "table in `12-glossary.md` recorded the mapping and is now history rather than "
        "a live contract.\n"
    )
    return "".join(out)


def main() -> None:
    """Write every derived page and report what changed."""
    WIKI.mkdir(parents=True, exist_ok=True)
    pages = {
        "02-design-tokens.md": build_tokens(),
        "03-components.md": build_components(),
        "07-js-contract.md": build_js_contract(),
    }
    for name, body in pages.items():
        target = WIKI / name
        target.write_text(body, encoding="utf-8")
        print("wrote " + str(target.relative_to(ROOT)) + " (" + str(len(body)) + " bytes)")


if __name__ == "__main__":
    main()
