# lupin3rd

The WordPress theme for [lupin3rd.org](https://lupin3rd.org). Standalone: no
parent theme, no CSS framework, no web fonts, no third-party request of any
kind on a public page.

MIT licensed. Built from the Open Design prototype, which stays the visual
source of truth.

## What it is

- **One stylesheet** (`css/lupin3rd.css`), compiled from 35 SCSS partials that
  mirror the prototype section by section. Six OKLCH tokens are the only place
  colour and typography are defined.
- **One front-end bundle** (`js/lupin3rd.js`), compiled from ES modules. No
  jQuery, no framework, nothing from a CDN. Every feature improves something
  that already works without it.
- **Singleton PHP**: one bootstrap, one instance per module, every hook
  registered exactly once. `functions.php` holds no logic.
- **Dark by default**, with a light variant applied before first paint so the
  page never flashes white.
- **No photographs.** An article's image is a generative mark; an avatar is a
  deterministic pattern, so Gravatar never receives a hash of anybody's email.

## Requirements

WordPress 6.5+, PHP 8.3+.

## Build

```sh
npm install
npm run dist     # compile CSS and JS once
npm run watch    # rebuild on change
```

Compiled assets are committed: a theme that is downloaded and dropped into
`wp-content/themes/` has to work without anybody running `npm install`.

## Layout

```
inc/                 one class per file, autoloaded
template-parts/      partials, grouped by where they appear
src/sass/            partials + one entry point
src/js/modules/      behaviour + one entry point
blocks/              proprietary blocks
languages/           lupin3rd.pot
docs/                the development plan and the llmwiki
tools/e2e/           end-to-end tests, no dependencies
tools/csp-hash.php   the CSP hash for the inline theme snippet
tools/seed-demo-content.sh   demo content for a local site
```

This repository is the theme and everything that belongs to it: its source,
its build, its documentation, its tests. What is deliberately outside it is
the WordPress install and the Open Design prototype — a theme repository that
shipped a compose file pointing four levels up would be lying to whoever
cloned it. The local stack lives in the working tree that holds both.

## Conventions

Every identifier and comment is English. Public slugs and URLs stay Italian —
they are content, and they are indexed.

Behaviour binds to `data-*` attributes only, never to a class or an id: classes
belong to the visual system and have to stay free to change.

Full documentation, including the decision log, is in `docs/llmwiki/`.
`docs/PIANO-DI-SVILUPPO.md` is the development plan and the current state.

## Quality

```sh
composer install
composer run-script qa    # parallel-lint, phpcs, phpstan
node --test tools/e2e/    # with BASE_URL pointing at a running site
```

The end-to-end tests come first, and not out of principle: every defect this
theme has had was invisible to static analysis and visible on the page. See
`tools/e2e/README.md`.
