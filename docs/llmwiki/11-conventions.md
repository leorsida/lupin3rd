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

   Not a rule about language, though it reads like one: a slug is Italian
   because that is the word the desk would use out loud. Where the word the
   desk uses is English, the slug is English — `/community/` since 20.09.2026,
   by the desk's own call (D-036).
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
| Hook, filter | `lupin3rd_` prefix | `lupin3rd_current_language` |
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
- Un reset su un elemento (`p`, `ul`, `ol`) dentro un blocco che ha una regola
  di ritmo (`> * + *`) va scritto in `:where()`. `.prose p { margin-block: 0 }`
  vale (0,1,1) e batte `.prose > * + *` che vale (0,1,0): il reset vince
  sempre, l'ordine non c'entra, e il ritmo muore in silenzio. Con
  `.prose :where(p, ul, ol)` il reset scende a (0,1,0) e la regola scritta
  dopo torna a valere. Chi imposta il proprio margine (`h2`, `blockquote`)
  resta più specifico e se lo tiene.

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
- Una misura che il CSS già conosce — l'altezza della testata, quella della
  barra admin — in JavaScript si **misura**, non si riscrive come costante.
  `table-of-contents.js` aveva `window.scrollY + 120` al posto del bordo
  inferiore della testata: da sloggati i 92px della testata ci stavano dentro,
  da loggati la barra admin portava il totale a 124 e l'indice evidenziava la
  voce precedente a quella cliccata. Un difetto visibile solo a chi ha una
  sessione aperta, cioè solo a noi. Ora legge
  `.topnav.getBoundingClientRect().bottom`, che la barra admin la include già
  perché la testata è appiccicata sotto di essa.

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
2. **phpcs** — WordPress standard, text domain `lupin3rd`, prefixes
   `lupin3rd` / `Lupin3rd` (see below), `PHPCompatibilityWP` at 8.2 and up.
   Theme file names follow the template hierarchy, not the class-file rule
   (`is_theme` is on). `tools/` is excluded: those are command-line helpers
   that run with no WordPress loaded, so the rules about escaping output and
   using `WP_Filesystem` do not apply to them.
3. **phpstan** — level 6 over `inc`, `blocks` and `functions.php`, with the
   WordPress stubs. `template-parts` is excluded: those files are procedural
   and take `$args` from `get_template_part()`, so the analysis says little and
   complains much. They are covered by phpcs and by the tests that load them.

`composer run-script qa` runs the lint, phpcs and phpstan in that order; the
`qa` service in compose does it without PHP on the host. All three are green as
of 13.09.2026, and `composer.lock` pins the versions that made them green — a
gate that passes with one set of tools and fails with another is not a gate.

The `qa` service passes its argument to `sh -c`, so a different command goes in
as a single string: `docker compose --profile qa run --rm qa "composer phpcbf"`.

Three rules are turned off, each with the reason written in `phpcs.xml.dist`
rather than here, because the place to argue with a rule is where it is
disabled: the variable-prefix check, the short-ternary ban, and the
prefixed-hook check in the three files that apply core's `the_content` filter
rather than inventing a hook.

Strings: `wp i18n make-pot . languages/lupin3rd.pot --domain=lupin3rd` after
adding or changing any. Every `%s` needs a `translators:` comment above the
call — phpcs and make-pot both say so.

## Two prefixes, and why

`l3_` is the theme's prefix for everything that is a **contract**: meta keys
(`l3_estimated_time`), query variables (`l3_format`, which appears in published
URLs), template variables, data attributes. Those are written into the database
and into readers' bookmarks; they do not change to please a tool.

`lupin3rd_` is the prefix for what genuinely lives in the global namespace and
could collide with somebody else's code: the hooks this theme invokes —
`lupin3rd_allow_anonymous_rest`, `lupin3rd_colliding_body_classes`,
`lupin3rd_current_language`.

The reason for the split is that phpcs rejects any prefix shorter than four
characters, and it is right to: `l3_` is not unique enough for a global hook.
It is unique enough for a meta key on this site's own posts.

A template file declares no functions. `404.php` used to declare one; the
Levenshtein search now lives in `Template_Tags::tag_suggestions()`, where
template logic belongs and where phpcs stops asking about the global namespace.

## Traps in the tools themselves

Found by running them, worth not rediscovering.

**phpcbf plays ping-pong on comment rules.** It rewrote two section banners to
satisfy `Squiz.Commenting.BlockComment.NoNewLine` and produced
`CloserSameLine`, which it then reported as an error. A banner is not a doc
comment: `// --- Name ---` ends the argument.

**`items_wrap` is a `sprintf()` format string.** Anything appended to it has
its per cent signs read as format specifiers, and one percent-encoded URL in
the appended markup (`redirect_to=http%3A%2F%2F…`) takes the whole page down
with «Unknown format specifier». `str_replace( '%', '%%', $markup )` before
concatenating. This one is not silent — it is a fatal — but it is invisible
until a page is loaded, which is what the end-to-end suite is for.

**A `backdrop-filter` makes an element the containing block for every
`position: fixed` descendant.** So does `filter`, `transform`, `perspective`,
`will-change` and `contain: paint`. The sticky bar carried
`backdrop-filter: blur(14px)`, and the drawer inside it — `position: fixed;
inset: 0 0 0 auto` — would have been positioned against the bar and come out
sixty pixels tall. Nothing errors. The cure is to move the filter onto a
pseudo-element (`::before` with `inset: 0; z-index: -1`), which looks identical
and leaves the element itself out of the way.

**`setcookie()` encodes the value; `setrawcookie()` is the one that does not.**
A value url-encoded before being handed to `setcookie()` goes out encoded
twice. `$_COOKIE` decodes once, so PHP never notices; a script decodes once and
gets a string that still looks like `%7B%22name`, fails to parse it, and does
nothing — no error anywhere. It also writes a space as `+`, which
`decodeURIComponent` does not undo. Hand it the plain value, and put the `+`
back on the way in.

**A block comment always wants a blank line above it — inside an array too.**
`Squiz.Commenting.BlockComment.NoEmptyLineBefore` fires wherever a `/* … */`
follows a line of code directly: after the last `add_filter()` in
`register_hooks()`, between two elements of an array literal. It is the single
most frequent finding in this theme, and phpcbf does not fix it. Write the
blank line while writing the comment.

**A `phpcs:ignore` on an array element changes how that element is aligned.**
The trailing comment lengthens the line, and `MultipleStatementAlignment` then
measures the block against a width that is no longer there; five warnings
arrive at once for keys that were aligned before the ignore was added. Align
the block after adding the ignore, not before.

**A `phpcs:ignore` between a docblock and a signature detaches the docblock.**
The comment becomes the thing above the function, the documentation stops
belonging to it, and phpcs reports a missing doc comment that was there all
along. The ignore goes on the signature line.

**The WordPress stubs must be loaded once.** `szepeviktor/phpstan-wordpress`
already lists them under `bootstrapFiles`; listing them again under `scanFiles`
parses ten megabytes of stubs a second time **in every parallel worker**, and a
512M limit disappears on a theme of sixty files. The number of workers is a
memory decision, not a speed one.

**What comes back from `apply_filters()` is `mixed`, whatever the docblock
says.** PHPStan reads the type of the value passed *in* and concludes that the
value coming *out* has it too, so a guard on the return reads as «always true».
It is not: the return comes from somebody else's callback and can be anything.
Correct the inference with an inline `@var mixed` rather than silencing the
check — the guard is the reason the code is safe, and the comment should say so.

**Two `instanceof` guards that look identical are opposite mistakes.** PHPStan
reports both as «always true», and the right answer differs:

- After a core call the stubs type definitely — `wp_get_current_user()`,
  `get_posts()` without `fields` — the guard is dead weight. Delete it and keep
  whatever check actually carries meaning (`->exists()`, a count).
- On a value that arrived through a hook — a comment passed to a
  `transition_comment_status` callback, a user passed to `show_user_profile` —
  the guard is the only thing making the rest of the method safe to read. There
  it is the **docblock** that is wrong: it promised more than the hook
  guarantees. Widen the parameter to `mixed` and keep the guard.

Reading the error alone cannot tell the two apart. Ask where the value came
from.

**A stub can be narrower than the function it describes.** PHPStan believed
`paginate_links( [ 'type' => 'array' ] )` always returns a list, so it called
the guard redundant; WordPress's own docblock says `string|string[]|void`, and
the function really does fall off its end when there is nothing to paginate.
Removing the guard would have traded a warning for a `count( null )` on any
page with a single screen of results. When an analyser and the documentation
disagree, check which one has read the source — then keep the guard and write
down why.

**`wp_setup_nav_menu_item()` decorates a `WP_Post` at runtime**: `url`,
`target`, `title` are not declared on the class and never will be.
`universalObjectCratesClasses` is the honest answer; inventing a type is not.

## Core hook priorities are not a contract

Bracketing an action — adding a filter early, removing it late — is a sound
technique, and it broke the first time it was used here because the closing
priority was written from memory. `wp_admin_bar_my_account_item()` is hooked at
**9991** in WordPress 7.1, not at the single-digit priority it once had; a
bracket that closed at 100 removed the filter before the node that needed it
had been built. The half of the job that happened at priority 0 worked, which
is the worst kind of failure: it looks like the mechanism is wrong rather than
the number.

Two rules follow.

- **Read the priority out of `wp-includes/` before relying on it.** For the
  admin bar that is `WP_Admin_Bar::add_menus()`.
- **Prefer a later hook to a larger number.** `wp_before_admin_bar_render` fires
  once `admin_bar_menu` has finished, whatever priorities core uses inside it.
  «After that action» is a fact; «priority 100 is late enough» is a guess that
  a core release can invalidate silently.

## A custom property is substituted where it is declared

```scss
:root        { --admin-bar: 0px;  --stick: calc(var(--admin-bar) + 92px); }
body.admin-bar { --admin-bar: 32px; }   /* --stick does not follow */
```

`--stick` computes on `:root`, where `--admin-bar` is `0px`, and the result —
`92px` — is what inherits into `body.admin-bar`. Redefining `--admin-bar`
lower down changes nothing, because `--stick` was already resolved.

So a token that depends on another token is only safe when both are declared
on the same element and neither is ever overridden. Where an override is the
whole point, keep the pieces separate and combine them **at the point of use**,
on the element that has the overridden value in scope:

```scss
top: calc(var(--admin-bar) + var(--header-height));
```

This costs a repeated `calc()` at two or three call sites and is worth it: the
failure mode is a layout that is quietly correct for a signed-out reader and
quietly wrong for everybody else, which is the hardest kind to notice.

## A long comment can read as commented-out code

`Squiz.PHP.CommentedOutCode` scores a `/* … */` block on how much of it parses
as PHP and warns past roughly 40%. Prose trips it: the score goes up with
underscored names (`wp_comments`, `pre_comment_user_ip`), hyphenated words,
semicolons and parentheses, and it is computed over the **whole block**, so
three paragraphs that each pass on their own can fail together. Two of the
paragraphs that produced a 55% here scored 0% when checked alone.

Docblocks (`/** … */`) are exempt, which is the tell: the sniff exists to catch
code parked in a comment, and code is not usually parked in a docblock.

The fix is not to reword around the sniff. A fifteen-line argument above two
`add_filter()` calls is a sign the argument belongs in `docs/llmwiki/`, where it
can be found by somebody who is not already reading that file — leave three
lines and a path at the call site. That is what happened to the comment above
`pre_comment_user_ip`, and the wiki already carried the same reasoning twice.

Checking it locally needs no WPCS:

```sh
php phpcs.phar --standard=Squiz --sniffs=Squiz.PHP.CommentedOutCode inc/
```

## A hook hands you `mixed`, and the docblock has to say so

PHPStan's `instanceof.alwaysTrue` on a callback almost always means the same
mistake, and it is not the `instanceof`:

```php
/** @param \WP_User_Query $query */          // the lie
public function skip_closed_accounts( $query ): void {
    if ( ! $query instanceof \WP_User_Query ) {  // reported as always true
```

WordPress passes a callback whatever the previous filter returned, so the
runtime guard is right and the docblock is wrong. Widen it to `mixed` and the
guard becomes meaningful to the analyser as well as to the reader. Deleting
the guard — the other way to silence the error — removes a real defence on the
strength of a comment somebody typed.

The exception is a value that comes from a **core call**, not from a hook:
there the type is known, and the guard really is dead weight.

The same widening fixes `booleanOr.alwaysFalse` on a two-part guard, and
`missingType.return` wants `@return mixed` on a callback that passes back
whatever it could not handle.
