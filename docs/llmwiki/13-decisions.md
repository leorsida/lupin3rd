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

**Amended 13.09.2026.** Everything that belongs to the theme lives in the theme
repository: `docs/` (this llmwiki and the development plan), `tools/e2e/`,
`tools/seed-demo-content.sh`, `LICENSE`, `README.md`. What stays outside is
what the theme does not own — `OD/`, the WordPress install, and the local stack
(`compose.yaml`, `docker/`, `.env.example`), which mounts both. A theme
repository shipping a compose file whose paths point four levels up would be
lying to whoever cloned it.

Two consequences, both handled: `docs/llmwiki/tools/sync_from_od.py` now finds
`OD/` by walking up (or from `OD_ROOT`) and says so plainly when it cannot, and
`tools/seed-demo-content.sh` finds `compose.yaml` the same way and runs from
there.

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

## D-018 — Two new roles, not three

**Decided:** 13.09.2026 · **Status:** accepted · **Phase:** community

The theme registers `l3_member` and `l3_contributor`. The prototype's third
badge, «redazione», is the core `editor` role (and `administrator`), shown
under that label.

*Rejected:* a third `l3_desk` role duplicating `editor`. WordPress already
maintains that capability map, and every plugin that asks
`current_user_can( 'edit_others_posts' )` would stop recognising the desk. The
prototype describes three *visible* roles, not three rows in `wp_roles`.

## D-019 — The pseudonym is the login and never changes; the display name does

**Decided:** 13.09.2026 · **Status:** accepted · **Phase:** community

`user_login` holds the pseudonym as first typed and is never edited.
`l3_pseudonym` holds the one on display and can be changed from `/account/`.
Templates render the display name and never the login. A retired pseudonym is
held for ninety days in the `l3_retired_pseudonyms` option so that it cannot be
claimed by somebody else in the meantime.

*Rejected:* editing `user_login`, which WordPress does not support without a
direct write, and which would break every session token in flight.

## D-020 — TOTP is written in the theme

**Decided:** 13.09.2026 · **Status:** accepted · **Phase:** community

The secret lives in `l3_totp_secret`, encrypted with a key derived from
`AUTH_SALT`; verification accepts one interval either side; ten recovery codes
are stored as hashes and shown once.

*Rejected:* a plugin. The no-paid-plugin constraint applies here too, and
RFC 6238 is sixty lines of PHP. The encryption is what makes a database dump
insufficient to sign in — a plaintext secret column would hand an attacker the
second factor along with the first.

## D-021 — `wp-login.php` is the desk's page, not a redirect

**Decided:** 13.09.2026 · **Amended:** 13.09.2026 · **Status:** accepted
· **Phase:** community

`/accesso/` is where readers sign in. `wp-login.php` still renders, and says so
— but an account without `edit_posts` is refused there even when the password
is right, and `?action=register` redirects to `/accesso/`. Every URL WordPress
generates for signing in or registering is filtered to point at `/accesso/`.

The first version of this decision sent `wp-login.php` to a blanket 302. It was
amended before it shipped, because a 302 reads better in a log and is worse in
practice: it removes the one door that still opens when the theme is broken,
which is precisely when somebody needs to get in. Refusing the account instead
of hiding the page keeps the lifeboat and still closes the door — and the
refusal is a thing a test can assert, which a hidden page is not.

Password recovery stays on `wp-login.php` (`lostpassword`, `rp`, `resetpass`).
Those screens are core's, they are safe, and they are hard to reimplement
correctly; theming them is its own piece of work and is not pretended to be
done here.

## D-022 — Sessions are listed from WordPress's own tokens, without a location
**Prototype realigned:** 20.09.2026 — the city is gone from both session rows, and so is the `Europe/Rome` on the identity line, which said the same thing more quietly.

**Decided:** 13.09.2026 · **Status:** accepted · **Phase:** community
· **Deviates from:** `OD/profilo-connesso.html`

`/account/` lists sessions by reading `WP_Session_Tokens` and shows an
approximate browser and platform, plus how long ago each was used. It does not
show a city.

The prototype shows «Firefox su Linux · Roma · adesso». A city means
geolocating an IP address, which means keeping the address — exactly what the
colophon promises not to do. The prototype has to be corrected, not the theme.

## D-023 — Comment drafts are not kept on the server
**Prototype realigned:** 20.09.2026 — the note now says the drafts live in the reader's own browser, with what that costs them: clear the site data and they are gone, and another device never had them.

**Decided:** 13.09.2026 · **Status:** accepted · **Phase:** community
· **Deviates from:** `OD/profilo-connesso.html`

The «Bozze» tab in `/account/` reads what the reader's own browser saved. The
server keeps nothing unpublished.

The prototype promises drafts held for ninety days. Unpublished text sitting in
a database for three months is precisely the category of data this site says it
does not hold, and it would be readable by anyone who reaches the database.

## D-024 — Bookmarks are a list of IDs and carry no history

**Decided:** 13.09.2026 · **Status:** accepted · **Phase:** community

`l3_bookmarks` is a user meta array of post IDs. No table, no timestamps, no
order of reading.

*Rejected:* a bookmarks table with `added_at`. The moment a timestamp exists,
the site holds a reading history, which the colophon says it does not.

## D-025 — «Active account» means «has published», not «has signed in»
**Prototype realigned:** 20.09.2026 — and the note now says why the other count is not available at all, not merely that this one is different.

**Decided:** 13.09.2026 · **Status:** accepted · **Phase:** community
· **Deviates from:** `OD/comunita.html`

`/comunita/` counts accounts with at least one approved contribution.

The prototype defines it as «un account che ha fatto almeno un accesso negli
ultimi dodici mesi». That number cannot be produced without keeping a record of
when each account signed in, and the same page promises not to keep one. The
choice is between a log nobody asked for and a different definition; the
different definition is also the truer one, because the sentence the prototype
appends — «non contiamo le iscrizioni dormienti per gonfiare il numero» — is
about participation, not about sessions.

The page states its own definition in the note under the numbers, so the
measure is legible rather than merely different, and `community.test.js` checks
that the note is there. `OD/comunita.html` and its English version have to be
corrected to match.

The median moderation wait on the same page needed a new field,
`l3_approved_at`: it is about the desk's own speed, not about the reader, and
it is the difference between two timestamps the site already holds. The median
and not the mean — one comment held over a weekend drags an average into a
number that describes nothing that happened.

## D-026 — The sign-out link ships unsigned and the script signs it

**Decided:** 13.09.2026 · **Amended:** 13.09.2026 · **Status:** accepted
· **Phase:** community

The account menu's «Esci» is a real sign-out link. PHP renders it without the
nonce — a nonce is different for every reader and this markup is in every
cached page — and `session.js` appends one from the session hint cookie.

The first version of this decision made «Esci» a link to `/accesso/`, on the
grounds that a nonce could not be in cached markup. That reasoning was right
and the conclusion was wrong: a control labelled «Esci» that does not sign you
out is a worse thing to ship than the problem it avoided. It was caught by the
first person who clicked it.

Carrying the nonce in the hint cookie does not make that cookie a credential.
The server verifies the nonce against the session, so a reader who edits it
gains no way to do anything — only a way to make their own sign-out fail. A
nonce is a request token, not a secret, and whoever can read a non-`HttpOnly`
cookie on this origin could already act as the reader.

Without the script the link still leads somewhere useful: the handler answers a
missing nonce by redirecting to `/accesso/`, which carries a server-rendered
button with a real nonce. `auth.test.js` still asserts that no `_wpnonce`
appears in the header as PHP renders it, which is the invariant that mattered.

## D-027 — Neither presence nor private messages

**Decided:** 16.09.2026 · **Status:** accepted · **Phase:** community

The site does not show who is online, and accounts cannot write to each other.
Instead, somebody who commented under a piece is emailed when the discussion
there moves.

Both of the rejected features were asked for, and both were asked for in good
faith — presence to make the community page feel alive, messages to give
readers a reason to come back. Both were refused on the same two grounds, and
the grounds are worth keeping because they will come up again.

**They contradict what the site says in public.** «Non conserviamo cronologia di
lettura» and «un account qui serve a una cosa sola: firmare quello che scrivi
sotto gli articoli e risponderne» are on `/comunita/` now. A last-seen column is
the reading record the first sentence denies; an inbox is the second thing the
second sentence says this is not. Shipping either without rewriting those lines
would have made the colophon a lie, on a magazine whose subject is surveillance.

**And the traffic argument runs the other way.** A private message produces a
visit to an inbox: a visit that reads no article, leaves no comment and gives
nobody else a reason to arrive. A reply under a piece is content — indexable,
readable by the next visitor, sometimes the reason that visitor came. An inbox
does not only add a feature; it moves conversations from where they are visible
to where they are not, and the visible half is the half that brings people.

What was built instead closes a loop that was already half-written:
`l3_notify_replies` existed as a field, was on the account screen, and was
attached to nothing.

## D-028 — A notification is restraint before it is a feature

**Decided:** 16.09.2026 · **Status:** accepted · **Phase:** community

`Reply_Notifications` writes to somebody only when a contribution is **approved**
(never when it is filed), never to whoever just wrote it, at most once per person
per piece per hour, only to accounts that left the setting on, and always with a
link that turns it off without signing in.

An unwanted email is worse than no email: it teaches the reader to ignore the
address and it teaches their provider to file the domain as spam. Every one of
those rules exists to make the message rare enough to be read.

Two details that are easy to get wrong:

- A comment reaches «approved» by two different routes — inserted approved
  (`comment_post`) for a member out of the queue, or moved there later
  (`transition_comment_status`) by the desk. Watching one of them covers about
  half the discussion and never says so.
- The opt-out link carries an account id, so it is signed with `wp_salt()`:
  without a signature anybody could unsubscribe anybody by counting upwards.
  `community.test.js` checks that the signature is actually verified.

Sending happens on a scheduled event, not in the request that posted the
comment: thirty participants would otherwise be thirty `wp_mail()` calls between
«Invia» and the page coming back.

## D-029 — The notice states, it does not ask

**Decided:** 19.09.2026 · **Status:** accepted · **Phase:** privacy

`orestbida/cookieconsent` 3.1.0 is vendored into `src/js/vendor/` (MIT, licence
beside it) with **one category, `necessary`, read-only**. The notice is a
full-width bar along the bottom carrying two controls: one that dismisses it,
one that opens the table of what is stored.

The site writes nothing optional. Everything in that table is either required
for the page to work or the direct result of an action the reader took —
signing in, ticking «ricorda», switching the theme. A banner with a switch on
it would be claiming there is a decision here, and there is not.

Third-party frames were briefly an optional category and are not one any more.
`Consent::gate_embeds()` still replaces every `<iframe>` pointing at another
host with a placeholder naming it, server-side, before the HTML is sent — but
there is no «allow them all», so a frame loads only when a reader asks for that
frame, and nothing about it is remembered. That is stricter than any answer the
banner could have collected, and it removes the one thing the banner would have
had to store a decision about.

Two consequences worth stating:

- **The panel behind «Vedi quali» stays**, even though its single switch cannot
  move, because the panel is where the cookie table lives and a reader looking
  at the notice should not have to leave it to find out what is stored. It
  costs ~17KB of CSS, which is what the honest version is worth. The library's
  two colour schemes are still left out: `#cc-main` is remapped onto the theme
  tokens, so the notice follows the reader's own light/dark switch.
- **The table is duplicated on purpose**, in `Consent::cookie_table()` and on
  `/privacy/`, and nothing keeps the two in step but `17-privacy.md` and one
  assertion in `privacy.test.js`. Generating one from the other would mean the
  page's prose and the panel's rows sharing a structure neither of them wants.

The wording travels in a `<script type="application/json">` block rather than
an inline `wp_add_inline_script()`: a JSON block is not executed, so a
hash-based `script-src` never has to account for it — and its hash would
otherwise change with every translated string.

**Superseded:** the first version of this decision gave the notice an optional
`embeds` category and three buttons. It was replaced the same day, on the
grounds above: a category is a promise that the alternative is on offer, and
loading third-party frames wholesale never was.

## D-030 — The privacy page is a template, not editable copy

**Decided:** 19.09.2026 · **Status:** accepted · **Phase:** privacy

`/privacy/` is rendered by `page-templates/template-privacy.php`. The seeded
page holds one opening paragraph; every table and every claim is in the
template, translated like any other string.

The desk can rewrite any other page in the editor. Not this one, because this
page's only value is that it agrees with the code: it lists the cookies the
theme actually writes, the fields the account actually has, and the things that
are absent because there is no field for them. A page that can drift from the
code will drift, and a privacy page that has drifted is not a stale page — it
is a false statement with a date on it.

`17-privacy.md` lists what to re-check when the code changes, which is the
other half of this decision: making the page uneditable only helps if somebody
knows when it has to be edited.

## D-031 — The comment IP is not purged, it is never written

**Decided:** 19.09.2026 · **Status:** accepted · **Phase:** privacy

`Security` filters `pre_comment_user_ip` and `pre_comment_user_agent` to `''`.

This was found while writing the privacy page: the colophon already promised
that addresses are not kept beyond the antispam window, and nothing in the
theme made that true. WordPress writes both columns with every comment and
never expires them.

The obvious fix — a scheduled purge — was rejected. WP-Cron runs when somebody
visits the site, so «deleted after 24 hours» on a quiet night means «deleted
whenever the next reader arrives». A promise whose clock is other people's
traffic is not a promise. Never writing the value has no clock at all.

The cost is real and accepted: core's own rate limiting and any future
IP-based defence lose their input. The moderation queue for new accounts
(`MODERATION_THRESHOLD`) and the report tool were chosen precisely because they
work without knowing where somebody writes from.

Rows written before this existed still carry addresses; `17-privacy.md` has the
one-off `UPDATE`.

## D-032 — An account is closed, not deleted, and the difference is said out loud

**Decided:** 20.09.2026 · **Status:** accepted · **Phase:** community

Closing an account from `/account/` changes its role to `l3_closed`, records the
role it had, destroys every session and stops it appearing anywhere a reader
can look. Nothing is overwritten: the address, the biography and the
fingerprint stay in the database, reachable by the desk.

Two things follow, and both are in the product rather than in the code.

**It is reversible.** `Retirement::reopen()` puts the previous role back and
the account returns exactly as it was, from a button on the user screen in
wp-admin. A first draft of this work scrubbed the row and had to be
irreversible; keeping the data buys the desk a way to undo somebody's mistake.

**It is not an erasure, so it is not called one.** The button says «chiudi
l'account». `/privacy/` now distinguishes the two in as many words: closing is
self-service and immediate, erasure is a request the desk answers by hand. The
temptation to label this «cancella i miei dati» was the whole risk of the
design — on the one page built so that it contains no false statement.

The state is a **role** and not a meta flag, because WordPress already prints a
role column in the user list and already builds the per-role filter links above
it: the desk sees closed accounts, and can list them, without this theme
drawing anything.

## D-033 — With nothing overwritten, hiding is the entire job

**Decided:** 20.09.2026 · **Status:** accepted · **Phase:** community

When the plan still scrubbed the row, a surface that forgot to check showed a
blank. Now it shows the real pseudonym. So the exclusions were built first,
before any button existed, and as **one rule** rather than four:

```php
add_action( 'pre_get_users', array( $this, 'skip_closed_accounts' ) );
```

Every `WP_User_Query` on the front end gets `role__not_in` — the community
lists, the «most active» table, and the user sitemap, which is a front-end
`WP_User_Query` like everything else. wp-admin is exempt, deliberately.

`Profile_Fields::pseudonym_is_free()` is the single query that must see them,
and says so with `l3_include_closed`. Without that opt-out a closed account's
pseudonym would read as free and somebody else could sign contributions with
it — the one outcome this whole mechanism exists to prevent.

Two surfaces were **measured rather than assumed**, and they disagreed:

- The comment feeds are safe: `get_comment_author_rss()` wraps
  `get_comment_author()`, so the theme's filter applies.
- REST is not. `WP_REST_Comments_Controller` assigns
  `$data['author_name'] = $comment->comment_author` — the raw column, no
  filter — and `author_name` is in `view` context, so an anonymous caller gets
  it. `Security::name_the_author_in_rest()` closes it on
  `rest_prepare_comment`, which is the right fix regardless of this feature:
  the pseudonym filter should have governed REST all along.

Checking the pages alone would have passed while `/wp-json/wp/v2/comments`
went on publishing the name.

## D-034 — The prototype is corrected when the theme is right, not left to drift

**Decided:** 20.09.2026 · **Status:** accepted · **Phase:** privacy

`OD/` is the design source of truth, and four of its statements had become
false: the two in D-022 and D-023, the definition of an active account in
D-025, and a «Cancellazione» callout on `comunita.html` promising an export
that does not exist and naming the account closure a deletion.

All four are now corrected in both languages. The rule this settles: when a
decision says the prototype is wrong, the prototype gets edited **then**, not
added to a list. A design source of truth that contradicts the running site is
not a source of truth, and the longer it sits the more likely somebody rebuilds
the mistake from it — which is exactly what `OD/` exists to make easy.

What is not corrected is anything where the prototype is simply ahead of the
theme: the second factor, the bookmarks tab, the `.onion` mirror. Those are
promises the theme has not kept yet, not statements that are false.

## D-035 — One source of truth, and it is the code

**Decided:** 20.09.2026 · **Status:** accepted · **Phase:** privacy
· **Supersedes:** the «Design source of truth: `OD/`» line in `00-index.md`

The Open Design prototype is retired. The theme is the source of truth for the
visual system, for behaviour and for structure, and `docs/llmwiki/` is derived
from it.

The reason is the one D-034 was circling: a second source has to be kept in
step, and keeping it in step is work nobody schedules. In four days the
prototype produced four false statements — a city beside a session, drafts held
for ninety days, an active account defined by a sign-in, an export that does
not exist — and each was found by accident rather than by looking. A reference
that has to be audited to be trusted is not a reference.

**What the migration actually required**, because «delete the folder» was not
it: the three generated pages were derived from `OD/assets/app.css` and
`app.js`. `sync_from_od.py` is replaced by `sync_from_theme.py`, which reads
`src/sass/partials/_tokens.scss` for the tokens, the `@use` order in
`src/sass/lupin3rd.scss` for the component inventory, and `src/js/modules/`
plus the PHP templates for the `data-*` contract. Same three pages, same
shape, derived from what they describe.

Two of them got better in the move, which is the argument for doing it this
way rather than freezing them:

- **The component inventory now follows the cascade.** Its order is the order
  of `@use` in the entry file, so the table cannot disagree with the
  stylesheet about which partial refines which.
- **The JS contract now has two columns**, emitted and read, instead of one
  occurrence count over prototype HTML. A hook the modules read and no
  template prints, or the reverse, is now visible on the page. There are none
  of the first kind today. The page also warns that a zero in the read column
  is not proof, because a module that builds the name at runtime —
  `'data-level-' + n` — never spells it out.

**What was given up, knowingly:** the 15 English prototype pages. That copy
existed nowhere else and the `/en/` build will start from the `.pot` instead.
Also the visual reference for what is designed and not yet built — the second
factor, the bookmarks tab, the `.onion` mirror. Those survive as prose in
`docs/PIANO-*.md` and nowhere as pictures.

The decision log keeps saying «Deviates from `OD/…`» in thirteen places. Those
lines are history, not directions, and they stay: a decision that was taken
against something is not clearer for hiding what it was taken against.

## D-036 — «Community», in the copy and in the URL

**Decided:** 20.09.2026 · **Status:** accepted · **Phase:** community
· **Amends:** the slug rule in `11-conventions.md`

The page is «Community» and lives at `/community/`. Every label, breadcrumb
and heading that said «Comunità» says «Community».

The convention said public slugs stay Italian, and this looks like an
exception to it. It is not, once the rule is stated properly: a slug was
Italian because Italian is the word the desk would use out loud, not because
of a policy about languages. For this one the desk uses the English word. The
convention now says that.

What the change actually costs, and why it was worth doing today rather than
later: the slug is written out in six templates, four e2e files and the seed,
and the seed had to **rename** the existing page rather than create one —
`ensure_post` finds a page by slug, so without the rename a seeded site would
carry two community pages and the old URL would go on resolving. On a site
already published this would also need a redirect from `/comunita/`; this one
is not published yet, which is the whole reason the rename is free.

## D-037 — La lingua la dichiara il tema, non l'impostazione di WordPress

**Decided:** 20.09.2026 · **Status:** accepted · **Phase:** 1

`I18n::published()` elenca le lingue in cui il sito **pubblica davvero** —
oggi `array( 'it' )`. `I18n::language()` legge `get_locale()` ma accetta solo
un codice presente in quell'elenco, altrimenti ripiega sul primo. Il filtro
`lupin3rd_published_languages` è il punto in cui la fase 2 aggiunge `'en'`.

Il difetto che l'ha provocata: l'aggiornamento a WordPress 7.1.1 ha lasciato
*Impostazioni → Generali → Lingua del sito* su English (United States).
`get_locale()` ha risposto `en_US`, `language()` si è fidata, e la testata ha
acceso EN su un sito interamente in italiano, con il link a un `/en/` che
risponde 404. Nessun errore: ogni pezzo faceva il suo lavoro: `get_locale()`
risponde per **l'installazione**, non per il contenuto. L'unica domanda che
nessuno poneva era se quella lingua esistesse qui.

Due conseguenze, entrambe della stessa forma — *una copia di un fatto che
nessuno tiene allineata*:

- `language-switcher.php` aveva l'elenco `array( 'it', 'en' )` scritto a mano
  e ne faceva due link veri. Ora quell'array dice **cosa si vede**, mentre
  `published()` dice **cosa funziona**: una lingua non pubblicata è uno
  `<span class="is-off">` inerte, non un link verso un 404.
- `<html lang>` lo componeva il core da `get_locale()`. Il filtro
  `language_attributes` lo riallinea a `language()`: la dichiarazione della
  pagina ora descrive la prosa che contiene, che è quello che leggono uno
  screen reader e un crawler.

Resta vero che l'impostazione va rimessa su Italiano: questa decisione toglie
al pannello di amministrazione il potere di mentire sul frontend, non lo
sostituisce.

## D-038 — Un gemello in Markdown, dichiarato e mai imposto

**Decided:** 23.09.2026 · **Status:** accepted · **Phase:** 1
· **Piano:** `claude/lupin3rd-markdown-agenti.md` nel progetto

Ogni contenuto pubblicato risponde anche al proprio indirizzo con `.md` in
fondo, con un'intestazione YAML che porta titolo, URL canonico, autore e
**licenza**. Accanto al titolo c'è un link a quel file, che `markdown-copy.js`
trasforma in un pulsante «copia».

**Il testo si costruisce dai blocchi, non dalla pagina.** `parse_blocks()`
restituisce la struttura prima che diventi HTML: `l3/code` conserva il
linguaggio che ha già nell'attributo, `l3/notice` conserva la sua etichetta. Le
alternative erano una libreria HTML→Markdown — una dipendenza *a runtime* in un
tema che sul server non carica `vendor/` e che vuole restare generico — o dei
file statici rigenerati al salvataggio, cioè una seconda copia del contenuto
che nessuno tiene allineata (D-035).

Due conseguenze arrivano gratis proprio perché si parte dai blocchi: i commenti
non ci sono perché non sono mai stati nel contenuto, e non c'è nessun iframe da
trattenere. Gli incorporati diventano l'indirizzo che puntavano — conversione
fatta in un punto solo, dentro `inline()`, perché un incorporato arriva per tre
strade diverse (blocco `core/embed`, blocco `core/html`, markup scritto a mano
in un post classico) e solo la prima porta l'URL in un attributo già letto.

**I crawler si dichiarano, non si dirottano.** Niente riconoscimento dello
`User-Agent`: è una stringa che il client si attribuisce da solo, funzionerebbe
con i quattro educati, e in cambio il sito servirebbe corpi diversi allo stesso
indirizzo a seconda di chi chiede — cloaking, e una cache davanti basterebbe a
farlo arrivare a un lettore umano. Al suo posto: `rel="alternate"` nel `<head>`
e come header `Link:`, l'elenco dei `.md` dentro il `/llms.txt` che `Geo` già
serviva, e `X-Robots-Tag: noindex` sui `.md` perché sono una seconda copia.

**Correzione, il giorno dopo.** La prima versione di questo lavoro aggiungeva un
modulo `Llms_Txt` che serviva `/llms.txt` da capo, ignorando che `Geo` lo faceva
già — con la licenza, la politica sui crawler e perfino la riga su
`redirect_canonical` che quell'indirizzo richiede. Registrandosi su
`template_redirect` con priorità 0 contro la 1 di `Geo`, il doppione **vinceva
in silenzio**: nessun errore, e la politica sui crawler spariva dal documento.
L'ha trovato un test che c'era da prima — `seo.test.js`, «data documents carry
text, not HTML entities» — perché la copia nuova non decodificava le entità. Il
modulo è stato cancellato e la sezione è entrata in `Geo`. Stessa lezione di
D-035, questa volta commessa da noi: prima di scrivere un modulo, cercare
l'indirizzo che deve servire.

Resta possibile più avanti, senza rifare niente, la negoziazione su `Accept:`
con `Vary: Accept` — corretta perché è il client a chiederla, e da rivedere il
giorno che davanti al sito ci sarà una cache.

Due difetti trovati misurando, non ragionando, e per questo vale la pena
scriverli: la home è una pagina statica, e `rtrim()` sul suo permalink produceva
`https://lupin3rd.org.md`, un indirizzo su un dominio che non è nostro — ora la
home non offre alcun gemello, essendo anche l'unica pagina il cui contenuto è il
template e non il post. E la cache del Markdown ha in chiave il `filemtime()` di
`class-markdown.php` oltre alla data di modifica del post: senza, una regola
corretta nel walker resta invisibile finché il transient non scade, che è il
modo più rapido di passare un pomeriggio a cercare un difetto già risolto.
