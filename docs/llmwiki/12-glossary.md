---
id: glossary
title: Glossary
status: authored
updated: 2026-09-12
sources: [`OD/assets/app.js`, `OD/struttura-wordpress.html`]
---

# Glossary

The prototype is Italian, the code is English. This table is binding. Both
sides of it are contracts: the left column is what `OD/` currently emits, the
right column is what the theme must emit. Changing either after the templates
exist breaks markup and behaviour simultaneously.

## Behaviour hooks — `data-*` attributes

| Prototype | Theme | Drives |
|---|---|---|
| `data-quando-dentro` | `data-when-signed-in` | shown only to a signed-in reader |
| `data-quando-fuori` | `data-when-signed-out` | shown only to a signed-out reader |
| `data-entra` | `data-sign-in` | opens a session |
| `data-esci` | `data-sign-out` | ends a session |
| `data-sessione-aperta` | `data-session-open` | marks a page that implies a session |
| `data-apre-sessione` | `data-opens-session` | a form whose success opens a session |
| `data-termina-sessione` | `data-ends-session` | control that ends the session |
| `data-uscita` | `data-exit-url` | where to land after signing out |
| `data-carica-altri` | `data-load-more` | load-more control |
| `data-oltre` | `data-overflow` | items revealed by load-more |
| `data-non-letta` | `data-unread` | unread notification row |
| `data-segna-lette` | `data-mark-all-read` | mark-all-read control |
| `data-etichetta-letta` | `data-read-label` | label swapped in once read |
| `data-conta-non-lette` | `data-unread-count` | unread counter |
| `data-conta-dispositivi` | `data-device-count` | device counter |
| `data-conta-interventi` | `data-contribution-count` | approved-contribution counter |
| `data-conta-iscritti` | `data-member-count` | member counter |
| `data-forza` | `data-password-strength` | password strength input |
| `data-forza-eco` | `data-password-strength-echo` | strength readout |
| `data-modale-apri` | `data-modal-open` | opens a modal |
| `data-modale-chiudi` | `data-modal-close` | closes a modal |
| `data-rispondi-a` | `data-reply-to` | reply target in the discussion |
| `data-segnala` | `data-report` | report a comment |
| `data-modello-commento` | `data-comment-template` | comment template node |
| `data-commenti-per` | `data-comments-for` | comment list owner |
| `data-categoria` | `data-category` | category value on a card |
| `data-difficolta` | `data-difficulty` | difficulty value on a card |
| `data-formato` | `data-format` | format value on a card |
| `data-tipo` | `data-type` | post type value on a card |
| `data-ruolo` | `data-role` | role badge value |
| `data-sessione-riga` | `data-session-row` | device/session row |

Attributes already in English in the prototype stay as they are:
`data-theme`, `data-theme-toggle`, `data-nav-toggle`, `data-post`,
`data-article`, `data-filter-group`, `data-filter-target`, `data-filter-empty`,
`data-filter-count`, `data-reset-filters`, `data-empty-for`, `data-search-open`,
`data-search-close`, `data-search-input`, `data-search-results`,
`data-search-empty`, `data-search-echo`, `data-search-count`, `data-live-search`,
`data-keywords`, `data-value`, `data-validate`, `data-error-for`,
`data-form-note`, `data-success-note`, `data-hide-when-empty`, `data-toc`,
`data-progress`, `data-tabs`, `data-account-chip`, `data-account-panel`,
`data-inline`, `data-fallback`, `data-query`.

`data-od-id` is emitted by the design tool and must not appear in theme output.

## Meta keys and roles

| Prototype | Theme | Holds |
|---|---|---|
| `l3_pseudonimo` | `l3_pseudonym` | unique lowercase pseudonym, replaces `display_name` |
| `l3_bio` | `l3_bio` | two lines, plain text, no links |
| `l3_pgp_fpr` | `l3_pgp_fingerprint` | declared fingerprint; the desk adds the "verified" label |
| `l3_avatar_seed` | `l3_avatar_seed` | random seed generating the avatar pattern |
| `l3_interventi_ok` | `l3_approved_contributions` | approved-contribution counter; at three the account leaves the queue |
| `l3_membro` | `l3_member` | role: reads, comments, keeps private bookmarks |
| `l3_collaboratore` | `l3_contributor` | role: proposes drafts and translations, sees revisions |

## Editorial vocabulary

| Italian | English | Note |
|---|---|---|
| notizia | news item | post type `post` |
| tutorial | tutorial | post type `tutorial` |
| dossier | dossier | post type `dossier`, hierarchical |
| difficoltà | difficulty | taxonomy `difficulty`; terms stay `base` / `medio` / `avanzato` |
| occhiello | kicker | `.kicker` |
| sommario | standfirst | `.lead` |
| informativa legale | legal scope | `l3/notice` variant `legal`, mandatory on tutorials |
| nota di metodo | method note | `l3/notice` variant `method` |
| correzione | correction | `l3/notice` variant `correction`, never deleted |
| marca | generative mark | `.mark`, used instead of a photograph |
| cassetta sicura | secure drop | `/cassetta-sicura/` |
| in coda | queued | custom post status, phase 2 |
| membro | member | role `l3_member` |
| commenti | contributions | approved comments and corrections. The Italian UI said «interventi» until 16.09.2026; the code identifier stays `contributions`, because it covers corrections too and «commenti» does not |
