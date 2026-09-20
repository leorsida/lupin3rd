---
id: template-map
title: Template map
status: authored
updated: 2026-09-12
sources: [`OD/struttura-wordpress.html`, `OD/*.html`]
---

# Template map

`OD/struttura-wordpress.html` describes a block theme with `templates/*.html`.
The theme is a **classic PHP theme**. This table is the translation, and it is
authoritative where the prototype and the code disagree.

| Screen | Prototype page | Theme file | URL | Phase |
|---|---|---|---|---|
| Home | `index.html` | `front-page.php` | `/` | 1 |
| Blog index fallback | — | `home.php` | — | 1 |
| General archive | `archivio.html` | `archive.php` | `/archivio/` | 1 |
| Category archive | — | `category.php` | `/categoria/<slug>/` | 1 |
| Crypto category | `crypto.html` | `category-crypto.php` | `/categoria/crypto/` | 1 |
| Difficulty archive | — | `taxonomy-difficulty.php` | `/difficolta/<slug>/` | 1 |
| News item | `ponte-41-milioni.html` | `single.php` | `/2026/08/<slug>/` | 1 |
| Tutorial | `articolo.html` | `single-tutorial.php` | `/tutorial/<slug>/` | 1 |
| Dossier | — | `single-dossier.php` | `/dossier/<slug>/` | 1 |
| Static page | `chi-siamo.html`, `linee-guida.html`, `cassetta-sicura.html` | `page.php` | `/<slug>/` | 1 |
| Search results | `risultati-ricerca.html` | `search.php` | `/?s=` | 1 |
| Not found | `pagina-404.html` | `404.php` | — | 1 |
| Community | `comunita.html` | `page-templates/template-community.php` | `/comunita/` | 2 |
| Member profile | `profilo.html` | `author.php` | `/membri/<pseudonym>/` | 2 |
| Sign in | `accesso.html` | `page-templates/template-sign-in.php` | `/accesso/` | 2 |
| Account area | `profilo-connesso.html` | `page-templates/template-account.php` | `/account/` | 2 |
| Technical colophon | `struttura-wordpress.html` | `page.php` | `/struttura/` | 1 |

## Template parts

The prototype's six reusable FSE parts become these partials.

| FSE part | Theme partial |
|---|---|
| `parts/statusbar.html` | `template-parts/header/statusbar.php` |
| `parts/header.html` | `template-parts/header/navbar.php` |
| `parts/account.html` | `template-parts/header/account-menu.php` |
| `parts/sidebar-filtri.html` | `template-parts/archive/filter-bar.php` |
| `parts/sidebar-mercato.html` | `template-parts/sidebar/widget-market.php` |
| `parts/footer.html` | `template-parts/footer/columns.php` |

Additional partials, with no FSE counterpart:

- `template-parts/header/language-switcher.php`
- `template-parts/header/search-overlay.php`
- `template-parts/content/card.php` — grid card, `.post`
- `template-parts/content/log-row.php` — dense list row, `.log-row`
- `template-parts/content/feature.php` — two-column feature, `.post.split-feature`
- `template-parts/content/empty-state.php` — `.emptystate`
- `template-parts/content/generative-mark.php` — `.mark`, see `03-components.md`
- `template-parts/single/table-of-contents.php`, `dossier-navigation.php`,
  `author-card.php` (under the piece, inside the main column),
  `related.php` (a section of its own, after the article)
- `template-parts/home/newsletter.php` — the Thursday dispatch
- `template-parts/archive/pagination.php`, `breadcrumb.php`
- `template-parts/sidebar/widget-tags.php`, `widget-links.php`
- `template-parts/footer/bottom-bar.php`

## Rules

- A template file contains no business logic. It calls template tags from
  `Template_Tags` and loops. Anything else belongs in a module.
- A template emits only classes listed in `03-components.md`.
- Every screen must render correctly with JavaScript disabled.

## comments.php

The design system has no comment component, so the discussion is assembled
from the primitives it does have: `.comment` for a posted comment (a grid of
exactly two children: the avatar, then one wrapper for everything else — see
`Comment_Walker`), and the `.field` / `.input` / `.textarea` / `.check` /
`.hint` set for the form.

Two rules that are easy to get wrong:

- **The form's card is `#respond` itself, styled in `_parity.scss` — never a
  wrapper around it.** `comment-reply.js` moves `#respond`, and only
  `#respond`, next to the comment being answered. A wrapper would stay behind
  and the reply form would appear outside its box.
- **Anything that must stay with the form goes inside the form.** The
  moderation notice is part of `comment_field`, not a sibling paragraph:
  `comment_notes_before` is skipped entirely for logged-in users, and content
  placed outside `#respond` does not travel with it.

Each comment carries two tools. *Rispondi* is core's reply link; *Segnala* is a
form, not a link, because a link that reports gets followed by every crawler
that meets it — it posts to `admin-post.php`, so it works with JavaScript off.
The form is `display: contents`, which drops its box and leaves the button as
the flex item of `.comment-tools`, so the two tools sit on one row.

The fields are supplied through `comment_form( array( 'fields' => … ) )`
rather than left to core, so they carry the design system's markup. The author
field is labelled *Pseudonimo*: the site asks for a pseudonym, and the label
should say so.

A signed-in reader gets `.compose-as` instead of core's three-link "Logged in
as …" sentence: the avatar, the identity the comment will carry, and a way
out. Core hides the author, email and URL fields for that reader, so the name
shown there is the only thing telling them who they are about to be.

## The side column

`single.php` fills the aside with widgets; the tutorial and dossier templates
put the index on top of them. That difference matters for scrolling.

In the prototype the article aside holds the index and nothing else, so
`.toc { position: sticky }` is also "the column is sticky". Here the index has
widgets under it, and a sticky box with no background of its own is a window:
the widgets scroll straight through the index. So the **column** is the sticky
unit — `.grid-main-side > aside:has(.toc)` in `_layout.scss` — and the index
flows inside it. The column is capped at the viewport height and scrolls
internally if a long index would otherwise put its bottom out of reach.

The index carries `widget` alongside `toc`, as in the prototype, for the
spacing between it and what follows — but not for the frame: the rule above a
widget marks where a new one starts, and the index already announces itself,
so `.toc.widget` drops it. The class also brings
`.widget li a`, which outweighs `.toc a` and flattens the index's left rail —
in the prototype only the current row keeps it, through the more specific
`.toc a.is-active`. The theme writes the index rows as `.toc li a` so every row
keeps the rail the index was drawn with.

One rule per boundary — this is the rule the side column keeps breaking.
`.widget li` underlines every row, which is a separator between two rows and,
under the last one, a line between the list and nothing: `.widget
li:last-child` drops it. `.list-rule` separates its items from above, so inside
a widget the two would stack into one thick line between rows; there the bottom
border goes.

One rule per widget. `.widget` already draws the heavy line that marks where a
widget starts, so a title inside one never adds its own: the dossier
navigation did, and the column showed two stacked rules. The index is the
opposite case — it takes `widget` for the spacing and drops the rule, because
it opens the column and has nothing to be separated from.

The column sticks for as long as `.grid-main-side` is tall, so the grid holds
the whole piece: the main column is a stack of body, tags and discussion, and
only the header and the mark sit above it. The prototype keeps the comments as
a full-width section below, which would have the column stop halfway down the
page — with a short body it would have nowhere to stick at all.

On one column the stack is dissolved with `display: contents`, which makes its
parts grid items in their own right, and `#comments` takes `order: 1`. The
index then falls after the piece and before the discussion, where a phone
reader can still use it.

## Page openings

A page opens tighter than the rhythm between its sections: every intestazione
in the prototype carries an inline 36-40px of top padding, where `.section`
alone leaves 86px under the navigation. `main > .section:first-child` carries
that in `_parity.scss` — top only, because in this theme the first section
often runs to the end of the page and its bottom padding is what separates it
from the footer. The home page's hero starts there too, and keeps its deep
bottom.

On the section pages — `home.php`, `index.php`, `archive.php`, `search.php` —
the kicker sits *under* the h1 and reads as a subtitle. `h1 + .kicker` moves
the space to the other side, and `.stack > h1 + .kicker` gives it back to the
stack's own gap where there is one. An article has no kicker at all: the
section it belongs to rides in the byline under the lead, as a `.tag
tag-strong` chip inside `.post-meta`, which is where the prototype puts it.
Above the title it was the breadcrumb's last-but-one step repeated one line
lower.

`archive.php` prints the kicker only when it says something the title does not.
On a post type archive `archive_kicker()` returns the post type and the title
is the same word — "Tutorial" over "Tutorial". On a tag or a category it reads
"Tag" over *forense*, which is worth the line.


## Menus, and what happens when there is none

Four locations, registered in `Setup::register_hooks()`:

| Location | Where it renders | Fallback when no menu is assigned |
|---|---|---|
| `primary` | `template-parts/header/navbar.php` | `template-parts/header/menu-fallback.php` |
| `sections` | footer, first column | Notizie / Tutorial / Dossier, built from the post-type archives |
| `desk` | footer, second column | the pages whose slugs are listed in `footer.php`, and only those that exist and are published |
| `channels` | footer, third column, **under** the feed link | nothing; the feed is printed by the template either way |

Every fallback exists so that a fresh install has a usable footer before
anybody has opened *Aspetto → Menu*. None of them is editable, so
`tools/seed-demo-content.sh` creates and assigns all four: on a seeded site
the footer is managed entirely from the back office, and the fallbacks are
what a brand-new install falls back to.

Two consequences to keep in mind.

- **Assigning a menu switches the fallback off completely.** For `desk` that
  also switches off the check that a page exists and is published: the
  fallback refuses to link a draft, a menu item will happily do it.
- **«Cookie e privacy» is not in any menu, by design.** It is also the control
  that reopens the consent notice (`data-consent-open`), so it is printed by
  `footer.php` itself and survives any rearrangement of the `desk` menu.
