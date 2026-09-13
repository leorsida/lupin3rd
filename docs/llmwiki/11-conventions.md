---
id: conventions
title: Conventions
status: authored
updated: 2026-09-12
sources: [`wp/wp-content/themes/lupin3rd/`]
---

# Conventions

## Language

English for every identifier and every comment: file names, class names,
function and method names, variables, constants, hook names, meta keys,
`data-*` attributes, docblocks, commit messages, this wiki.

Italian survives in exactly two places, both of them content rather than code:

1. **Public slugs and URLs** — `/tutorial/`, `/categoria/`, `/archivio/`,
   `/membri/`, `/difficolta/`. They are already indexed and they are read by
   people.
2. **Translatable strings**, which are Italian because Italian is the default
   language of the site, and which pass through `__()` regardless.

The prototype in `OD/` is written in Italian throughout. `12-glossary.md` is
the binding translation. Renaming a term after the templates are written breaks
markup and behaviour at the same time, so the glossary is settled first.

## Naming

| Thing | Convention | Example |
|---|---|---|
| Namespace | `Lupin3rd` | `namespace Lupin3rd;` |
| Class | `Capital_Snake_Case` | `Content_Types` |
| Class file | `class-<kebab>.php` | `inc/class-content-types.php` |
| Trait file | `trait-<kebab>.php` | `inc/trait-singleton.php` |
| Method, function, variable | `snake_case` | `get_generative_mark()` |
| Constant | `UPPER_SNAKE_CASE` | `Theme::VERSION` |
| Hook, filter | `l3_` prefix | `l3_before_article_body` |
| Post meta | `l3_` prefix | `l3_sample_hash` |
| User meta | `l3_` prefix | `l3_pseudonym` |
| Custom role | `l3_` prefix | `l3_member` |
| CSS class | as the prototype defines it | `.log-row`, `.mark--bars` |
| `data-` attribute | English, kebab-case | `data-when-signed-in` |
| Text domain | `lupin3rd` | `__( 'Archivio', 'lupin3rd' )` |

## File layout

One class per file. A file declares one thing and nothing else. No function
definitions floating in `functions.php`; no `require` chains outside the
autoloader.

## Code style

- PHP 8.3. Typed properties, typed parameters, typed returns, `declare` where
  it matters.
- WordPress Coding Standards, enforced by `phpcs` with the config carried over
  from `understrap-child`. `phpstan` at the strictest level that stays green.
- Escape on output, always, at the point of output: `esc_html`, `esc_attr`,
  `esc_url`, `wp_kses_post`. Never trust a value because it came from the
  database.
- Every function has a docblock that says what it does, not what it is called.

## CSS

- One compiled stylesheet: `css/lupin3rd.css`, from the single entry
  `src/sass/lupin3rd.scss`.
- One prototype section becomes one partial, in the cascade order given by
  `03-components.md`. That order is load-bearing; do not rearrange it.
- All colour and typography values come from `_tokens.scss`. A hard-coded
  colour anywhere else is a bug.
- `--radius: 0` is deliberate. Rounded corners are a regression.
- No web fonts, no `@font-face`, no icon font.

## JavaScript

- One compiled front-end bundle: `js/lupin3rd.js`, from the single entry
  `src/js/lupin3rd.js`, built from ES modules under `src/js/modules/`.
- Vanilla. No jQuery, no framework, no library from a CDN.
- Behaviour binds to `data-*` attributes only, never to a class or an id.
  Classes belong to the visual system and must stay free to change.
- Every module returns early when its hook is absent from the page.
- Every feature has a server-side fallback. A page with JavaScript disabled
  loses polish, never function.
- The editor bundle `js/lupin3rd-editor.js` is separate and is never served to
  a reader.

## Accessibility

Non-negotiable, and easy to lose when porting static HTML to PHP: skip link,
`:focus-visible`, `aria-current` on the active nav item, `aria-pressed` on
filter chips, `prefers-reduced-motion` honoured, difficulty encoded with filled
blocks rather than colour, every interactive control reachable and operable
from the keyboard.

## Quality gates

Three, in the order they earn their keep.

1. **End-to-end tests** — `tools/e2e/`, run with
   `docker compose --profile qa run --rm e2e`. They load real pages and assert
   on what comes back. This theme's defects had one thing in common: not one
   produced an error anywhere. Three of them were invisible to any static
   analysis by construction. When a defect gets through, the fix is not
   finished until a line there would have caught it.
2. **phpcs** — WordPress standard, text domain `lupin3rd`, prefixes `l3` /
   `Lupin3rd`, `PHPCompatibilityWP` at 8.2 and up. Theme file names follow the
   template hierarchy, not the class-file rule (`is_theme` is on).
3. **phpstan** — level 6 over `inc`, `blocks` and `functions.php`, with the
   WordPress stubs. `template-parts` is excluded: those files are procedural
   and take `$args` from `get_template_part()`, so the analysis says little and
   complains much. They are covered by phpcs and by the tests that load them.

`composer run-script qa` runs the lint, phpcs and phpstan in that order; the
`qa` service in compose does it without PHP on the host.

Strings: `wp i18n make-pot . languages/lupin3rd.pot --domain=lupin3rd` after
adding or changing any. Every `%s` needs a `translators:` comment above the
call — phpcs and make-pot both say so.

