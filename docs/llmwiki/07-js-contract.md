---
id: js-contract
title: JavaScript contract
status: generated
updated: 2026-09-20
sources: [`src/js/modules/*.js`, `*.php`, `template-parts/**/*.php`]
---

# JavaScript contract

Markup and behaviour are bound exclusively through `data-*` attributes. No behaviour may key off a class name, an id or a tag: classes belong to the visual system and must stay free to change.

> **Generated file — do not edit by hand.**
> Regenerate with `python3 docs/llmwiki/tools/sync_from_theme.py` after changing the source it reads.


The bundle is built from 12 ES modules under `src/js/modules/`, compiled into one file. Every module must return early when its hook is absent from the page.

## The two columns are the contract

**Emitted** counts the attribute in the PHP that prints it. **Read** counts it in the modules that act on it. Both numbers matter, and so does each being zero:

- read but never emitted — the module is waiting for markup nobody prints;
- emitted but never read — either a CSS or server-side hook, which is fine, or a behaviour that was removed and left its handle behind.

**Read `0` is not proof that nothing reads it.** This table counts literal occurrences, and a module that builds the name at runtime — `getAttribute('data-level-' + n)`, or `dataset.copiedLabel` for `data-copied-label` — never spells it out. Check the module before deleting an attribute on the strength of a zero.

| Attribute | Emitted | Read |
|---|---|---|
| `data-error-for` | 9 | 1 |
| `data-when-signed-in` | 6 | 1 |
| `data-filter-scope` | 4 | 2 |
| `data-validate` | 5 | 1 |
| `data-filter-group` | 2 | 3 |
| `data-tab-heading` | 4 | 1 |
| `data-when-signed-out` | 4 | 1 |
| `data-account-name` | 2 | 1 |
| `data-account-public` | 2 | 1 |
| `data-account-signout` | 2 | 1 |
| `data-close-confirm` | 1 | 2 |
| `data-consent-open` | 2 | 1 |
| `data-copy` | 2 | 1 |
| `data-filter-target` | 2 | 1 |
| `data-nav-close` | 1 | 2 |
| `data-password-strength` | 2 | 1 |
| `data-password-strength-echo` | 2 | 1 |
| `data-post` | 2 | 1 |
| `data-tabs` | 2 | 1 |
| `data-theme` | 1 | 2 |
| `data-theme-toggle` | 1 | 2 |
| `data-account-chip` | 1 | 1 |
| `data-account-panel` | 1 | 1 |
| `data-article` | 2 | 0 |
| `data-close-account` | 1 | 1 |
| `data-close-submit` | 1 | 1 |
| `data-copied-label` | 2 | 0 |
| `data-embed` | 1 | 1 |
| `data-embed-gate` | 1 | 1 |
| `data-embed-show` | 1 | 1 |
| `data-failed-label` | 2 | 0 |
| `data-filter-empty` | 1 | 1 |
| `data-level-0` | 2 | 0 |
| `data-level-1` | 2 | 0 |
| `data-level-2` | 2 | 0 |
| `data-level-3` | 2 | 0 |
| `data-level-4` | 2 | 0 |
| `data-live-search` | 1 | 1 |
| `data-nav-scrim` | 1 | 1 |
| `data-nav-toggle` | 1 | 1 |
| `data-progress` | 1 | 1 |
| `data-reset-filters` | 1 | 1 |
| `data-search-close` | 1 | 1 |
| `data-search-empty` | 1 | 1 |
| `data-search-input` | 1 | 1 |
| `data-search-open` | 1 | 1 |
| `data-search-overlay` | 1 | 1 |
| `data-search-results` | 1 | 1 |
| `data-toc` | 1 | 1 |
| `data-value` | 2 | 0 |
| `data-category` | 1 | 0 |
| `data-difficulty` | 1 | 0 |
| `data-embed-host` | 1 | 0 |
| `data-keywords` | 1 | 0 |
| `data-role` | 1 | 0 |
| `data-section` | 1 | 0 |
| `data-type` | 1 | 0 |

## Modules

| Module | Attributes it reads |
|---|---|
| `account-close.js` | `data-close-account`, `data-close-confirm`, `data-close-submit` |
| `code-copy.js` | `data-copy` |
| `consent.js` | `data-consent-open`, `data-embed`, `data-embed-gate`, `data-embed-show` |
| `form-validation.js` | `data-error-for`, `data-validate` |
| `mobile-nav.js` | `data-nav-close`, `data-nav-scrim`, `data-nav-toggle` |
| `password-strength.js` | `data-password-strength`, `data-password-strength-echo` |
| `search-overlay.js` | `data-live-search`, `data-search-close`, `data-search-empty`, `data-search-input`, `data-search-open`, `data-search-overlay`, `data-search-results` |
| `session.js` | `data-account-chip`, `data-account-name`, `data-account-panel`, `data-account-public`, `data-account-signout`, `data-when-signed-in`, `data-when-signed-out` |
| `table-of-contents.js` | `data-progress`, `data-toc` |
| `tabs.js` | `data-tab-heading`, `data-tabs` |
| `taxonomy-filters.js` | `data-filter-empty`, `data-filter-group`, `data-filter-scope`, `data-filter-target`, `data-post`, `data-reset-filters` |
| `theme-switch.js` | `data-theme`, `data-theme-toggle` |

## Visibility is the stylesheet's business

A module must toggle the state the design system defines, not invent one. The search overlay is `.overlay` / `.overlay.is-open`; the drawer is `.topnav nav.is-drawer.is-open`, defined inside a media query. Toggling the `.hidden` utility instead does nothing visible, because it is `display: none !important` and beats the open state — and nothing errors, so the feature simply never works.

The `hidden` attribute is a separate concern and is kept in step: it takes a closed panel's links out of the tab order. Remove it before calling `focus()`, because an unrendered element cannot take focus.

Which of the two an element ships with is not a style choice. A **control** that needs a script ships `hidden` and the module reveals it, so it is never an inert button. A **panel** ships visible, so a reader without the bundle still reaches its contents.

## Naming

Every attribute here is English, like every other identifier in this theme. Public slugs and URLs stay Italian, because they are content. The prototype this theme was ported from named these attributes in Italian; the translation table in `12-glossary.md` recorded the mapping and is now history rather than a live contract.
