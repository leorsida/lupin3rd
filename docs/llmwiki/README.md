# llmwiki

A knowledge base written for machines first and humans second.

Every page is short, declarative and self-contained. An assistant that reads
`00-index.md` plus the two or three pages it names should be able to write
correct code for this theme without opening the prototype, the stylesheet or
the WordPress core.

## Rules for this wiki

1. **English only.** Page text, headings, identifiers, examples. The prototype
   in `OD/` is written in Italian; `12-glossary.md` is the binding translation
   between the two and is the only place where Italian appears on purpose.
2. **One fact per line.** No narrative. If a paragraph is needed to explain a
   rule, the rule is probably wrong.
3. **State the constraint, not the preference.** "Rounded corners are a
   regression" is useful. "We prefer sharp corners" is not.
4. **Front matter is mandatory** on every page: `id`, `title`, `status`,
   `updated`, `sources`.
5. **`status: generated` means do not edit.** Those pages are rebuilt by
   `tools/sync_from_theme.py` and hand edits are lost on the next run.
6. **A page that contradicts the code is a bug.** Fix the page in the same
   commit that changes the code.

## Generated pages

| Page | Derived from |
|---|---|
| `02-design-tokens.md` | `src/sass/partials/_tokens.scss` |
| `03-components.md` | `src/sass/lupin3rd.scss`, `src/sass/partials/*.scss` |
| `07-js-contract.md` | `src/js/modules/*.js`, the PHP templates |

Regenerate with:

```
python3 docs/llmwiki/tools/sync_from_theme.py
```

Run it after changing a token, a partial or a `data-*` hook, and commit the
result. It reads the theme, so it cannot disagree with the theme — which is
the whole reason these three pages are generated rather than written.

## Reading order for a new contributor or agent

`00-index.md` -> `01-project.md` -> `11-conventions.md` -> the page for the
task at hand.
