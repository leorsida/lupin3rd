---
id: security-and-privacy
title: Security and privacy
status: authored
updated: 2026-09-12
sources: [`OD/struttura-wordpress.html`, `OD/cassetta-sicura.html`]
---

# Security and privacy

The site publishes about surveillance and disclosure. It is a target, and its
readers have a reason to care how it behaves.

## What the theme closes — `Security`

| Surface | Action |
|---|---|
| XML-RPC | disabled |
| `/wp/v2/users` | denied to anonymous callers |
| `?author=<id>` | does not resolve to a pseudonym |
| Author feeds | disabled |
| `generator` meta, WLW, RSD, shortlink | removed from `<head>` |
| Uploads | EXIF and other metadata stripped on `wp_handle_upload`, **before** thumbnails are generated |
| Gravatar | `pre_get_avatar` filtered; no request ever leaves for gravatar.com |
| Comments | pre-moderation; no external anti-spam service |

Enforced outside the theme, in `wp-config.php` and in Nginx, and listed here so
that nobody assumes the theme does it: `DISALLOW_FILE_EDIT`,
`DISALLOW_FILE_MODS`, a separate admin hostname, executable uploads refused at
the web-server level, request logs kept 72 hours without full IP addresses.

## Content-Security-Policy

The policy allows scripts from this origin only. That collides with one
requirement the design insists on: `data-theme` must be set **before first
paint**, which needs an inline script in `<head>`.

Resolution (D-011): the snippet is fixed, its SHA-256 hash is computed at build
time by `tools/csp-hash.py` and written into the CSP header at the web-server
level. Not `'unsafe-inline'`, which would void the policy, and not a
per-request nonce, which would break the static page cache.

```
default-src 'self';
script-src  'self' 'sha256-<snippet hash>';
style-src   'self' 'unsafe-inline';
img-src     'self' data:;
font-src    'self';
connect-src 'self';
form-action 'self';
frame-ancestors 'none';
base-uri 'none';
object-src 'none'
```

Two exceptions that are deliberate, not oversights:

- **`/wp-admin` and `wp-login.php` are excluded.** The block editor needs
  `'unsafe-eval'` and inline styles; the policy protects the public site, which
  is where the promise lives.
- **`style-src` keeps `'unsafe-inline'`.** WordPress core blocks emit inline
  `<style>` and hashing them is not tractable. `script-src` is where the
  strictness has to be real.

`docker/Caddyfile` carries the policy commented out, with the procedure beside
it. It goes on when the asset pipeline is settled: earlier, it turns every
violation into a silent failure while the theme is still moving.

## Cache and session state

Public HTML must not vary by reader, or the static page cache is pointless.

Decided in D-017: PHP always renders the header signed-out, and a small script
swaps in the signed-in header using a minimal non-`HttpOnly` cookie carrying a
pseudonym and an unread count. The WordPress session cookie stays `HttpOnly`
and is never read by script. The display cookie is reader-editable and
therefore authorises nothing.

Both header states ship in every page with one hidden, and both occupy the same
44px box so the swap cannot move the layout.

## Sensitive material

Sensitive material reaches the desk through the SecureDrop instance on the
`.onion` mirror, or by email encrypted to `0x9F2C 4B11`. Never through
comments, never through a contact form. The theme's job is to say so clearly
and to never offer a form that invites otherwise.

## Data rights

Export and erasure go through the native WordPress privacy tools, surfaced in
`/account/` rather than in `/wp-admin/`. Comments by an erased account remain
on the page signed "account rimosso": a published correction is part of the
article.

## Reporting a comment

A report is a signal, never a verdict. `Comment_Reports` raises a counter on
the comment, sends the desk one email the first time, and shows the count in a
column on the comments screen. It hides nothing by itself.

That is deliberate. Every comment here was read by a person before it appeared,
so letting reports unpublish one would hand any group of readers the power to
remove what they dislike — brigading with extra steps. A human decides, the
same human who let it through.

Nothing about the reporter is stored: no address, no identifier, no per-person
tally. The same reader can therefore report twice and the counter will say two,
which is fine, because the desk reads the comment and not the number. The
counter stops at 25: a robot can keep pressing, and the desk only needs to know
that something is being reported.

Both public forms — the report and the newsletter — carry a nonce. A nonce
lives about a day, so whenever full-page caching arrives a page holding one of
these forms must not be served from a cache older than that.

## Caddy runs directives in its own order, not yours

Everything the site refuses or adds at the edge lives in **one `route`** in
`docker/Caddyfile`, and the order inside it is the order that runs: the refusal
of `/wp-config.php` and friends, then the security headers, then `php_server`.

Outside a route, Caddy sorts directives by its own list, and that cost two
defects in ten minutes:

1. `php_server` sorts before `header` and terminates the chain, so
   `X-Content-Type-Options`, `Referrer-Policy` and `X-Frame-Options` were
   configured and never sent.
2. Moving only `header` and `php_server` into a route left `respond` outside
   it, running too late: **`/wp-config.php` and `/xmlrpc.php` answered 200**,
   handed to PHP.

Neither was visible in a browser — the headers are invisible by nature, and
nobody types `/wp-config.php` by hand. The end-to-end tests reported both on
the first run after the change. This is what they are for.

