---
id: security-and-privacy
title: Security and privacy
status: authored
updated: 2026-09-19
sources: [`inc/class-security.php`, `inc/class-retirement.php`, `inc/class-consent.php`]
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
| Avatar in the WP admin bar | suppressed: `pre_get_avatar` returns `''` between `admin_bar_menu` (-9999) and `wp_before_admin_bar_render`. Closing on the later *action* rather than on a high priority is deliberate — core builds the greeting's avatar at priority 9991 in WP 7.1, and that number is not a contract |
| Comments | pre-moderation; no external anti-spam service |
| `/wp-json/wp/v2/comments` | `author_name` rewritten through `get_comment_author()` on `rest_prepare_comment`: core reads the raw column, so the pseudonym filter did not reach REST |
| `comment_author_IP`, `comment_agent` | `pre_comment_user_ip` and `pre_comment_user_agent` filtered to `''`; core would write both and keep them forever |
| Third-party `<iframe>` in content | replaced server-side with a placeholder naming the host — `Consent`, see `17-privacy.md` |

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

Sensitive material reaches the desk by email encrypted to the key the status
bar publishes. Never through comments, never through a contact form. The
theme's job is to say so clearly and to never offer a form that invites
otherwise.

The `.onion` mirror and the SecureDrop instance it was to carry are announced
as «soon» and are not in service. Three places used to describe them as if they
were — this page, the sidebar widget and `llms.txt` — and `llms.txt` is the one
that mattered, because it is the document machines read and quote. When the
mirror does arrive, those are the three places to change together.

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


## The admin bar, the sticky header and the drawer

A signed-in editor gets WordPress's admin bar, and it gets in the way twice,
in two different ways.

**The sticky header goes under it.** `.topnav` offsets by `--admin-bar`,
defined in `_tokens.scss`: `0px` for a reader and, under `body.admin-bar`,
core's own `--wp-admin--admin-bar--height`, which already becomes 46px below
782px so the number is never repeated here. Below 600px core makes the bar
`position: absolute` — it scrolls away, and the header can sit at `top: 0`
again.

**The drawer and the search overlay go over it instead.** Offsetting does not
work for them: below 600px the bar is `position: absolute`, so where it sits
depends on the scroll position, and an offset that clears it at the top of the
page leaves a gap everywhere else. They are modal, so they cover it —
`--z-modal` is 100000, one above the bar's own 99999.

The z-index that matters for the drawer goes on **`.topnav`**, not on the
drawer:

```scss
html.nav-locked .topnav { z-index: var(--z-modal); }
```

`.topnav` is `position: sticky` with a z-index, which makes it a stacking
context: the drawer and its scrim paint as part of that one layer whatever
number they carry, so raising the header is the only way to lift what is
inside it. `nav-locked` is already on `<html>` while the drawer is open, so
the header only outranks the bar for as long as the drawer is there.

The search overlay is a sibling of `.topnav`, after `</header>`, so its own
`z-index: var(--z-modal)` is enough.

**And anything that sticks below the header has to move with it.** The side
column rested at a flat `top: 92px` — the header's height, written out — which
stopped clearing the header the moment the header moved down by 32px. The
height is now `--header-height`, and anything sticking below the header writes

```scss
top: calc(var(--admin-bar) + var(--header-height));
```

**at the point of use**, never as a derived token on `:root`. See
`11-conventions.md`: a custom property that references another one is
substituted where it is *declared*, so a token built on `:root` captures
`--admin-bar: 0px` and inherits that zero straight past `body.admin-bar`. That
is exactly how the first attempt at this failed.

The same calc gives in-page anchors their `scroll-margin-top`, on the target
rather than as `scroll-padding-top` on `<html>`, because `admin-bar.css` sets
that property on `<html>` too and which one wins would depend on enqueue order.

Anything new joins one of three lists: offset by `--admin-bar` if it is fixed
at the top of the viewport and part of the page, `--z-modal` if it is modal,
`calc(var(--admin-bar) + var(--header-height))` if it sticks below the header.

## A closed account

Closing an account (`Retirement`, from `/account/`) overwrites nothing. The
role becomes `Community::ROLE_CLOSED`, the previous one is recorded so the desk
can reopen it, every session is destroyed and `authenticate` refuses the
account with the same words as a wrong password — saying «this account was
closed» would answer a question the person typing has no right to ask.

Because nothing is scrubbed, every reader-facing surface has to check. They are:

| Surface | What holds |
|---|---|
| any front-end `WP_User_Query` | `pre_get_users` adds `role__not_in` — covers the community lists and the user sitemap |
| `Profile_Fields::pseudonym()` | returns «account rimosso», which is what the `get_comment_author` and `get_the_author_display_name` filters then publish |
| `Profile_Fields::wants_replies()` | `false`, so no notification is ever addressed to somebody who left |
| `Security::replace_gravatar()` | the generic pattern: the seed is still stored, and a stable shape beside every old comment is an identifier |
| `/wp-json/wp/v2/comments` | see the table above |
| `/membri/<pseudonimo>/` | `410 Gone` on `template_redirect`, before any output |

`Profile_Fields::pseudonym_is_free()` is the one query that opts back in, with
`l3_include_closed`: the pseudonym stays taken, for good.

The desk sees closed accounts in the user list (a role column WordPress already
prints, plus a «Chiuso il» column), on the account's own screen, and in the
subscriber list — an address stays on the newsletter unless the reader ticked
the box, because those are two separate consents.

**This is not an erasure.** See `13-decisions.md` D-032: the wording on
`/privacy/` and on `/account/` is part of the feature, not decoration.
