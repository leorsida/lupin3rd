---
id: components
title: Component inventory
status: generated
updated: 2026-09-13
sources: [`OD/assets/app.css`]
---

# Component inventory

Every class the visual system defines, grouped by the section of `app.css` that owns it. A PHP template may only emit classes listed here; a new class must be added to the SCSS partial for its section first, and this page regenerated.

> **Generated file — do not edit by hand.**
> Regenerate with `python3 docs/llmwiki/tools/sync_from_od.py` after changing the prototype.


## The prototype also styles in the markup

`app.css` is not the whole visual system. The prototype carries **618 inline `style` attributes** across its 15 Italian pages (the English tree repeats them): hero padding, the headline measure, stack gaps, small muted notes, ruled lists. None of it is in the stylesheet, so none of it is in the table below.

Everything recurring five times or more has been promoted to a real class in `src/sass/partials/_parity.scss`, which loads last so it can refine the components above it. The long tail is genuinely per-instance. When you port a new screen, check its inline styles before assuming a class exists for what you are looking at.

## Sections and their SCSS partials

One prototype section becomes one SCSS partial. Cascade order is the order of this table and must not be rearranged.

| # | Section | SCSS partial | Selectors |
|---|---|---|---|
| 1 | Preamble | `src/sass/partials/_tokens.scss` | 2 |
| 2 | Reset and base | `src/sass/partials/_reset.scss` | 19 |
| 3 | Layout primitives | `src/sass/partials/_layout.scss` | 13 |
| 4 | Typography | `src/sass/partials/_typography.scss` | 13 |
| 5 | Status bar and navigation | `src/sass/partials/_navbar.scss` | 37 |
| 6 | Buttons | `src/sass/partials/_buttons.scss` | 12 |
| 7 | Cards and surfaces | `src/sass/partials/_cards.scss` | 3 |
| 8 | Generative marks (used instead of photographs) | `src/sass/partials/_marks.scss` | 10 |
| 9 | Pills and tags | `src/sass/partials/_tags.scss` | 7 |
| 10 | Post lists | `src/sass/partials/_post-lists.scss` | 11 |
| 11 | Taxonomy filters | `src/sass/partials/_filters.scss` | 7 |
| 12 | Empty states | `src/sass/partials/_empty-state.scss` | 5 |
| 13 | Widgets and sidebar | `src/sass/partials/_widgets.scss` | 8 |
| 14 | Forms | `src/sass/partials/_forms.scss` | 11 |
| 15 | Tables | `src/sass/partials/_tables.scss` | 8 |
| 16 | Article prose | `src/sass/partials/_prose.scss` | 26 |
| 17 | Table of contents | `src/sass/partials/_toc.scss` | 8 |
| 18 | Search overlay | `src/sass/partials/_search.scss` | 10 |
| 19 | Pagination | `src/sass/partials/_pagination.scss` | 5 |
| 20 | Breadcrumb | `src/sass/partials/_breadcrumb.scss` | 3 |
| 21 | Footer | `src/sass/partials/_footer.scss` | 7 |
| 22 | Utilities | `src/sass/partials/_utilities.scss` | 6 |
| 23 | Identity: locally generated avatars | `src/sass/partials/_identity.scss` | 15 |
| 24 | Role badges | `src/sass/partials/_badges.scss` | 4 |
| 25 | Member rows | `src/sass/partials/_members.scss` | 9 |
| 26 | Activity feed | `src/sass/partials/_activity.scss` | 7 |
| 27 | Discussion | `src/sass/partials/_comments.scss` | 10 |
| 28 | Tabs | `src/sass/partials/_tabs.scss` | 7 |
| 29 | Counters | `src/sass/partials/_counters.scss` | 4 |
| 30 | Account menu in the header | `src/sass/partials/_account.scss` | 13 |
| 31 | Account area: notifications and devices | `src/sass/partials/_account-area.scss` | 7 |
| 32 | Account forms | `src/sass/partials/_account-forms.scss` | 11 |
| 33 | Modal dialog | `src/sass/partials/_modal.scss` | 6 |
| 34 | Market quotes | `src/sass/partials/_market.scss` | 13 |
| 35 | Inline bar charts | `src/sass/partials/_barlist.scss` | 10 |

## Preamble

`src/sass/partials/_tokens.scss` — 2 selectors

- `:root`
- `:root[data-theme="light"]`

## Reset and base

`src/sass/partials/_reset.scss` — 19 selectors

- `*`
- `*::before`
- `*::after`
- `html`
- `body`
- `img`
- `svg`
- `a`
- `button`
- `p`
- `h1`
- `h2`
- `h3`
- `h4`
- `::selection`
- `:focus-visible`
- `.skip-link`
- `.skip-link:focus`
- `.sr-only`

## Layout primitives

`src/sass/partials/_layout.scss` — 13 selectors

- `.container`
- `.section`
- `.section + .section`
- `.stack`
- `.row`
- `.row-between`
- `.grid-2`
- `.grid-3`
- `.grid-4`
- `.grid-2-1`
- `.grid-1-2`
- `.grid-main-side`
- `.split-feature`

## Typography

`src/sass/partials/_typography.scss` — 13 selectors

- `.h1`
- `h1`
- `.h2`
- `h2`
- `.h3`
- `h3`
- `.lead`
- `.eyebrow`
- `.kicker`
- `.meta`
- `.num`
- `.rule`
- `.rule-strong`

## Status bar and navigation

`src/sass/partials/_navbar.scss` — 37 selectors

- `.statusbar`
- `.statusbar .container`
- `.statusbar .ticker`
- `.statusbar b`
- `.caret`
- `50%`
- `.topnav`
- `.topnav-inner`
- `.logo`
- `.logo .dot`
- `.logo .motto`
- `.topnav nav`
- `.topnav nav a`
- `.topnav nav a:hover`
- `.topnav nav a[aria-current="page"]`
- `.nav-tools`
- `.icon-btn`
- `.icon-btn:hover`
- `.icon-btn svg`
- `.icon-btn .icon-sun`
- `:root[data-theme="light"] .icon-btn .icon-sun`
- `:root[data-theme="light"] .icon-btn .icon-moon`
- `.nav-burger`
- `.langswitch`
- `.langswitch > *`
- `.langswitch > a:hover`
- `.langswitch .is-on`
- `.nav-lang`
- `.nav-account`
- `.nav-tools > .langswitch`
- `.nav-tools > .account`
- `.topnav nav.is-open .nav-account`
- `.topnav nav.is-open .nav-lang`
- `.topnav nav.is-open .nav-lang a`
- `mark`
- `.topnav nav.is-open`
- `.topnav nav.is-open a`

## Buttons

`src/sass/partials/_buttons.scss` — 12 selectors

- `.btn`
- `.btn:active`
- `.btn-primary`
- `.btn-primary:hover`
- `.btn-secondary`
- `.btn-secondary:hover`
- `.btn-ghost`
- `.btn-ghost:hover`
- `.btn-arrow::after`
- `.btn-arrow:hover::after`
- `.btn-sm`
- `.btn-nav`

## Cards and surfaces

`src/sass/partials/_cards.scss` — 3 selectors

- `.card`
- `.card-flat`
- `.card-rule`

## Generative marks (used instead of photographs)

`src/sass/partials/_marks.scss` — 10 selectors

- `.mark`
- `.mark.wide`
- `.mark.square`
- `.mark.tall`
- `.mark-glyph`
- `.mark::after`
- `.mark--scan::after`
- `.mark--bars::after`
- `.mark--grid::after`
- `.mark-tag`

## Pills and tags

`src/sass/partials/_tags.scss` — 7 selectors

- `.pill`
- `.tag`
- `a.tag:hover`
- `.tag-strong`
- `.diff`
- `.diff i`
- `.diff i.on`

## Post lists

`src/sass/partials/_post-lists.scss` — 11 selectors

- `.log-row`
- `.log-row:hover h3`
- `.log-row .pull`
- `.post`
- `.post .mark`
- `.post h3`
- `.post:hover h3`
- `.post:hover h2`
- `.post p`
- `.post-meta`
- `.post.split-feature`

## Taxonomy filters

`src/sass/partials/_filters.scss` — 7 selectors

- `.filterbar`
- `.chip`
- `.chip:hover`
- `.chip[aria-pressed="true"]`
- `.chip.is-active`
- `.chip[aria-pressed="true"]:hover`
- `.chip.is-active:hover`

## Empty states

`src/sass/partials/_empty-state.scss` — 5 selectors

- `.emptystate`
- `.emptystate-mark`
- `.emptystate h3`
- `.emptystate p`
- `.emptystate .row`

## Widgets and sidebar

`src/sass/partials/_widgets.scss` — 8 selectors

- `.widget`
- `.widget + .widget`
- `.widget-title`
- `.widget ul`
- `.widget li`
- `.widget li a`
- `.widget li a:hover`
- `.widget li a span`

## Forms

`src/sass/partials/_forms.scss` — 11 selectors

- `.field`
- `.field label`
- `.input`
- `.textarea`
- `select.input`
- `.input::placeholder`
- `.textarea::placeholder`
- `.input:focus`
- `.textarea:focus`
- `.field-error`
- `.input[aria-invalid="true"]`

## Tables

`src/sass/partials/_tables.scss` — 8 selectors

- `.ds-table`
- `.ds-table th`
- `.ds-table td`
- `.ds-table tbody tr:hover`
- `.ds-table .num-col`
- `.ds-table code`
- `.code-inline`
- `.table-wrap`

## Article prose

`src/sass/partials/_prose.scss` — 26 selectors

- `.prose`
- `.prose > * + *`
- `.prose h2`
- `.prose h3`
- `.prose p`
- `.prose a`
- `.prose a:hover`
- `.prose ul`
- `.prose ol`
- `.prose li + li`
- `.prose figcaption`
- `.prose blockquote`
- `.callout`
- `.callout .callout-title`
- `.callout p`
- `.callout svg`
- `.callout--legal`
- `.codeblock`
- `.codeblock-head`
- `.codeblock pre`
- `.codeblock code`
- `.tok-com`
- `.tok-str`
- `.tok-kw`
- `.copy-btn`
- `.copy-btn:hover`

## Table of contents

`src/sass/partials/_toc.scss` — 8 selectors

- `.toc`
- `.toc ol`
- `.toc li`
- `.toc a`
- `.toc a::before`
- `.toc a:hover`
- `.toc a.is-active`
- `.progress`

## Search overlay

`src/sass/partials/_search.scss` — 10 selectors

- `.overlay`
- `.overlay.is-open`
- `.overlay-panel`
- `.overlay-panel .input`
- `.overlay-results`
- `.overlay-results li a`
- `.overlay-results li a:hover`
- `.overlay-results li a:focus-visible`
- `.overlay-results .r-title`
- `.overlay-empty`

## Pagination

`src/sass/partials/_pagination.scss` — 5 selectors

- `.pagination`
- `.pagination a`
- `.pagination span`
- `.pagination a:hover`
- `.pagination .is-current`

## Breadcrumb

`src/sass/partials/_breadcrumb.scss` — 3 selectors

- `.breadcrumb`
- `.breadcrumb a:hover`
- `.breadcrumb span[aria-hidden]`

## Footer

`src/sass/partials/_footer.scss` — 7 selectors

- `.pagefoot`
- `.pagefoot .foot-grid`
- `.pagefoot h4`
- `.pagefoot ul`
- `.pagefoot a`
- `.pagefoot a:hover`
- `.foot-bottom`

## Utilities

`src/sass/partials/_utilities.scss` — 6 selectors

- `.hidden`
- `.mw-36`
- `.bigcode`
- `.person`
- `.person .mark`
- `.mw-46`

## Identity: locally generated avatars

`src/sass/partials/_identity.scss` — 15 selectors

- `.avatar`
- `.avatar::before`
- `.avatar b`
- `.av--scan::before`
- `.av--bars::before`
- `.av--grid::before`
- `.av--dots::before`
- `.av--diag::before`
- `.avatar-sm`
- `.avatar-lg`
- `.avatar-self`
- `.avatar-self::before`
- `.avatar-stack`
- `.avatar-stack .avatar`
- `.avatar-stack .avatar:last-child`

## Role badges

`src/sass/partials/_badges.scss` — 4 selectors

- `.badge`
- `.badge-strong`
- `.badge-dashed`
- `.badge svg`

## Member rows

`src/sass/partials/_members.scss` — 9 selectors

- `.member`
- `.member:first-child`
- `.member-id`
- `.member-name`
- `a.member:hover`
- `a.member:hover .member-name`
- `a.member`
- `.member-note`
- `.member-pull`

## Activity feed

`src/sass/partials/_activity.scss` — 7 selectors

- `.activity`
- `.activity:first-child`
- `.activity-head`
- `.activity-quote`
- `.activity-on`
- `.activity-on a`
- `.activity-on a:hover`

## Discussion

`src/sass/partials/_comments.scss` — 10 selectors

- `.comment`
- `.comment-head`
- `.comment-name`
- `.comment-name:hover`
- `.comment-body`
- `.comment-reply-to`
- `.comment-tools`
- `.comment-tool`
- `.comment-tool:hover`
- `.comment-nested`

## Tabs

`src/sass/partials/_tabs.scss` — 7 selectors

- `.tabs`
- `.tab`
- `.tab:hover`
- `.tab[aria-selected="true"]`
- `.tab[aria-selected="true"]:hover`
- `.tab .num`
- `.tabpanel[hidden]`

## Counters

`src/sass/partials/_counters.scss` — 4 selectors

- `.statgrid`
- `.statgrid .stat`
- `.stat .stat-num`
- `.stat .stat-lab`

## Account menu in the header

`src/sass/partials/_account.scss` — 13 selectors

- `.account`
- `.account-chip`
- `.account-chip:hover`
- `.account-chip[aria-expanded="true"]`
- `.account-chip .avatar`
- `.account-menu`
- `.account-menu .account-menu-head`
- `.account-menu a`
- `.account-menu button`
- `.account-menu a:hover`
- `.account-menu button:hover`
- `.account-menu a span`
- `.account-menu button span`

## Account area: notifications and devices

`src/sass/partials/_account-area.scss` — 7 selectors

- `.log-row[data-non-letta] .pull`
- `.log-row[data-non-letta] .pull::before`
- `.sessione-riga`
- `.sessione-riga:last-of-type`
- `.sessione-riga p`
- `.sessione-riga .meta`
- `.btn:disabled`

## Account forms

`src/sass/partials/_account-forms.scss` — 11 selectors

- `.check`
- `.check input[type="checkbox"]`
- `.check a`
- `.hint`
- `.meter`
- `.meter i`
- `.meter i.on`
- `.formcard`
- `.gate`
- `.gate p`
- `.compose-as`

## Modal dialog

`src/sass/partials/_modal.scss` — 6 selectors

- `.overlay-form`
- `.overlay-panel-form`
- `.overlay-panel-form h2`
- `.overlay-panel-form .input`
- `.overlay-panel-form .textarea`
- `.comment-tool[disabled]`

## Market quotes

`src/sass/partials/_market.scss` — 13 selectors

- `.coin`
- `.coin:first-of-type`
- `.coin-sym`
- `.coin-price`
- `.spark`
- `.spark path`
- `.coin-delta`
- `.coin.is-up .spark path`
- `.coin.is-up .coin-delta`
- `.coin.is-down .spark path`
- `.coin.is-down .coin-delta`
- `.mkt`
- `.mkt b`

## Inline bar charts

`src/sass/partials/_barlist.scss` — 10 selectors

- `.barlist`
- `.barlist .bar-row`
- `.barlist .bar-lab`
- `.barlist .bar-val`
- `.barlist .bar-track`
- `.barlist .bar-fill`
- `.barlist .bar-row.is-muted .bar-fill`
- `.grid-side-main`
- `.grid-side-main > aside`
- `.grid-side-main > div`
