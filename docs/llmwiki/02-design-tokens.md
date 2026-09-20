---
id: design-tokens
title: Design tokens
status: generated
updated: 2026-09-20
sources: [`src/sass/partials/_tokens.scss`]
---

# Design tokens

Every colour, font and measurement the theme uses is declared here and nowhere else. A component that needs a value it cannot find in this table is a design bug, not a licence to hard-code.

> **Generated file — do not edit by hand.**
> Regenerate with `python3 docs/llmwiki/tools/sync_from_theme.py` after changing the source it reads.


## Dark theme (default)

`:root`

| Token | Value | Group |
|---|---|---|
| `--bg` | `oklch(13% 0.008 250)` | palette |
| `--surface` | `oklch(17% 0.010 250)` | palette |
| `--fg` | `oklch(95% 0.004 100)` | palette |
| `--muted` | `oklch(68% 0.012 100)` | palette |
| `--border` | `oklch(32% 0.012 250)` | palette |
| `--border-strong` | `oklch(52% 0.012 250)` | palette |
| `--accent` | `oklch(66% 0.200 27)` | palette |
| `--on-accent` | `oklch(14% 0.010 250)` | palette |
| `--accent-soft` | `color-mix(in oklch, var(--accent) 18%, transparent)` | derived (do not override) |
| `--fg-soft` | `color-mix(in oklch, var(--fg) 7%, transparent)` | derived (do not override) |
| `--dot` | `color-mix(in oklch, var(--fg) 24%, transparent)` | derived (do not override) |
| `--scrim` | `color-mix(in oklch, var(--bg) 82%, transparent)` | derived (do not override) |
| `--font-display` | `'Times New Roman', 'Iowan Old Style', Georgia, serif` | typography |
| `--font-body` | `ui-monospace, 'JetBrains Mono', 'IBM Plex Mono', Menlo, monospace` | typography |
| `--font-mono` | `ui-monospace, 'JetBrains Mono', 'IBM Plex Mono', Menlo, monospace` | typography |
| `--fs-h1` | `clamp(46px, 7vw, 104px)` | scale |
| `--fs-h2` | `clamp(30px, 3.6vw, 46px)` | scale |
| `--fs-h3` | `20px` | scale |
| `--fs-lead` | `17px` | scale |
| `--fs-body` | `15px` | scale |
| `--fs-meta` | `12px` | scale |
| `--gap-xs` | `8px` | scale |
| `--gap-lg` | `32px` | scale |
| `--container` | `1240px` | scale |
| `--gutter` | `28px` | scale |
| `--radius` | `0px` | scale |
| `--radius-lg` | `0px` | scale |
| `--admin-bar` | `0px` | scale |
| `--z-modal` | `100000` | scale |
| `--header-height` | `92px` | scale |

## With the WordPress admin bar on the page

`body.admin-bar`

| Token | Value | Group |
|---|---|---|
| `--admin-bar` | `var(--wp-admin--admin-bar--height, 32px)` | core |

## Light theme variant

`:root[data-theme="light"]`

| Token | Value | Group |
|---|---|---|
| `--bg` | `oklch(97% 0.004 240)` | core |
| `--surface` | `oklch(100% 0 0)` | core |
| `--fg` | `oklch(15% 0.020 100)` | core |
| `--muted` | `oklch(42% 0.020 100)` | core |
| `--border` | `oklch(80% 0.010 240)` | core |
| `--border-strong` | `oklch(58% 0.012 240)` | core |
| `--accent` | `oklch(52% 0.210 27)` | core |
| `--on-accent` | `oklch(100% 0 0)` | core |
| `--dot` | `color-mix(in oklch, var(--fg) 20%, transparent)` | core |

## Rules

- The six palette slugs (`bg`, `surface`, `fg`, `muted`, `border`, `accent`) are the same six slugs declared in `theme.json`. They must stay in sync.
- Derived tokens (`--accent-soft`, `--fg-soft`, `--dot`, `--scrim`) are computed with `color-mix()` and must never be overridden per component.
- `--radius` and `--radius-lg` are `0px` by deliberate choice: the direction is brutalist. Rounded corners are a regression, not a refinement.
- No web fonts. `--font-display` and `--font-body` resolve to fonts already on the reader's machine. Adding `@font-face` breaks both the CSP and the weight budget.
- The light variant redefines token values only. It never adds or removes a token.
- A token that is built from another token must be combined **at the point of use**, not declared as a third token here: a custom property is substituted on the element where it is declared, so one written on `:root` captures `:root`'s values and ignores every override below. See `11-conventions.md`.
