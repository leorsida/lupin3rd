# End-to-end tests

They load real pages from a running site and assert on what comes back. No
dependencies: Node's own test runner and `node:http`, nothing installed.
(Not `fetch`: it cannot set a `Host` header, and the site redirects anything
that asks by a name other than its own. `helpers.js` says why at length.)

They exist because of what this theme's defects had in common — **not one of
them produced an error anywhere**. A feature card rendered three times, a search
overlay that never opened, blocks that were never registered, a comment grid
given three children instead of two. Every one was visible only by loading the
page, and three of them were invisible to any static analysis by construction.
That is the gap these tests close, and it is why they came before phpcs.

## Running them

With the stack up:

```sh
docker compose --profile qa run --rm e2e
```

From the host, if Node 22 or newer is installed:

```sh
BASE_URL=http://localhost:8080 node --test tools/e2e/*.test.js
```

The files are listed rather than the directory: `node --test tools/e2e/` is
read as a single file to run, and the run fails before a test starts.

`BASE_URL` defaults to `http://app`, which is how the site answers from inside
the compose network.

They read the demo content through the sitemaps, so run
`tools/seed-demo-content.sh` first on an empty site. A test that needs something
the content does not have — a comment, a numbered code block — skips itself
rather than failing.

## Why not `fetch()`

WordPress knows what it is called — `WP_HOME` — and redirects anything that
asks by another name. Inside the compose network the site answers as `app`, so
every request was a 301 towards `localhost:8080`, a host this container cannot
reach: eighteen tests out of twenty-eight failed on the same redirect.

The cure is to connect to `app` while asking for `localhost:8080`. `fetch()`
cannot do that — `Host` is a forbidden header, dropped without a word — so the
helper uses `node:http`, which sets it without argument.

The name is configured nowhere: `helpers.js` reads it from the sitemap, which
lists absolute URLs and is served without a canonical redirect. The site says
what it is called, and the tests call it that. `E2E_SITE_HOST` overrides, if a
setup ever needs it to.

## What each file watches

| File | Watches |
|---|---|
| `pages.test.js` | Every kind of page renders and carries its furniture: sections, byline, index, code block, related row, the dispatch form. Archive filters that actually filter on the server. |
| `seo.test.js` | Canonical, description, Open Graph with an image that exists, JSON-LD that parses, `llms.txt`, `robots.txt`, feed, sitemap. No HTML entities in documents machines read. No third-party host anywhere in the page. |
| `structure.test.js` | The design system's structural contracts: `.comment` takes exactly two children, `#respond` is what carries the form's frame, the side column is one block, cards contain no nested anchors, the overlay uses the state the stylesheet defines. |
| `community.test.js` | The profile promise: a member page lives under `/membri/`, shows pseudonym and role, and carries no email address, no last access and no IP. Tab panels that work without JavaScript. On `/comunita/`: the numbers, the stated definition behind them, and a role filter that filters on the server. |
| `auth.test.js` | The front door: two real POST forms with nonces, a registration and a sign-in that write nothing without one, a honeypot, `wp-login.php` that says it is the desk's and registers nobody, a header account corner with no per-reader nonce in it, a session hint that carries display data and no credential, and an `/account/` closed to strangers whose handlers take no account id from the request. |
| `security.test.js` | `/wp/v2/users` closed, author enumeration blocked, XML-RPC shut, no generator tag, security headers, `wp-config.php` unreachable, and both public forms refusing a request without a nonce. |

## Adding one

When a defect gets through, the fix is not finished until a line here would
have caught it. Write the assertion first, watch it fail, then fix the theme.
