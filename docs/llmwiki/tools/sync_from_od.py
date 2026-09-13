#!/usr/bin/env python3
"""Regenerate the derived llmwiki pages from the Open Design prototype.

The prototype in OD/ is the single source of truth for the visual system and
for the client-side behaviour contract. Three wiki pages are mechanically
derived from it and must never be edited by hand:

    02-design-tokens.md   <- :root blocks in OD/assets/app.css
    03-components.md      <- section comments + selectors in OD/assets/app.css
    07-js-contract.md     <- data-* attributes in OD/assets/app.js and OD/*.html

Usage:
    python3 docs/llmwiki/tools/sync_from_od.py

Run it after every change to the prototype, and commit the regenerated pages.
"""

import re
import subprocess
from collections import Counter, OrderedDict
from datetime import date
import os
from pathlib import Path

def _find_od() -> Path:
    """Locate the prototype.

    The theme is its own repository, so OD/ is not inside it: it sits in the
    working tree that also holds the WordPress install. Walk up until we find
    it, and let an environment variable win, so this keeps working whether the
    theme is checked out inside a site or on its own.
    """
    override = os.environ.get("OD_ROOT")

    if override:
        return Path(override).resolve()

    here = Path(__file__).resolve()

    for parent in here.parents:
        candidate = parent / "OD"

        if (candidate / "assets" / "app.css").is_file():
            return candidate

    raise SystemExit(
        "OD/ not found above " + str(here.parent) + ". "
        "Set OD_ROOT to the prototype's directory."
    )


OD = _find_od()
ROOT = Path(__file__).resolve().parents[3]
WIKI = ROOT / "docs" / "llmwiki"
CSS = OD / "assets" / "app.css"
JS = OD / "assets" / "app.js"

# The prototype is written in Italian. The theme, the wiki and every identifier
# are English. This table is the binding translation for the section names that
# become SCSS partials and wiki headings; extend it when the prototype grows.
SECTION_NAMES = {
    "preamble": ("Preamble", "tokens"),
    "tema scuro (default)": ("Dark theme tokens", "tokens"),
    "reset & base": ("Reset and base", "reset"),
    "primitive di layout": ("Layout primitives", "layout"),
    "tipografia": ("Typography", "typography"),
    "barra di stato + navigazione": ("Status bar and navigation", "navbar"),
    "bottoni": ("Buttons", "buttons"),
    "card / superfici": ("Cards and surfaces", "cards"),
    "marche grafiche generative (al posto delle foto)": ("Generative marks (used instead of photographs)", "marks"),
    "pill / tag": ("Pills and tags", "tags"),
    "liste articoli": ("Post lists", "post-lists"),
    "filtri": ("Taxonomy filters", "filters"),
    "stato vuoto (archivi e ricerca senza risultati)": ("Empty states", "empty-state"),
    "widget / sidebar (wp)": ("Widgets and sidebar", "widgets"),
    "form": ("Forms", "forms"),
    "tabelle": ("Tables", "tables"),
    "prosa articolo": ("Article prose", "prose"),
    "indice laterale": ("Table of contents", "toc"),
    "ricerca (overlay)": ("Search overlay", "search"),
    "paginazione": ("Pagination", "pagination"),
    "breadcrumb": ("Breadcrumb", "breadcrumb"),
    "footer": ("Footer", "footer"),
    "utilit\u00e0": ("Utilities", "utilities"),
    "identit\u00e0: avatar generati in locale": ("Identity: locally generated avatars", "identity"),
    "etichette di ruolo": ("Role badges", "badges"),
    "righe membro": ("Member rows", "members"),
    "attivit\u00e0: chi ha commentato cosa": ("Activity feed", "activity"),
    "discussione": ("Discussion", "comments"),
    "schede": ("Tabs", "tabs"),
    "contatori": ("Counters", "counters"),
    "account nella testata": ("Account menu in the header", "account"),
    "area account: notifiche e dispositivi": ("Account area: notifications and devices", "account-area"),
    "moduli account": ("Account forms", "account-forms"),
    "finestra modale (impostazioni profilo)": ("Modal dialog", "modal"),
    "quotazioni: riga moneta con sparkline": ("Market quotes", "market"),
    "barre orizzontali dentro il testo": ("Inline bar charts", "barlist"),
}

TOKEN_GROUPS = {
    "tema scuro (default)": "palette",
    "derivati \u2014 non modificare": "derived (do not override)",
    "tipografia": "typography",
    "scala": "scale",
    "core": "core",
}


def translate_section(name: str) -> tuple[str, str]:
    """Return the English heading and the SCSS partial slug for a section."""
    key = name.strip().lower()
    if key in SECTION_NAMES:
        return SECTION_NAMES[key]
    slug = re.sub(r"[^a-z0-9]+", "-", key).strip("-")
    return name.strip(), slug


BANNER = (
    "> **Generated file — do not edit by hand.**\n"
    "> Regenerate with `python3 docs/llmwiki/tools/sync_from_od.py` "
    "after changing the prototype.\n"
)


def front_matter(page_id: str, title: str, summary: str, sources: list[str]) -> str:
    """Build the YAML front matter shared by every wiki page."""
    src = ", ".join(f"`{s}`" for s in sources)
    return (
        "---\n"
        f"id: {page_id}\n"
        f"title: {title}\n"
        "status: generated\n"
        f"updated: {date.today().isoformat()}\n"
        f"sources: [{src}]\n"
        "---\n\n"
        f"# {title}\n\n"
        f"{summary}\n\n"
        f"{BANNER}\n"
    )


def read(path: Path) -> str:
    """Read a UTF-8 source file from the prototype."""
    return path.read_text(encoding="utf-8")


def strip_accents_comment(text: str) -> str:
    """Normalise a CSS section comment into a plain heading."""
    text = text.strip().strip("/*").strip("*/").strip()
    return text.strip("─ ").strip()


# --------------------------------------------------------------------------
# 02 — design tokens
# --------------------------------------------------------------------------
def build_tokens() -> str:
    """Extract every custom property declared in a :root block."""
    css = read(CSS)
    out = [
        front_matter(
            "design-tokens",
            "Design tokens",
            "Every colour, font and measurement used by the theme is declared "
            "here and nowhere else. A component that needs a value it cannot "
            "find in this table is a design bug, not a licence to hard-code.",
            ["OD/assets/app.css"],
        )
    ]

    for match in re.finditer(r"(:root[^{]*)\{(.*?)\n\}", css, re.S):
        selector = match.group(1).strip()
        body = match.group(2)
        theme = "Dark theme (default)" if selector == ":root" else "Light theme variant"
        out.append(f"\n## {theme}\n\n`{selector}`\n\n| Token | Value | Group |\n|---|---|---|\n")
        group = "core"
        for line in body.splitlines():
            comment = re.search(r"/\*\s*(?:─+\s*)?(.*?)\s*(?:─+\s*)?\*/", line)
            if comment and "--" not in line:
                group = strip_accents_comment(comment.group(1)) or group
                continue
            decl = re.match(r"\s*(--[a-z0-9-]+)\s*:\s*([^;]+);", line)
            if decl:
                label = TOKEN_GROUPS.get(group.strip().lower(), group)
                out.append(f"| `{decl.group(1)}` | `{decl.group(2).strip()}` | {label} |\n")

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
    )
    return "".join(out)


def count_inline_styles() -> int:
    """Count the style attributes the prototype carries in its markup."""
    # The Italian tree only: the English pages are translations of the same
    # markup and counting both would double every figure.
    total = 0
    for page in OD.glob("*.html"):
        html = re.sub(r"<style.*?</style>", "", read(page), flags=re.S)
        total += len(re.findall(r'<\w+[^>]*?style="', html))
    return total


# --------------------------------------------------------------------------
# 03 — components
# --------------------------------------------------------------------------
def build_components() -> str:
    """Map every CSS section to the selectors it introduces.

    The parser walks the stylesheet tracking brace depth so that one-line rules
    and rules nested in a media query are collected as reliably as block rules.
    """
    css = read(CSS)

    sections: "OrderedDict[str, list[str]]" = OrderedDict()
    current = "preamble"
    sections[current] = []

    buffer = ""
    depth = 0
    i = 0
    while i < len(css):
        char = css[i]

        if css.startswith("/*", i):
            close = css.find("*/", i + 2)
            close = len(css) if close == -1 else close
            # A section heading may open a multi-line comment; only its first
            # line carries the name.
            comment = css[i + 2:close].splitlines()[0] if css[i + 2:close] else ""
            heading = re.match(r"\s*\u2500+\s*(.*?)\s*\u2500*\s*$", comment)
            if heading and depth == 0:
                current = heading.group(1).strip()
                sections.setdefault(current, [])
            i = close + 2
            buffer = ""
            continue

        if char == "{":
            selector = " ".join(buffer.split())
            if selector and not selector.startswith("@"):
                for part in selector.split(","):
                    part = part.strip()
                    if part and part not in sections[current]:
                        sections[current].append(part)
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

    out = [
        front_matter(
            "components",
            "Component inventory",
            "Every class the visual system defines, grouped by the section of "
            "`app.css` that owns it. A PHP template may only emit classes listed "
            "here; a new class must be added to the SCSS partial for its section "
            "first, and this page regenerated.",
            ["OD/assets/app.css"],
        )
    ]

    out.append(
        "\n## The prototype also styles in the markup\n\n"
        "`app.css` is not the whole visual system. The prototype carries "
        f"**{count_inline_styles()} inline `style` attributes** across its 15 Italian pages "
        "(the English tree repeats them): hero padding, "
        "the headline measure, stack gaps, small muted notes, ruled lists. None of it "
        "is in the stylesheet, so none of it is in the table below.\n\n"
        "Everything recurring five times or more has been promoted to a real class in "
        "`src/sass/partials/_parity.scss`, which loads last so it can refine the "
        "components above it. The long tail is genuinely per-instance. When you port a "
        "new screen, check its inline styles before assuming a class exists for what "
        "you are looking at.\n"
    )

    out.append(
        "\n## Sections and their SCSS partials\n\n"
        "One prototype section becomes one SCSS partial. Cascade order is the order "
        "of this table and must not be rearranged.\n\n"
        "| # | Section | SCSS partial | Selectors |\n|---|---|---|---|\n"
    )
    order = 0
    for name, selectors in sections.items():
        if not selectors:
            continue
        order += 1
        title, slug = translate_section(name)
        out.append(f"| {order} | {title} | `src/sass/partials/_{slug}.scss` | {len(selectors)} |\n")

    for name, selectors in sections.items():
        if not selectors:
            continue
        title, slug = translate_section(name)
        out.append(f"\n## {title}\n\n`src/sass/partials/_{slug}.scss` \u2014 {len(selectors)} selectors\n\n")
        for sel in selectors:
            out.append(f"- `{sel}`\n")

    return "".join(out)


# --------------------------------------------------------------------------
# 07 — JS contract
# --------------------------------------------------------------------------
def build_js_contract() -> str:
    """Inventory the data-* attributes that bind markup to behaviour."""
    html_files = sorted(OD.glob("*.html")) + sorted((OD / "en").glob("*.html"))
    counts: Counter = Counter()

    for path in [JS] + html_files:
        for attr in re.findall(r"data-[a-z0-9-]+", read(path)):
            counts[attr] += 1

    # data-od-id is emitted by the design tool, not by the behaviour layer.
    counts.pop("data-od-id", None)

    js = read(JS)
    blocks = [m.start() for m in re.finditer(r"^\(function \(\) \{", js, re.M)]

    out = [
        front_matter(
            "js-contract",
            "JavaScript contract",
            "Markup and behaviour are bound exclusively through `data-*` "
            "attributes. No behaviour may key off a class name, an id or a tag: "
            "classes belong to the visual system and must stay free to change. "
            "This page is the full list of hooks the prototype defines.",
            ["OD/assets/app.js", "OD/*.html"],
        )
    ]

    out.append(
        f"\nThe prototype ships {len(blocks)} IIFE blocks in a single file. "
        "The theme compiles the same behaviour from ES modules under `src/js/modules/` "
        "into one bundle. Every module must return early when its hook is absent "
        "from the page.\n"
    )

    out.append("\n## Attribute inventory\n\n| Attribute | Occurrences |\n|---|---|\n")
    for attr, n in sorted(counts.items(), key=lambda kv: (-kv[1], kv[0])):
        out.append(f"| `{attr}` | {n} |\n")

    out.append(
        "\n## Visibility is the stylesheet's business\n\n"
        "A module must toggle the state the design system defines, not invent one. "
        "The search overlay is `.overlay` / `.overlay.is-open`; the collapsed "
        "navigation is `.topnav nav.is-open`, defined inside a media query. Toggling "
        "the `.hidden` utility instead does nothing visible, because it is "
        "`display: none !important` and beats the open state — and nothing errors, so "
        "the feature simply never works.\n\n"
        "The `hidden` attribute is a separate concern and is kept in step: it takes a "
        "closed panel's links out of the tab order. Remove it before calling `focus()`, "
        "because an unrendered element cannot take focus.\n"
    )

    out.append(
        "\n## Naming\n\n"
        "The prototype names these attributes in Italian (`data-quando-dentro`, "
        "`data-carica-altri`). The theme requires English identifiers throughout. "
        "The translation table lives in `12-glossary.md` and is binding: renaming "
        "one of these after the templates are written is a breaking change on both "
        "sides at once.\n"
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
        print(f"wrote {target.relative_to(ROOT)} ({len(body)} bytes)")


if __name__ == "__main__":
    main()
