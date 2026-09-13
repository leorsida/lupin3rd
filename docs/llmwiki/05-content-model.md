---
id: content-model
title: Content model
status: authored
updated: 2026-09-12
sources: [`OD/struttura-wordpress.html`]
---

# Content model

Three post types, three taxonomies, no page builder.

## Post types

### `post` — News

Native. A short piece tied to current events.

| Meta key | Type | Purpose |
|---|---|---|
| `l3_primary_source` | string (URL) | the primary source being reported on |
| `l3_verification_status` | string | `verified`, `partial`, `unverified` |
| `l3_last_reviewed` | string (date) | when the claims were last checked |

### `tutorial` — Tutorial

Custom post type. Step-by-step, with a reproducible lab.

| Meta key | Type | Purpose |
|---|---|---|
| `l3_difficulty` | string | mirrors the `difficulty` term, used for sorting |
| `l3_estimated_time` | integer (minutes) | feeds `HowTo.totalTime` |
| `l3_prerequisites` | array of strings | feeds `HowTo.supply` |
| `l3_lab_repository` | string (URL) | repository holding the lab |
| `l3_sample_hash` | string | SHA-256 of the sample file |

Editorial constraint: a `tutorial` cannot be published without a legal-scope
notice block. See `08-blocks-and-patterns.md`.

### `dossier` — Dossier

Custom post type, hierarchical. A serialised investigation: the parent is the
index, the children are the instalments, ordered manually with `menu_order`.

| Meta key | Type | Purpose |
|---|---|---|
| `l3_method_note` | string | methodology note, mandatory |
| `l3_documents` | array of attachment IDs | documentary attachments |

## Taxonomies

| Taxonomy | Applies to | Terms |
|---|---|---|
| `category` (native) | `post`, `tutorial`, `dossier` | Sicurezza, Privacy, Hardware, OSINT, Crypto, Reverse engineering, Diritto & etica |
| `difficulty` | `tutorial` only | `base`, `medio`, `avanzato` |
| `post_tag` (native) | all | free, curated by hand: osint, firmware, anonimato, ctf, disclosure, forense |

## Permalinks

| Pattern | Applies to |
|---|---|
| `/%year%/%monthnum%/%postname%/` | `post` |
| `/tutorial/%postname%/` | `tutorial` |
| `/dossier/%postname%/` | `dossier` |
| `/categoria/<slug>/` | `category` |
| `/difficolta/<slug>/` | `difficulty` |
| `/tag/<slug>/` | `post_tag` |
| `/categoria/<slug>/feed/` | per-category feed |
| `/tag/<slug>/feed/` | per-tag feed |

Public slugs stay Italian: they are content and they are already indexed.
Code identifiers are English. See `11-conventions.md`.

## Registration rules

- Every post type and taxonomy is registered by `Content_Types`, never inline
  in a template.
- Every meta field is registered with `register_post_meta`, typed, with an
  explicit `sanitize_callback` and an explicit `auth_callback`.
- `show_in_rest` is true only where the block editor needs the field. It is
  false for anything that must not leave the server.
- Rewrite rules are flushed on `after_switch_theme`, never on every request.

## Subscribers

`l3_subscriber` holds one newsletter address per record, and is the only post
type that is not editorial. Not public, not queryable, not in REST, out of
search, and readable in the dashboard only by whoever can manage the site: it
is a list of people's addresses.

- **Title** — the address.
- **Slug** — `md5( strtolower( address ) )`. That makes the lookup an indexed
  one and keeps the list free of duplicates that differ only in capitalisation.
- **Status** — `draft` is unconfirmed, `publish` is confirmed. Nothing is ever
  sent to a draft.
- `l3_confirm_token` — the token in the confirmation link, deleted on use.
- `l3_subscriber_user` — the account this address belongs to, when it has one.
  Set when subscribing and again on `user_register`, because the two events
  happen in either order.

It is a post type rather than a table of its own so that the addresses are
backed up, exported and deleted with everything else, and so that removing the
theme leaves nothing orphaned behind it.

There is no hosted list service: the colophon promises no third party in the
page and no tracking in the email, which rules every one of them out.

