---
id: js-contract
title: JavaScript contract
status: generated
updated: 2026-09-13
sources: [`OD/assets/app.js`, `OD/*.html`]
---

# JavaScript contract

Markup and behaviour are bound exclusively through `data-*` attributes. No behaviour may key off a class name, an id or a tag: classes belong to the visual system and must stay free to change. This page is the full list of hooks the prototype defines.

> **Generated file — do not edit by hand.**
> Regenerate with `python3 docs/llmwiki/tools/sync_from_od.py` after changing the prototype.


The prototype ships 3 IIFE blocks in a single file. The theme compiles the same behaviour from ES modules under `src/js/modules/` into one bundle. Every module must return early when its hook is absent from the page.

## Attribute inventory

| Attribute | Occurrences |
|---|---|
| `data-keywords` | 218 |
| `data-theme` | 215 |
| `data-filter-group` | 138 |
| `data-quando-dentro` | 134 |
| `data-non-letta` | 132 |
| `data-quando-fuori` | 132 |
| `data-filter-target` | 107 |
| `data-post` | 103 |
| `data-theme-toggle` | 92 |
| `data-etichetta-letta` | 72 |
| `data-oltre` | 66 |
| `data-search-echo` | 66 |
| `data-esci` | 65 |
| `data-live-search` | 64 |
| `data-value` | 64 |
| `data-account-chip` | 61 |
| `data-account-panel` | 61 |
| `data-error-for` | 61 |
| `data-nav-toggle` | 61 |
| `data-search-close` | 61 |
| `data-search-empty` | 61 |
| `data-search-input` | 61 |
| `data-search-open` | 61 |
| `data-search-results` | 61 |
| `data-inline` | 60 |
| `data-validate` | 49 |
| `data-filter-empty` | 47 |
| `data-form-note` | 47 |
| `data-reset-filters` | 47 |
| `data-rispondi-a` | 41 |
| `data-modale-chiudi` | 39 |
| `data-segnala` | 39 |
| `data-categoria` | 38 |
| `data-hide-when-empty` | 37 |
| `data-tabs` | 37 |
| `data-article` | 35 |
| `data-commenti-per` | 35 |
| `data-conta-interventi` | 35 |
| `data-conta-non-lette` | 35 |
| `data-modale-apri` | 35 |
| `data-modello-commento` | 35 |
| `data-progress` | 35 |
| `data-termina-sessione` | 35 |
| `data-toc` | 35 |
| `data-carica-altri` | 33 |
| `data-conta-dispositivi` | 33 |
| `data-forza` | 33 |
| `data-forza-eco` | 33 |
| `data-segna-lette` | 33 |
| `data-sessione-aperta` | 33 |
| `data-uscita` | 33 |
| `data-entra` | 31 |
| `data-difficolta` | 18 |
| `data-formato` | 18 |
| `data-success-note` | 18 |
| `data-tipo` | 18 |
| `data-empty-for` | 16 |
| `data-ruolo` | 16 |
| `data-filter-count` | 14 |
| `data-arch-count` | 8 |
| `data-apre-sessione` | 4 |
| `data-conta-iscritti` | 4 |
| `data-crypto-count` | 4 |
| `data-fallback` | 4 |
| `data-iscrizione` | 4 |
| `data-news-count` | 4 |
| `data-search-count` | 4 |
| `data-liv0` | 2 |
| `data-liv1` | 2 |
| `data-liv2` | 2 |
| `data-liv3` | 2 |
| `data-liv4` | 2 |
| `data-query` | 2 |

## Visibility is the stylesheet's business

A module must toggle the state the design system defines, not invent one. The search overlay is `.overlay` / `.overlay.is-open`; the collapsed navigation is `.topnav nav.is-open`, defined inside a media query. Toggling the `.hidden` utility instead does nothing visible, because it is `display: none !important` and beats the open state — and nothing errors, so the feature simply never works.

The `hidden` attribute is a separate concern and is kept in step: it takes a closed panel's links out of the tab order. Remove it before calling `focus()`, because an unrendered element cannot take focus.

## Controls that need a script ship hidden, panels do not

A tab strip is the clearest case. The server renders **every** panel open, each
with its own heading, and the strip itself carrying the `hidden` attribute.
Without the bundle the page is a sequence of labelled lists: everything the
reader came for is on screen, and there is no control that does nothing. With
the bundle, `tabs.js` reveals the strip, folds the per-panel headings away with
`[data-tab-heading]` and leaves one panel showing.

The inverse — shipping panels with `hidden` and revealing them from script — is
the shape to avoid: it hides content behind a file that may not arrive, and it
fails silently when it does not.

## Naming

The prototype names these attributes in Italian (`data-quando-dentro`, `data-carica-altri`). The theme requires English identifiers throughout. The translation table lives in `12-glossary.md` and is binding: renaming one of these after the templates are written is a breaking change on both sides at once.
