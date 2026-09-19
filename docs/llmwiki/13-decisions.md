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

**Decided:** 13.09.2026 · **Status:** accepted · **Phase:** community
· **Deviates from:** `OD/profilo-connesso.html`

`/account/` lists sessions by reading `WP_Session_Tokens` and shows an
approximate browser and platform, plus how long ago each was used. It does not
show a city.

The prototype shows «Firefox su Linux · Roma · adesso». A city means
geolocating an IP address, which means keeping the address — exactly what the
colophon promises not to do. The prototype has to be corrected, not the theme.

## D-023 — Comment drafts are not kept on the server

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

## D-029 — The notice governs one thing, and that thing is enforced

**Decided:** 19.09.2026 · **Status:** accepted · **Phase:** privacy

`orestbida/cookieconsent` 3.1.0 is vendored into `src/js/vendor/` (MIT, licence
beside it), with a single optional category: `embeds`.

The question a banner is supposed to ask is «may we do this thing to you». On
this site there is exactly one such thing: an `<iframe>` inside an article that
would tell another host you are reading this page. Everything else stored here
is the session, the comment fields somebody ticked a box to keep, and the theme
switch — necessary, or already an explicit action. A category for those is a
question with one answer, which is the kind of banner this magazine writes
about.

So the one category is enforced rather than declared. `Consent::gate_embeds()`
rewrites the content server-side before it is sent, which means a reader who
blocks JavaScript, or who never answers the notice, still never contacts the
third party. A banner that sets a flag some script is supposed to respect is a
promise; a frame that was never in the HTML is a fact.

Two consequences worth stating:

- **Loading one frame remembers nothing.** `data-embed-show` restores that
  placeholder and no other, and writes no cookie. The next page asks again.
- **The preferences panel stays**, and the notice keeps its third button. One
  category makes the panel's *switches* redundant — but the panel is also where
  the cookie table lives, and a reader who wants to know what is stored before
  answering should not have to leave the notice to find out. It costs ~17KB of
  CSS, which is what the honest version of this notice is worth. The two colour
  schemes are still left out: `_cookieconsent.scss` remaps `--cc-*` onto the
  theme tokens under `#cc-main`, so the notice follows the reader's own switch
  instead of carrying its own palette.
- **The table is duplicated on purpose**, here and on `/privacy/`, and nothing
  keeps the two in step but `17-privacy.md`. Generating one from the other
  would mean the page's prose and the panel's rows sharing a structure neither
  of them wants.

The wording travels in a `<script type="application/json">` block rather than
an inline `wp_add_inline_script()`: a JSON block is not executed, so a
hash-based `script-src` never has to account for it — and its hash would
otherwise change with every translated string.

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
