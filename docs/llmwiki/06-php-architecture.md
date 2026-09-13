---
id: php-architecture
title: PHP architecture
status: authored
updated: 2026-09-12
sources: [`wp/wp-content/themes/lupin3rd/`]
---

# PHP architecture

The theme follows a strict singleton design: one bootstrap object, one instance
of every module, one place where each hook is registered.

## Bootstrap

`functions.php` contains no logic. It registers an autoloader and starts the
theme:

```php
<?php
/**
 * Theme bootstrap.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/class-autoloader.php';

Lupin3rd\Autoloader::instance()->register();
Lupin3rd\Theme::instance()->boot();
```

## The singleton trait

```php
<?php
/**
 * Shared singleton behaviour.
 *
 * A class using this trait is instantiated exactly once. Hooks are registered
 * in register_hooks(), which the constructor calls, so that a class can never
 * attach its hooks twice.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

trait Singleton {

	/**
	 * The single instance of the class.
	 *
	 * @var static|null
	 */
	private static ?self $instance = null;

	/**
	 * Return the single instance, creating it on first call.
	 */
	public static function instance(): static {
		return static::$instance ??= new static();
	}

	/**
	 * Private constructor: instances are created through instance() only.
	 */
	private function __construct() {
		$this->register_hooks();
	}

	/**
	 * Cloning a singleton would defeat its purpose.
	 */
	private function __clone() {}

	/**
	 * Unserialising a singleton would defeat its purpose.
	 *
	 * @throws \LogicException Always.
	 */
	public function __wakeup(): void {
		throw new \LogicException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Attach the class's WordPress hooks. Called once, from the constructor.
	 */
	abstract protected function register_hooks(): void;
}
```

## The registry

`Theme` is the only class that knows the module list. Boot order is the array
order and is significant: `Setup` must run before `Content_Types`, which must
run before `Meta_Fields`.

```php
private const MODULES = [
	Setup::class,
	Assets::class,
	I18n::class,
	Content_Types::class,
	Meta_Fields::class,
	Blocks::class,
	Patterns::class,
	Marks::class,
	Template_Tags::class,
	Archive_Filters::class,
	Search::class,
	Seo::class,
	Schema::class,
	Geo::class,
	Feeds::class,
	Security::class,
	Performance::class,
];
```

## Module responsibilities

| Class | File | Owns |
|---|---|---|
| `Theme` | `inc/class-theme.php` | module registry, boot order |
| `Setup` | `inc/class-setup.php` | theme supports, menus, image sizes, sidebars |
| `Assets` | `inc/class-assets.php` | the one stylesheet, the one bundle, the inline theme script |
| `I18n` | `inc/class-i18n.php` | text domain, date formats, language hooks |
| `Content_Types` | `inc/class-content-types.php` | post types, taxonomies, rewrites |
| `Meta_Fields` | `inc/class-meta-fields.php` | `register_post_meta`, editor panels |
| `Blocks` | `inc/class-blocks.php` | `l3/code`, `l3/notice` |
| `Patterns` | `inc/class-patterns.php` | block patterns |
| `Marks` | `inc/class-marks.php` | generative marks, avatar patterns, OG image rendering |
| `Template_Tags` | `inc/class-template-tags.php` | every helper a template calls |
| `Comment_Walker` | `inc/class-comment-walker.php` | comment markup per the prototype |
| `Archive_Filters` | `inc/class-archive-filters.php` | taxonomy filters, server fallback |
| `Search` | `inc/class-search.php` | search query, overlay endpoint |
| `Seo` | `inc/class-seo.php` | title, description, canonical, Open Graph, robots |
| `Schema` | `inc/class-schema.php` | JSON-LD |
| `Geo` | `inc/class-geo.php` | `llms.txt`, citable summaries, crawler policy |
| `Feeds` | `inc/class-feeds.php` | per-taxonomy feeds, author feeds off |
| `Security` | `inc/class-security.php` | closes the surfaces listed in `10-security-and-privacy.md` |
| `Performance` | `inc/class-performance.php` | head cleanup, preload, cache friendliness |

`Comment_Walker` extends `Walker_Comment` and is therefore not a singleton: it
is instantiated per call by `wp_list_comments()`.

## Known trade-off

Singletons hide dependencies and cannot be replaced with a test double. The
mitigation is discipline, not cleverness: modules stay thin and hook-only, and
any logic worth testing lives in a static method that takes its inputs as
arguments and returns a value.

## Capabilities on a private post type

`register_post_type( …, 'capabilities' => … )` takes **primitive** capabilities
only when `map_meta_cap` is on: `edit_posts`, `delete_others_posts`,
`publish_posts` and the rest. WordPress derives the per-post ones —
`edit_post`, `read_post`, `delete_post` — from those.

Naming a per-post capability in that array makes core check it without a post,
which raises a `_doing_it_wrong` notice. On a page that only reads, the notice
is noise; on a request that ends in `wp_redirect()`, the notice is output, the
headers are already sent and **the redirect never happens** — the reader is left
looking at a warning instead of the page they were going to. `l3_subscriber`
did exactly that.

