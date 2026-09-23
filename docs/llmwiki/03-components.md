---
id: components
title: Component inventory
status: generated
updated: 2026-09-23
sources: [`src/sass/lupin3rd.scss`, `src/sass/partials/*.scss`]
---

# Component inventory

Every class the visual system defines, grouped by the SCSS partial that owns it. A PHP template may only emit classes listed here; a new class goes into a partial first, and this page is regenerated.

> **Generated file — do not edit by hand.**
> Regenerate with `python3 docs/llmwiki/tools/sync_from_theme.py` after changing the source it reads.


## Styling in the markup

Inline `style` attributes in the theme's templates: **none at all**, which is the number to keep. The prototype this theme was ported from carried several hundred — hero padding, headline measures, stack gaps, muted notes — and everything recurring five times or more became a real class in `src/sass/partials/_parity.scss`, which loads last so it can refine the components above it. The rest was rewritten rather than carried over. Each new one is a small argument that a class is missing.

## Partials, in cascade order

The order is the order of `@use` in `src/sass/lupin3rd.scss`, and it is not alphabetical by accident: a partial can only refine the ones above it. Rearranging this table means rearranging the stylesheet.

| # | Partial | Section | Selectors |
|---|---|---|---|
| 1 | `_tokens.scss` | Design tokens | 3 |
| 2 | `_reset.scss` | Reset and base | 19 |
| 3 | `_layout.scss` | Layout primitives | 26 |
| 4 | `_typography.scss` | Typography | 19 |
| 5 | `_navbar.scss` | Status bar and navigation | 48 |
| 6 | `_buttons.scss` | Buttons | 15 |
| 7 | `_cards.scss` | Cards and surfaces | 3 |
| 8 | `_marks.scss` | Generative marks (used instead of photographs) | 10 |
| 9 | `_tags.scss` | Pills and tags | 7 |
| 10 | `_post-lists.scss` | Post lists | 21 |
| 11 | `_filters.scss` | Taxonomy filters | 7 |
| 12 | `_empty-state.scss` | Empty states | 5 |
| 13 | `_widgets.scss` | Widgets and sidebar | 15 |
| 14 | `_forms.scss` | Forms | 11 |
| 15 | `_tables.scss` | Tables | 8 |
| 16 | `_prose.scss` | Article prose | 29 |
| 17 | `_toc.scss` | Table of contents | 8 |
| 18 | `_search.scss` | Search overlay | 10 |
| 19 | `_pagination.scss` | Pagination | 5 |
| 20 | `_breadcrumb.scss` | Breadcrumb | 3 |
| 21 | `_footer.scss` | Footer | 8 |
| 22 | `_utilities.scss` | Utilities | 10 |
| 23 | `_identity.scss` | Identity: locally generated avatars | 19 |
| 24 | `_badges.scss` | Role badges | 4 |
| 25 | `_members.scss` | Member rows | 11 |
| 26 | `_activity.scss` | Activity feed | 8 |
| 27 | `_comments.scss` | Discussion | 14 |
| 28 | `_tabs.scss` | Tabs | 8 |
| 29 | `_counters.scss` | Counters | 4 |
| 30 | `_account.scss` | Account menu in the header | 13 |
| 31 | `_account-area.scss` | Account area: notifications and devices | 7 |
| 32 | `_account-forms.scss` | Account forms | 11 |
| 33 | `_modal.scss` | Modal dialog | 6 |
| 34 | `_market.scss` | Market quotes | 13 |
| 35 | `_barlist.scss` | Inline bar charts | 10 |
| 36 | `_parity.scss` | Parity refinements (loads last) | 53 |
| 37 | `_cookieconsent.scss` | Cookie notice (vendored) | 291 |

## Design tokens

`src/sass/partials/_tokens.scss` — 3 selectors

- `:root`
- `body.admin-bar`
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

`src/sass/partials/_layout.scss` — 26 selectors

- `.container`
- `.section`
- `.section + .section`
- `.section-tight`
- `main > .section-tight:last-child`
- `.section + .section-tight`
- `.section-tight:has(.statgrid) + .section`
- `.section:has(.lead-cover) + .section`
- `.stack`
- `.row`
- `.row-tight`
- `.row-between`
- `.grid-2`
- `.grid-3`
- `.grid-4`
- `.grid-2-1`
- `.grid-1-2`
- `.grid-main-side`
- `.grid-main-side > aside`
- `.prose :is(h2`
- `h3`
- `h4)[id]`
- `[id^="comment-"]`
- `.split-feature`
- `.grid-main-side > .stack`
- `.grid-main-side #comments`

## Typography

`src/sass/partials/_typography.scss` — 19 selectors

- `.h1`
- `h1`
- `.h2`
- `h2`
- `.h3`
- `h3`
- `.lead`
- `.eyebrow`
- `.kicker`
- `h1 + .kicker`
- `h2 + .kicker`
- `h3 + .kicker`
- `.stack > h1 + .kicker`
- `.stack > h2 + .kicker`
- `.stack > h3 + .kicker`
- `.meta`
- `.num`
- `.rule`
- `.rule-strong`

## Status bar and navigation

`src/sass/partials/_navbar.scss` — 48 selectors

- `.statusbar`
- `.statusbar .container`
- `.statusbar .ticker`
- `.statusbar b`
- `.caret`
- `50%`
- `.topnav`
- `.topnav::before`
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
- `.langswitch .is-off`
- `.nav-tools > .langswitch`
- `.nav-tools > .account`
- `mark`
- `.nav-extra`
- `.nav-account`
- `.no-js .nav-burger`
- `.no-js .topnav nav`
- `html:not(.no-js) .nav-burger`
- `html:not(.no-js) .topnav nav`
- `html:not(.no-js) .topnav nav.is-open`
- `.nav-extra .nav-close`
- `.nav-account .nav-account-name`
- `.nav-account a:not(.btn)`
- `.nav-account a:not(.btn):hover`
- `.nav-scrim`
- `.nav-scrim.is-open`
- `html.nav-locked`
- `html.nav-locked body`
- `html.nav-locked .topnav`

## Buttons

`src/sass/partials/_buttons.scss` — 15 selectors

- `.btn`
- `.btn:active`
- `.btn-primary`
- `.btn-primary:hover`
- `.btn-secondary`
- `.btn-secondary:hover`
- `.btn-danger`
- `.btn-danger:hover`
- `.btn-danger[disabled]`
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

`src/sass/partials/_post-lists.scss` — 21 selectors

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
- `.mdlink`
- `.mdlink:hover`
- `p.meta > .mdlink`
- `.post.split-feature`
- `.post.lead-cover`
- `.post.lead-cover > .mark`
- `.post.lead-cover > .mark img`
- `.post.lead-cover::after`
- `.post.lead-cover > .lead-cover-text`
- `.post.lead-cover .lead-cover-text h1`
- `.post.lead-cover .lead-cover-text .lead`

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

`src/sass/partials/_widgets.scss` — 15 selectors

- `.widget`
- `.widget + .widget`
- `.grid-main-side > aside > .widget:first-child`
- `.widget:has(.ds-table) + .widget`
- `.page-template-template-community .grid-main-side > aside > .widget + .widget`
- `.widget-title`
- `.widget ul`
- `.widget li`
- `.widget li:last-child`
- `.widget .list-rule > li`
- `.widget li a`
- `.widget li a:hover`
- `.widget li a span`
- `.widget-note`
- `.widget .avatar-stack`

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

`src/sass/partials/_prose.scss` — 29 selectors

- `.prose`
- `.prose h2`
- `.prose h3`
- `.prose :where(p`
- `ul`
- `ol)`
- `.prose p`
- `.prose a`
- `.prose a:hover`
- `.prose ul`
- `.prose ol`
- `.prose li + li`
- `.prose figcaption`
- `.prose blockquote`
- `.prose > * + *`
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

- `.toc ol`
- `.toc ul`
- `.toc li`
- `.toc li a`
- `.toc li a::before`
- `.toc li a:hover`
- `.toc li a.is-active`
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

`src/sass/partials/_footer.scss` — 8 selectors

- `.pagefoot`
- `.pagefoot .foot-grid`
- `.pagefoot h4`
- `.pagefoot ul`
- `.pagefoot ul + ul`
- `.pagefoot a`
- `.pagefoot a:hover`
- `.foot-bottom`

## Utilities

`src/sass/partials/_utilities.scss` — 10 selectors

- `.hidden`
- `.mw-36`
- `.bigcode`
- `.person`
- `.person .mark`
- `.mw-46`
- `.plain-list`
- `.plain-list li`
- `.link-quiet`
- `.link-quiet:hover`

## Identity: locally generated avatars

`src/sass/partials/_identity.scss` — 19 selectors

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
- `.identity`
- `.identity-name`
- `.identity-name h1`
- `.identity-tools`

## Role badges

`src/sass/partials/_badges.scss` — 4 selectors

- `.badge`
- `.badge-strong`
- `.badge-dashed`
- `.badge svg`

## Member rows

`src/sass/partials/_members.scss` — 11 selectors

- `.member`
- `.member:first-child`
- `.member:not(:has(~ .member))`
- `.member:nth-child(odd):not(:has(~ .member ~ .member))`
- `.member-id`
- `.member-name`
- `a.member:hover`
- `a.member:hover .member-name`
- `a.member`
- `.member-note`
- `.member-pull`

## Activity feed

`src/sass/partials/_activity.scss` — 8 selectors

- `.activity`
- `.activity:first-child`
- `.activity:last-child`
- `.activity-head`
- `.activity-quote`
- `.activity-on`
- `.activity-on a`
- `.activity-on a:hover`

## Discussion

`src/sass/partials/_comments.scss` — 14 selectors

- `.comment`
- `.comment:last-child`
- `.comment-head`
- `.comment-name`
- `.comment-name:hover`
- `.comment-body`
- `.comment-reply-to`
- `.comment-tools`
- `.comment-tool`
- `.comment-tool:hover`
- `.comment-report`
- `.comment-tool[disabled]`
- `span.comment-tool`
- `.comment-nested`

## Tabs

`src/sass/partials/_tabs.scss` — 8 selectors

- `.tabs`
- `.tab`
- `.tab:hover`
- `.tab[aria-selected="true"]`
- `.tab[aria-selected="true"]:hover`
- `.tab .num`
- `.tabpanel[hidden]`
- `.tabpanel > article:first-of-type`

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

## Parity refinements (loads last)

`src/sass/partials/_parity.scss` — 53 selectors

- `main > .section:first-child`
- `.section.hero`
- `.hero h1`
- `.hero .grid-2-1`
- `.note`
- `.note-sm`
- `.mw-32`
- `.stack-sm`
- `.stack-md`
- `.stack-lg`
- `.row-wrap`
- `.list-rule`
- `.list-rule > li`
- `.row-between--bottom`
- `.section--tight`
- `.diff > span[aria-hidden]`
- `ol.list-rule > li`
- `.list-plain`
- `.pagefoot ul`
- `.list-plain > li`
- `.pagefoot ul > li`
- `.overlay-search`
- `.overlay-search .input`
- `.overlay-search .btn`
- `.overlay-results .r-title`
- `.overlay-results .meta`
- `.overlay-hint`
- `.codeblock-body`
- `.codeblock-body > pre`
- `.codeblock-lines`
- `.comment-body > :first-child`
- `.comment-body > :last-child`
- `.comment-body p`
- `.comment-respond`
- `.comment-respond .meta`
- `.comment-form`
- `.comment-form p`
- `.comment-respond > h3`
- `.comment-reply-title`
- `.comment-reply-title small`
- `.form-submit`
- `.authorcard`
- `.authorcard .avatar`
- `.authorcard .meta`
- `.authorcard h3`
- `.authorcard p:last-child`
- `.section-cta`
- `.container-narrow`
- `.row-center`
- `.input-inline`
- `.form-note`
- `.form-note--error`
- `.row-center .field-error`

## Cookie notice (vendored)

`src/sass/vendor/_cookieconsent.scss` — 291 selectors, not listed.

Vendored, and scoped to its own root, so nothing here is a class a template may emit. Read the file when you need one.
