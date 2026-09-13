---
id: decisions
title: Decision log
status: authored
updated: 2026-09-12
sources: [`docs/PIANO-DI-SVILUPPO.md`]
---

# Decision log

One entry per decision. Each records what was chosen, what was rejected and
why, so that a later reader does not relitigate a settled question or
accidentally undo one.

## D-001 — Standalone theme, no parent

**Decided:** 12.09.2026 · **Status:** accepted

The theme is standalone. It is not a child of Understrap.

*Rejected:* an Understrap child with Bootstrap removed, and an Understrap child
with Bootstrap kept.

*Why:* the visual system in `OD/` is complete and deliberately library-free —
OKLCH tokens, its own grid, `--radius: 0`, a CSP with no external scripts,
41 KB of CSS and 21 KB of vanilla JavaScript. With Bootstrap removed, Understrap
contributes only its build pipeline, which is copyable, while charging a parent
that must be overridden almost entirely (`header.php`, `footer.php`,
`loop-templates/`, `global-templates/`, the nav walker, the sidebar logic) and
that can break those overrides on upgrade. With Bootstrap kept, the design
system would have to beat Bootstrap's specificity on every component and the
result would never match the prototype.

*Carried over from Understrap:* the toolchain only — `src/build/`,
`package.json`, the `css` / `js` / `dist` / `watch` scripts, `phpcs` and
`phpstan` configs. The developer workflow is unchanged.

## D-002 — Classic PHP theme, not a block theme

**Decided:** 12.09.2026 · **Status:** accepted

`OD/struttura-wordpress.html` describes a block theme with `templates/*.html`.
The theme is a classic PHP theme instead.

*Why:* it is what was asked for, and it gives full control over markup, which
this design needs.

*Consequence:* the template map in `04-template-map.md` is the authoritative
translation. `theme.json` is still required, for the editor palette,
`appearanceTools` and locking custom colours. When phase 1 lands, both
`OD/struttura-wordpress.html` and `OD/en/wordpress-structure.html` must be
updated so that the public colophon describes the theme that actually exists.

## D-003 — Singleton architecture

**Decided:** 12.09.2026 · **Status:** accepted

One bootstrap object, one instance per module, hooks registered exactly once.

*Known cost:* singletons hide dependencies and resist test doubles. Mitigated
by keeping modules thin and hook-only, with testable logic in static methods.

## D-004 — Community is phase 2

**Decided:** 12.09.2026 · **Status:** accepted

Phase 1 delivers a publishable editorial theme. Roles, public profiles,
sign-in outside `wp-login.php`, TOTP, generated avatars and the `/account/`
area are a separate later module.

*Why:* the site can go live on the editorial theme alone. The community module
is the larger and riskier half and should not block publication.

## D-005 — Italian only in phase 1

**Decided:** 12.09.2026 · **Status:** accepted

No multilingual plugin in phase 1. Text domain, translatable strings and slug
structure are prepared; Polylang integration and the `/en/` tree come in
phase 2.

## D-006 — Real Gutenberg blocks

**Decided:** 12.09.2026 · **Status:** accepted

`l3/code` and `l3/notice` are registered blocks with `block.json` and
server-side PHP rendering.

*Rejected:* shortcodes plus patterns (poor editing experience), ACF blocks
(a paid-plugin dependency, which `01-project.md` forbids).

## D-007 — SEO and GEO live in the theme

**Decided:** 12.09.2026 · **Status:** accepted

Metadata, Open Graph, JSON-LD, feeds and `llms.txt` are theme code. No SEO
suite.

*Why:* the theme already owns its markup; owning its metadata means fewer
moving parts and no settings screen that can be misconfigured. The prototype's
allowance for a "lightweight plugin, meta and sitemap only" is satisfied by
WordPress's native sitemap.

## D-008 — GEO means Generative Engine Optimization

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** Q2

GEO means being cited correctly by generative engines, not local or geographic
SEO. The desk is distributed and the servers are in Iceland; there is no
location to optimise for.

## D-009 — Local environment is a Docker stack at the project root

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** Q9

`compose.yaml` at the project root brings up FrankenPHP with PHP 8.3, MariaDB
11.4 and Redis, with WP-CLI, Adminer, Mailpit and a Node build container behind
profiles. `wp/wp-config.php` reads everything from the environment.

*Rejected:* DDEV and `wp-env`, both of which work, but both of which impose
their own stack rather than the one the colophon commits to publicly. Since the
production stack is documented and unusual — FrankenPHP rather than PHP-FPM —
running something else locally would hide exactly the class of bug worth
catching early.

*Deliberate gaps:* no TLS, no static page cache, no CSP. See
`15-local-environment.md`. The missing page cache is why Q3 is settled by
reasoning rather than by experiment.

## D-010 — English identifiers, Italian public slugs

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** Q1

Every code identifier is English: file, class, function, variable, constant,
hook, meta key, `data-*` attribute, comment. Public slugs and URLs stay Italian
— `/tutorial/`, `/categoria/`, `/archivio/`, `/membri/`, `/difficolta/` — because
they are content, they are read by people, and they are already indexed.

The translation from the Italian prototype is `12-glossary.md` and is now
frozen. `OD/` has to be realigned to it in phase 9, or prototype and theme
diverge permanently.

*Why not Italian throughout:* the code is meant to be published under MIT and
read by people who do not speak Italian.
*Why not English slugs too:* changing an indexed URL costs real traffic and
buys nothing.

## D-011 — CSP allows the inline theme snippet by hash

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** the 7.3 conflict

The theme sets `data-theme` on `<html>` before first paint, which needs an
inline `<script>`. The policy allows exactly that one script, by its SHA-256
hash.

*Rejected:* `'unsafe-inline'`, which would void the policy; and a per-request
nonce, which would make every response unique and defeat the static page cache.

The hash is computed at build time by `tools/csp-hash.py` and written into the
header at the web-server layer. `docker/Caddyfile` carries the policy,
commented out, with the procedure next to it.

Two details that are easy to get wrong:

- **The policy applies to the public site only.** `/wp-admin` and
  `wp-login.php` are excluded: the block editor needs `'unsafe-eval'` and
  inline styles, and fighting that achieves nothing.
- **`style-src` keeps `'unsafe-inline'`.** WordPress core blocks emit inline
  `<style>` and hashing them is not tractable. The strictness that matters is
  on `script-src`, which is where the site's own promise — no third-party code
  — actually lives.

Turn the policy on when the asset pipeline is settled. Before that it converts
every violation into a silent failure.

## D-012 — Market data is fetched server-side, on a schedule

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** Q4

The Crypto column never causes a request from the reader's browser. A scheduled
job fetches the figures every 15 minutes into a transient, with the last good
response kept separately as a stale fallback; the template renders whatever is
in the cache and always prints the timestamp it was taken at. Older than two
hours, the column says the data is unavailable rather than showing a stale
number without saying so.

*Why:* a browser-side call to a market API would break the CSP and the
"no third parties" promise in one move, and would leak every reader's address
to that API.

Provider: a single public endpoint behind a `Market_Data` module, so swapping it
is a one-file change. CoinGecko's public API is the default because it needs no
key. That choice is reversible and is the desk's to confirm.

## D-013 — The newsletter is collected in-house

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** Q5

Addresses go into the site's own table, with double opt-in, a one-time
confirmation token, and unsubscribe by token from a link in every dispatch.
Export and erasure go through the native WordPress privacy tools, like
everything else.

*Why:* an external provider would contradict the CSP and the no-third-parties
rule, and would hand the subscriber list to somebody else. For a magazine whose
readers chose it partly for that reason, the list is the last thing to
outsource.

*Accepted cost, stated plainly:* sending mail from your own server is real
work — SPF, DKIM, DMARC, reputation, bounce handling. That is precisely why
most sites use a provider. The trade-off is taken on purpose, and it is an
infrastructure task, not a theme task.

## D-014 — Two repositories

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** Q6

- The **theme** is its own git repository, public, MIT, containing only the
  theme directory.
- The **project** is a separate private repository: `OD/`, `docs/`, `docker/`,
  `compose.yaml`, `.env.example`, `tools/`.
- `wp/` is in neither. WordPress core is 60 MB of vendor code that the stack
  reinstalls on demand; committing it buys nothing and hides the diff that
  matters.

*Rejected:* a git submodule, and moving the theme out of
`wp/wp-content/themes/` and bind-mounting it into place. Both work. Both add a
layer of indirection that every future tool — WP-CLI, phpcs, the IDE, a
contributor's clone — has to be taught about, in exchange for a tidiness that
two plain repositories already provide.

## D-015 — The theme repository commits its compiled assets

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** Q7

`css/lupin3rd.css` and `js/lupin3rd.js` are committed. A published WordPress
theme has to work when it is downloaded and dropped into `wp-content/themes/`;
whoever does that will not run `npm install`.

`node_modules/` is never committed.

*Consequence to guard:* compiled assets can drift from their sources without
anyone noticing. A pre-commit check, or a CI step that rebuilds and fails on a
diff, is part of phase 9 rather than an afterthought.

## D-016 — Crawler policy follows from the licence

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** Q8 · **Review:** quarterly

The texts are CC BY-SA 4.0: reuse is allowed, with attribution and share-alike.
The crawler policy is that licence applied consistently, not a mood about AI.

**Allowed** — crawlers that fetch in order to answer a question and cite the
source, and fetchers acting on a specific reader's request. They can satisfy
attribution, and a citation is a reader arriving:

```
OAI-SearchBot, Claude-SearchBot, PerplexityBot
ChatGPT-User, Claude-User
```

**Disallowed** — collectors whose purpose is training corpora:

```
GPTBot, ClaudeBot, meta-externalagent, CCBot, Google-Extended
```

Not because training is illegitimate, but because **share-alike cannot be
honoured by a set of model weights**. The licence's condition is unmeetable, so
the licence is not granted. That reason is worth stating in `llms.txt`, because
it is a position rather than a reflex, and it tells a well-behaved operator
exactly what would change our mind.

Two honesties to keep in the file:

- `robots.txt` is a request, not a control. Some fetchers are documented as
  ignoring it. Anything that must not be taken has to be behind authentication.
- **This list goes stale.** Crawler tokens are added and renamed constantly.
  Review it every quarter and record the date; a robots.txt written once and
  forgotten is worse than none, because it produces false confidence.

## D-017 — The header renders signed-out, then JavaScript corrects it

**Decided:** 12.09.2026 · **Status:** accepted · **Closes:** Q3

Public HTML is identical for every reader. PHP always renders the header in its
signed-out state; a small script then reads a minimal, non-`HttpOnly` cookie
that carries only a pseudonym and an unread count, and swaps in the signed-in
header. The session itself stays in the ordinary WordPress cookie, which is
`HttpOnly` and is never read by script.

*Rejected:* rendering the header from PHP, which makes every page unique and
turns the static page cache into decoration; and edge side includes, which work
but put a second templating system in Nginx for one fragment.

Consequences that have to be respected from phase 4, not retrofitted:

- The markup for both states ships in every page, one of them hidden. It is a
  few hundred bytes.
- Nothing behind the session may be rendered server-side into a public page. A
  private bookmark list is a page under `/account/`, which is not cached.
- The swap must not move the layout. Both states occupy the same 44px box —
  the prototype already does this deliberately, and
  `03-components.md` keeps it that way.
- The cookie carries display data only. Treat it as untrusted, because the
  reader can edit it; nothing is authorised by it.
