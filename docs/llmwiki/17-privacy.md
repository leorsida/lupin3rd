---
id: privacy
title: The privacy page, and the notice
status: authored
updated: 2026-09-19
sources: [`page-templates/template-privacy.php`, `inc/class-consent.php`, `src/js/modules/consent.js`]
---

# The privacy page, and the notice

Two things that look like compliance furniture and are not. The page is
written from the code, and the notice governs exactly one thing that would
otherwise happen without asking.

## The one question worth asking

A consent notice on a site whose subject is surveillance has to be honest or
it is worse than nothing. This one has a single optional category, `embeds`,
and it is **enforced server-side rather than declared**: `Consent::gate_embeds()`
runs on `the_content` at priority 20 — after blocks and after WordPress has
expanded its own oEmbeds — and replaces every `<iframe>` pointing at another
host with a placeholder naming that host. Nothing is fetched until a reader
says so.

Everything else the site stores is either strictly necessary or the direct
result of an action the reader took (ticking «remember me», switching the
theme, signing in). Putting those behind a button that only ever gets one
answer is the theatre the page is meant to avoid, so they are described on
`/privacy/` instead.

The notice has three buttons — allow, refuse, **Personalizza** — and the third
opens the preferences panel. With one optional category the panel's switches are
nearly redundant; what earns it its place is the **cookie table** inside it,
naming every cookie the site writes. A reader deciding should not have to leave
the notice to find out what is stored.

`src/sass/vendor/_cookieconsent.scss` therefore carries three of the library's
five stylesheets — base, consent modal, preferences modal — byte for byte as
npm ships them, newlines stripped. The two colour schemes are the ones left out:
the block underneath remaps `--cc-*` onto the theme tokens under `#cc-main`, so
the notice follows the reader's own light/dark switch rather than carrying its
own palette.

## Moving parts

| Piece | Where | Does |
|---|---|---|
| `Lupin3rd\Consent` | `inc/class-consent.php` | gates iframes, prints the wording |
| `l3-consent-config` | `wp_footer` | `<script type="application/json">` — not executed, so a hash-based `script-src` never has to account for it, and the hash would otherwise change with every translated string |
| `initConsent()` | `src/js/modules/consent.js` | runs the library, restores gated embeds, reopens the notice |
| vendored library | `src/js/vendor/cookieconsent.js` | `orestbida/cookieconsent` 3.1.0, MIT, licence beside it |
| `l3_consent` | cookie, 180 days, `SameSite=Lax` | the answer, and nothing else |

The panel's cookie table is built by `Consent::cookie_table()` and duplicates
the one on `/privacy/`. Nothing enforces the match — change one, change both.

`data-*` contract (also in `07-js-contract.md`):

| Attribute | On | Meaning |
|---|---|---|
| `data-embed-gate` | the placeholder | holds `data-embed-host` and `data-embed` (the original iframe, base64 so quotes survive the attribute) |
| `data-embed-show` | its button | load **this** frame, once, remembering nothing |
| `data-consent-open` | footer link, privacy-page button | reopen the notice; the button ships `hidden` and the module unhides it, the footer's is a real link to `/privacy/` and is only intercepted |

## Why the page is a template and not editable copy

`page-templates/template-privacy.php` holds the text. The seeded page holds
one opening paragraph and nothing else. This is deliberate: a desk that could
rewrite the tables in the editor could make them disagree with the code, and a
privacy page that disagrees with the code is a lie with a timestamp on it.
Wording changes go through `languages/`, like every other string.

## When the code changes, check this page

Anything here is a claim the page makes. Break one and the page is wrong until
somebody edits it.

- **A new cookie, or a changed lifetime.** Two tables now: the page's, and
  `Consent::cookie_table()` behind «Personalizza». Both list `wordpress_logged_in_*`,
  `wordpress_sec_*`, `l3_hint` (14 days), `comment_author_*`, `l3_consent`
  (180 days) and `l3-theme` (localStorage, described as not a cookie).
- **A new field on the account.** The page says the site asks for a pseudonym,
  an email and a password, and does not want a real name, a document, a phone
  number, a birth date, a company or a photograph. `Profile_Fields` adding one
  contradicts that sentence.
- **Anything loaded from another domain.** The page says there is none: no
  font, no stylesheet, no script. This is also what makes the notice's single
  category true.
- **A third-party service of any kind** — an anti-spam API, a mail relay, an
  analytics endpoint. The page says the newsletter addresses live in this
  database and nowhere else.
- **`MODERATION_THRESHOLD`.** Printed in the sidebar, read from `Community`,
  so it follows on its own — but the sentence around it («the defence we chose
  instead of recording where you write from») stops being true the day an IP
  is stored.
- **`Security::forget_address()` / `forget_agent()`.** These two filters are
  the reason the page can say, in bold, that neither the IP nor the user agent
  is recorded. See below.
- **The retention list.** Comments last as long as the piece; an unconfirmed
  newsletter subscription is deleted; account data lasts as long as the
  account.

## `comment_author_IP` and `comment_agent`

WordPress writes both with every comment and keeps them forever. Nothing in
core expires them. `Security` filters `pre_comment_user_ip` and
`pre_comment_user_agent` to `''`, so they are never written in the first
place — which is a promise the site can keep, unlike a scheduled purge, whose
only clock is somebody visiting the site.

Rows written **before** those filters existed still carry addresses. Purging
them is a one-off, not something the theme should do on activation:

```sql
UPDATE wp_comments SET comment_author_IP = '', comment_agent = '';
```

## What still has to be true outside the theme

The page says request logs are kept only as long as it takes to run and defend
the server, and that the data sits on a server in Europe. Neither is enforced
by any code in here. `10-security-and-privacy.md` lists the rest of what lives
in `wp-config.php` and the web-server configuration.
