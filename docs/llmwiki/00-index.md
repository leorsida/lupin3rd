---
id: index
title: Index
status: authored
updated: 2026-09-19
sources: [`OD/`, `wp/wp-content/themes/lupin3rd/`]
---

# Index

Knowledge base for the `lupin3rd` WordPress theme. Read `01-project.md` and
`11-conventions.md` before writing any code.

| Page | Read it when you need to know |
|---|---|
| `01-project.md` | what the site is, who it is for, what it refuses to do |
| `02-design-tokens.md` | a colour, a font, a size, a spacing value |
| `03-components.md` | which CSS class to emit, and which SCSS partial owns it |
| `04-template-map.md` | which PHP file renders which screen, at which URL |
| `05-content-model.md` | post types, taxonomies, meta fields, permalinks |
| `06-php-architecture.md` | how the singleton bootstrap and the modules fit together |
| `07-js-contract.md` | which `data-*` attribute drives which behaviour |
| `08-blocks-and-patterns.md` | the proprietary blocks and the editorial constraints |
| `09-seo-and-geo.md` | metadata, JSON-LD, feeds, generative-engine surface |
| `10-security-and-privacy.md` | what the theme closes off, and why |
| `11-conventions.md` | naming, language, file layout, code style |
| `12-glossary.md` | Italian prototype term to English code identifier |
| `13-decisions.md` | why the theme is built the way it is |
| `14-open-questions.md` | what is still undecided, and who decides it |
| `15-local-environment.md` | how to run the project, and what local success does not prove |
| `16-emails.md` | the three messages the theme sends, and where to change their wording |
| `17-privacy.md` | the consent notice, the embed gate, and what keeps the privacy page true |

## Fixed points

- Design source of truth: `OD/` (prototype, 17 pages IT + 17 EN).
- Architecture source of truth: `OD/struttura-wordpress.html`.
- Theme location: `wp/wp-content/themes/lupin3rd/`.
- The theme is standalone. There is no parent theme and no Bootstrap.
- One compiled stylesheet, one compiled front-end bundle. No exceptions.
- Development plan and phasing: `docs/PIANO-DI-SVILUPPO.md`.
- Local stack: `compose.yaml` at the project root, documented in `docker/README.md`.
