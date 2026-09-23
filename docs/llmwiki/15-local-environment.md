---
id: local-environment
title: Local environment
status: authored
updated: 2026-09-12
sources: [`compose.yaml`, `docker/`, `wp/wp-config.php`]
---

# Local environment

A Docker stack at the project root. It mirrors the production stack declared in
the public colophon — PHP 8.3, MariaDB, FrankenPHP, Redis — closely enough that
a bug found here is a real bug, and no more closely than that.

Operating instructions are in `docker/README.md`. This page records what the
stack is and what it deliberately is not, so that nobody mistakes a local
success for a production guarantee.

## Shape

| Service | Role | Profile |
|---|---|---|
| `app` | FrankenPHP: Caddy with PHP 8.3 embedded, built from `docker/php` | default |
| `db` | MariaDB 11.4 | default |
| `redis` | object cache, no volume | default |
| `wp` | WP-CLI, run on demand | `cli` |
| `adminer` | database browser | `tools` |
| `mailpit` | catches every outgoing message | `tools` |
| `node` | theme build, optional | `build` |

| URL | What |
|---|---|
| `http://localhost:8080` | the site |
| `http://localhost:8082` | Adminer |
| `http://localhost:8025` | Mailpit |

The prototype is served beside the site on purpose. During phase 4 the work is
comparing two tabs at the same window size, and that should not require a
second tool.

## Configuration

`wp/wp-config.php` reads every value from the environment. No credential and no
salt is written into it, so the same file works locally and on the server.
`.env` holds the local values and is not committed; `.env.example` documents
them.

Constraints that follow from `10-security-and-privacy.md` and are set from
configuration rather than from theme code:

- `DISALLOW_FILE_EDIT` is always true.
- `DISALLOW_FILE_MODS` is true except when `WP_ENVIRONMENT_TYPE` is `local`,
  because installing a plugin locally is how you test against one.
- `AUTOMATIC_UPDATER_DISABLED` is true: code arrives through deployment.

## What the stack does not reproduce

These are the things it would be a mistake to believe are tested because the
site works locally.

- **TLS.** `auto_https` is off. Certificates belong to the edge.
- **The static page cache.** Every local request goes through PHP. The rule in
  `10-security-and-privacy.md` that public HTML must not vary by reader
  therefore cannot be observed locally; it has to be reasoned about. This is
  the single largest gap between the two environments and the reason Q3 is
  decided on paper rather than by experiment.
- **The Content-Security-Policy**, including the SHA-256 hash of the inline
  theme-switch snippet. Off by default because during development it would hide
  every violation behind a silent failure. Turn it on in the Caddyfile before
  declaring that part done.
- **The `.onion` mirror** and the SecureDrop instance.

## Redis

The service runs but WordPress ignores it until an `object-cache.php` drop-in is
installed; `docker/README.md` has the two commands. The service has no volume:
an object cache that survives a restart produces bugs that are very hard to
read.
