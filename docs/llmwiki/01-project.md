---
id: project
title: The project
status: authored
updated: 2026-09-12
sources: [`docs/PIANO-DI-SVILUPPO.md`, `style.css`, the site's own pages]
---

# The project

`lupin3rd.org` is an independent Italian magazine about technical culture at
the margins: security, privacy, hardware, OSINT, crypto, reverse engineering,
law and ethics. Online since 1999. Six people, no office, distributed desk,
servers in Iceland, no investors.

It publishes in Italian and English, with two separate URL trees and no
machine translation.

## What the site is, technically

| | |
|---|---|
| Platform | WordPress 7.x |
| Theme | `lupin3rd`, standalone, MIT licensed, public repository |
| Stack | PHP 8.3, MariaDB, Nginx + FrankenPHP |
| Cache | static page cache + Redis object cache |
| Analytics | self-hosted, cookie-free, aggregate only |
| Backups | encrypted off-site snapshots, daily |

## Hard constraints

These are not preferences. Code that violates one of them does not ship.

- **No third-party requests on a public page.** No CDN, no web fonts, no
  Gravatar, no analytics script, no embedded player, no external syntax
  highlighter. The `Content-Security-Policy` allows scripts from this origin
  only.
- **No profiling cookies, no consent banner.** The banner is absent because
  there is nothing to consent to.
- **No photographs.** Article imagery is a generative mark rendered from CSS;
  avatars are deterministic patterns derived from an account seed.
- **No user enumeration.** `/wp/v2/users` is closed to anonymous callers,
  author feeds are off, `?author=1` reveals nothing.
- **No paid plugin dependency.** Custom fields, roles and metadata are
  registered in theme code and live in the theme repository.
- **Corrections are never deleted.** They stay at the foot of the piece.

## Editorial identity that the code must preserve

- Three content formats with three different promises: news, tutorial,
  dossier. See `05-content-model.md`.
- A tutorial without a "legal scope" notice cannot be published. This is
  enforced in code, not by convention. See `08-blocks-and-patterns.md`.
- Difficulty is encoded with filled blocks, never with colour, so that it
  survives colour blindness and monochrome rendering.
- Pseudonyms only. The theme never renders an email address or a last-seen
  timestamp on a public page.
