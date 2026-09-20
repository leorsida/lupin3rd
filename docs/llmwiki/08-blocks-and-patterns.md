---
id: blocks-and-patterns
title: Blocks and patterns
status: authored
updated: 2026-09-12
sources: [`blocks/`, `inc/class-blocks.php`, `inc/class-patterns.php`]
---

# Blocks and patterns

Two proprietary blocks and one pattern. Both blocks render server-side in PHP;
neither ships a front-end script.

## `l3/code`

A code block with a header, a file name, a copy button and optional line
numbers.

- Syntax colouring is **monochrome and server-side**. No highlighting library,
  on either side. The prototype uses three token classes only: `.tok-kw`,
  `.tok-str`, `.tok-com`.
- Markup: `.codeblock` > `.codeblock-head` (file name + `.copy-btn`) > `pre`.
- Attributes: `content` (string), `filename` (string), `language` (string),
  `showLineNumbers` (boolean).
- The copy button is wired by the `code-copy` JS module through `.copy-btn`.
  With JavaScript disabled the block still renders and reads correctly.

## `l3/notice`

A callout with three variants.

| Variant | Meaning | Class |
|---|---|---|
| `method` | note on method | `.callout` |
| `legal` | legal scope | `.callout.callout--legal` |
| `correction` | published correction | `.callout` |

**Editorial constraint.** A `tutorial` that contains no `l3/notice` with
variant `legal` cannot be published. Enforcement happens in two places, because
one is not enough:

1. In the editor, so the author sees why the publish button is refusing.
2. On the server, in `Blocks`, via `rest_pre_insert_tutorial` and
   `wp_insert_post_data`, so that WP-CLI, an import and a REST client are
   subject to the same rule.

A correction notice, once published, is never removed. It stays at the foot of
the piece.

## Pattern `l3/tutorial-header`

Kicker, title, standfirst, metadata row and opening figure, already laid out.
Registered from PHP by `Patterns`, so that the editorial desk starts from the
right structure instead of rebuilding it each time.

## Reading block attributes in a filter

Block attributes are JSON inside the delimiter comment, so anything that
inspects them has to receive the content **unslashed**. This is not a detail:

- `wp_insert_post_data` hands over **slashed** data. Parsed as it arrives, the
  delimiter reads `{\"variant\":\"legal\"}`, which is not valid JSON, so
  `parse_blocks()` returns no attributes at all. Every attribute looks absent.
  Call `wp_unslash()` first.
- `rest_pre_insert_{post_type}` hands over the prepared object **unslashed** —
  REST slashes later, on the way into `wp_insert_post()`. Pass it through.

This cost an afternoon once. The symptom was a tutorial that carried a perfectly
good legal-scope notice and was demoted to a draft anyway, with no error
anywhere: the gate did exactly what it was told, on content it could not read.

`Blocks::has_legal_notice()` documents the expectation in its docblock and the
regression test covers both shapes. If you add another filter that reads block
attributes, check which of the two it is before trusting the parse.

## Editor bundle

Block edit scripts and the document-settings panels for the meta fields in
`05-content-model.md` compile to a **separate** editor bundle,
`js/lupin3rd-editor.js`. This does not breach the one-bundle rule, which
governs the front end: no editor code is ever served to a reader.
