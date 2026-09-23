<?php
/**
 * A Markdown twin of every published piece.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Serves `<permalink>.md` and builds the Markdown it answers with.
 *
 * The text is built from the BLOCKS, not from the rendered page. `parse_blocks()`
 * hands back the tree before it becomes HTML, so nothing has to be parsed back
 * out of markup, our own two blocks translate better than any generic converter
 * could — `l3/code` already carries its language — and the theme gains no
 * dependency to do it. See docs/llmwiki/13-decisions.md, D-038.
 *
 * Two consequences of building from blocks rather than from `the_content`, both
 * wanted:
 *
 * - the comments are not here, because they were never in the content;
 * - the embed gate is not here either, because there is no iframe to gate. An
 *   embed becomes a link, which is what it should be in a text file.
 */
final class Markdown {

	use Singleton;

	/**
	 * The query variable the rewrite rule fills.
	 */
	public const QUERY_VAR = 'l3_md';

	/**
	 * Option holding the version of the rewrite rules installed.
	 */
	private const RULES_OPTION = 'l3_md_rules_version';

	/**
	 * Raise this in the same commit that changes the rules below.
	 *
	 * The server then flushes once, by itself, on the first request after the
	 * deploy. Asking a human to re-save the permalinks is a step that works
	 * until the one time somebody forgets.
	 */
	private const RULES_VERSION = 1;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'add_rules' ) );
		add_action( 'init', array( $this, 'flush_once' ), 20 );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );

		// Priority 0: before anything decides to render a page.
		add_action( 'template_redirect', array( $this, 'serve' ), 0 );
	}

	/**
	 * One rule, for every permalink shape there is.
	 *
	 * `/linee-guida.md`, `/2026/09/articolo.md` and `/tutorial/x.md` all match
	 * it, and the path is resolved afterwards by `url_to_postid()`. A rule per
	 * post type would have to know how each type's permalink is built, which is
	 * knowledge that ages badly and silently.
	 */
	public function add_rules(): void {
		add_rewrite_rule( '^(.+?)\.md/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	/**
	 * Flush the rewrite rules once per version, never on every request.
	 */
	public function flush_once(): void {
		if ( (int) get_option( self::RULES_OPTION ) === self::RULES_VERSION ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::RULES_OPTION, self::RULES_VERSION, true );
	}

	/**
	 * Declare the query variable, or WordPress drops it.
	 *
	 * @param array<int, string> $vars Public query variables.
	 * @return array<int, string>
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * Answer a `.md` request, or let the page render.
	 */
	public function serve(): void {
		$path = (string) get_query_var( self::QUERY_VAR );

		if ( '' === $path ) {
			return;
		}

		$post = self::resolve( $path );

		if ( ! $post instanceof \WP_Post || ! self::is_public( $post ) ) {
			/*
			 * A draft, a private page, one behind a password: the answer is the
			 * one a reader gets for a URL that does not exist. Saying «that
			 * exists but you cannot have it» would be telling somebody what we
			 * are working on.
			 */
			global $wp_query;

			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			include get_404_template();
			exit;
		}

		$markdown = self::of( $post );

		header( 'Content-Type: text/markdown; charset=utf-8' );

		// A second copy of the same piece: fetchable, not indexable.
		header( 'X-Robots-Tag: noindex' );
		header( 'Link: <' . esc_url_raw( (string) get_permalink( $post ) ) . '>; rel="canonical"' );

		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text, and escaping it would corrupt it.
		exit;
	}

	/**
	 * The address of a post's Markdown twin, or an empty string.
	 *
	 * Empty when the site runs on plain permalinks: without a permalink
	 * structure there is nothing to append `.md` to, and the rewrite rule never
	 * matches. Callers print nothing rather than a link that would 404.
	 *
	 * @param int|\WP_Post|null $post Post to address.
	 */
	public static function url( $post = null ): string {
		$post = get_post( $post );

		if ( ! $post instanceof \WP_Post || ! self::is_public( $post ) ) {
			return '';
		}

		if ( ! get_option( 'permalink_structure' ) ) {
			return '';
		}

		$permalink = (string) get_permalink( $post );

		if ( '' === $permalink || str_contains( $permalink, '?' ) ) {
			return '';
		}

		/*
		 * The front page has no path to append to: `rtrim()` would hand back
		 * the bare host and produce `https://lupin3rd.org.md`, an address on a
		 * domain that is not ours. It is also the one page whose content is the
		 * template rather than the post, so there is nothing to offer.
		 */
		$path = (string) wp_parse_url( $permalink, PHP_URL_PATH );

		if ( '' === $path || '/' === $path ) {
			return '';
		}

		return rtrim( $permalink, '/' ) . '.md';
	}

	/**
	 * The Markdown for a post, built once per revision.
	 *
	 * The cache key carries the modification time, so publishing a change
	 * invalidates nothing: it simply asks a different question, and the stale
	 * answer expires on its own.
	 *
	 * @param int|\WP_Post|null $post Post to render.
	 */
	public static function of( $post = null ): string {
		$post = get_post( $post );

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		$key    = 'l3_md_' . $post->ID . '_' . substr( md5( $post->post_modified_gmt . '|' . self::signature() ), 0, 10 );
		$cached = get_transient( $key );

		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$markdown = self::build( $post );

		set_transient( $key, $markdown, WEEK_IN_SECONDS );

		return $markdown;
	}

	/**
	 * A fingerprint of the code that produces the text.
	 *
	 * The cache key carries the post's modification time, so editing a piece
	 * asks a different question. But the answer also depends on this file, and
	 * changing a rule here while the old text sits in a transient is how you
	 * spend an afternoon debugging a fix that already works. The modification
	 * time of the file itself is free, exact, and changes on deploy.
	 */
	private static function signature(): string {
		static $signature = null;

		return $signature ??= (string) ( filemtime( __FILE__ ) ?: 0 );
	}

	/**
	 * Whether a post may be served at all.
	 *
	 * @param \WP_Post $post Post to check.
	 */
	private static function is_public( \WP_Post $post ): bool {
		if ( 'publish' !== $post->post_status || '' !== $post->post_password ) {
			return false;
		}

		if ( 'attachment' === $post->post_type ) {
			return false;
		}

		$type = get_post_type_object( $post->post_type );

		return $type instanceof \WP_Post_Type && $type->public;
	}

	/**
	 * Find the post a `.md` path refers to.
	 *
	 * @param string $path Path captured by the rewrite rule, without the suffix.
	 */
	private static function resolve( string $path ): ?\WP_Post {
		$path = trim( $path, '/' );

		if ( '' === $path ) {
			return null;
		}

		foreach ( array( user_trailingslashit( $path ), $path ) as $candidate ) {
			$id = url_to_postid( home_url( '/' . ltrim( $candidate, '/' ) ) );

			if ( $id > 0 ) {
				return get_post( $id );
			}
		}

		$page = get_page_by_path( $path );

		return $page instanceof \WP_Post ? $page : null;
	}

	/**
	 * Assemble the whole document.
	 *
	 * @param \WP_Post $post Post to render.
	 */
	private static function build( \WP_Post $post ): string {
		$parts = array( self::front_matter( $post ), '# ' . self::inline( get_the_title( $post ) ) );

		if ( has_post_thumbnail( $post ) ) {
			$image = (string) get_the_post_thumbnail_url( $post, 'l3-feature' );
			$alt   = (string) get_post_meta( (int) get_post_thumbnail_id( $post ), '_wp_attachment_image_alt', true );

			if ( '' !== $image ) {
				$parts[] = sprintf( '![%s](%s)', self::escape( $alt ), $image );
			}
		}

		foreach ( parse_blocks( $post->post_content ) as $block ) {
			$rendered = self::block( $block );

			if ( '' !== $rendered ) {
				$parts[] = $rendered;
			}
		}

		return implode( "\n\n", $parts ) . "\n";
	}

	/**
	 * The YAML header: what the piece is, and under what licence.
	 *
	 * This is what turns a text into an attributable text. A model that quotes
	 * the page has the author, the canonical address and the licence in the
	 * same file as the words, instead of in a footer it never read.
	 *
	 * @param \WP_Post $post Post to describe.
	 */
	private static function front_matter( \WP_Post $post ): string {
		$lines = array(
			'title: ' . self::quote( get_the_title( $post ) ),
			'url: ' . self::quote( (string) get_permalink( $post ) ),
			'date: ' . self::quote( (string) get_the_date( 'Y-m-d', $post ) ),
		);

		if ( $post->post_modified_gmt !== $post->post_date_gmt ) {
			$lines[] = 'updated: ' . self::quote( (string) get_the_modified_date( 'Y-m-d', $post ) );
		}

		$summary = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );

		if ( '' !== $summary ) {
			$lines[] = 'summary: ' . self::quote( $summary );
		}

		$lines[] = 'author: ' . self::quote( Profile_Fields::pseudonym( (int) $post->post_author ) );
		$lines[] = 'section: ' . self::quote( Content_Types::label( $post->post_type ) );
		$lines[] = 'license: ' . self::quote( 'CC BY-SA 4.0' );
		$lines[] = 'license_url: ' . self::quote( 'https://creativecommons.org/licenses/by-sa/4.0/' );

		return "---\n" . implode( "\n", $lines ) . "\n---";
	}

	/**
	 * Translate one block, and its children.
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @param int                  $depth Nesting depth, for lists.
	 */
	private static function block( array $block, int $depth = 0 ): string {
		$name  = (string) ( $block['blockName'] ?? '' );
		$attrs = (array) ( $block['attrs'] ?? array() );
		$html  = (string) ( $block['innerHTML'] ?? '' );

		switch ( $name ) {
			case '':
				// Classic content: HTML the editor never turned into blocks.
				return self::classic( $html );

			case 'core/paragraph':
				return self::inline( $html );

			case 'core/heading':

				/*
				 * Never above h2: the h1 of the document is the title, and a
				 * second one would make the file claim to be two documents.
				 */
				$level = max( 2, min( 6, (int) ( $attrs['level'] ?? 2 ) ) );

				return str_repeat( '#', $level ) . ' ' . self::inline( $html );

			case 'core/list':
				return self::list_block( $block, ! empty( $attrs['ordered'] ), $depth );

			case 'core/quote':
			case 'core/pullquote':
				return self::quote_block( $block );

			case 'core/separator':
				return '---';

			case 'core/image':
				return self::image_block( $html );

			case 'core/table':
				return self::table_block( $html );

			case 'core/embed':

				/*
				 * A link, never an embed. On the page an iframe is held back by
				 * the consent gate; in a text file there is no gate to hold it,
				 * so there must be nothing to hold.
				 */
				$url = (string) ( $attrs['url'] ?? '' );

				return '' === $url ? '' : sprintf( '[%s](%s)', $url, $url );

			case 'core/code':
			case 'core/preformatted':
				return "```\n" . self::plain( $html ) . "\n```";

			case 'l3/code':
				return self::code_block( $attrs );

			case 'l3/notice':
				return self::notice_block( $block, $attrs );

			case 'core/html':
				// Raw HTML the author typed: its text, never its markup.
				return self::inline( $html );

			default:
				return self::children( $block, $depth );
		}
	}

	/**
	 * Render a block's children, for containers and for anything unknown.
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @param int                  $depth Nesting depth.
	 */
	private static function children( array $block, int $depth = 0 ): string {
		$inner = (array) ( $block['innerBlocks'] ?? array() );

		if ( ! $inner ) {
			/*
			 * A dynamic block with no children — a list of latest posts, a
			 * plugin's widget. Render it and keep the words: impoverished, but
			 * never raw markup inside a Markdown file.
			 *
			 * Unless it has no name at all, in which case it is a fragment this
			 * class built to reuse the branches below, and rendering it would
			 * ask core to render nothing.
			 */
			return '' === (string) ( $block['blockName'] ?? '' )
				? self::inline( (string) ( $block['innerHTML'] ?? '' ) )
				: self::inline( (string) render_block( $block ) );
		}

		$parts = array();

		foreach ( $inner as $child ) {
			$rendered = self::block( $child, $depth );

			if ( '' !== $rendered ) {
				$parts[] = $rendered;
			}
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * A list, with its nested lists.
	 *
	 * @param array<string, mixed> $block   Parsed list block.
	 * @param bool                 $ordered Whether it is numbered.
	 * @param int                  $depth   Nesting depth.
	 */
	private static function list_block( array $block, bool $ordered, int $depth ): string {
		$items  = (array) ( $block['innerBlocks'] ?? array() );
		$indent = str_repeat( '  ', $depth );
		$lines  = array();
		$number = 1;

		if ( ! $items ) {
			// A list written before core split the items into blocks of their own.
			preg_match_all( '#<li\b[^>]*>(.*?)</li>#is', (string) ( $block['innerHTML'] ?? '' ), $found );

			foreach ( (array) ( $found[1] ?? array() ) as $item ) {
				$lines[] = $indent . ( $ordered ? $number++ . '.' : '-' ) . ' ' . self::inline( (string) $item );
			}

			return implode( "\n", $lines );
		}

		foreach ( $items as $item ) {
			$marker = $ordered ? $number++ . '.' : '-';
			$text   = self::inline( (string) ( $item['innerHTML'] ?? '' ) );
			$lines[] = $indent . $marker . ' ' . $text;

			foreach ( (array) ( $item['innerBlocks'] ?? array() ) as $child ) {
				$nested = self::block( $child, $depth + 1 );

				if ( '' !== $nested ) {
					$lines[] = $nested;
				}
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * A quotation: every line of it prefixed, including the blank ones.
	 *
	 * @param array<string, mixed> $block Parsed quote block.
	 */
	private static function quote_block( array $block ): string {
		$inner = empty( $block['innerBlocks'] )
			? self::inline( (string) ( $block['innerHTML'] ?? '' ) )
			: self::children( $block );

		if ( '' === $inner ) {
			return '';
		}

		$lines = array();

		foreach ( explode( "\n", $inner ) as $line ) {
			$lines[] = rtrim( '> ' . $line );
		}

		return implode( "\n", $lines );
	}

	/**
	 * An image, with its caption underneath.
	 *
	 * @param string $html Block markup.
	 */
	private static function image_block( string $html ): string {
		if ( ! preg_match( '#<img\b[^>]*>#i', $html, $tag ) ) {
			return '';
		}

		$src = preg_match( '#\bsrc=["\']([^"\']+)["\']#i', $tag[0], $found ) ? $found[1] : '';
		$alt = preg_match( '#\balt=["\']([^"\']*)["\']#i', $tag[0], $found ) ? $found[1] : '';

		if ( '' === $src ) {
			return '';
		}

		$image = sprintf( '![%s](%s)', self::escape( html_entity_decode( $alt, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ), $src );

		if ( preg_match( '#<figcaption\b[^>]*>(.*?)</figcaption>#is', $html, $caption ) ) {
			$text = self::inline( $caption[1] );

			if ( '' !== $text ) {
				return $image . "\n" . '*' . $text . '*';
			}
		}

		return $image;
	}

	/**
	 * A table. The first row becomes the header, because Markdown has no other.
	 *
	 * @param string $html Block markup.
	 */
	private static function table_block( string $html ): string {
		preg_match_all( '#<tr\b[^>]*>(.*?)</tr>#is', $html, $rows );

		$grid = array();

		foreach ( (array) ( $rows[1] ?? array() ) as $row ) {
			preg_match_all( '#<t[hd]\b[^>]*>(.*?)</t[hd]>#is', (string) $row, $cells );

			$line = array();

			foreach ( (array) ( $cells[1] ?? array() ) as $cell ) {
				$line[] = str_replace( '|', '\\|', self::inline( (string) $cell ) );
			}

			if ( $line ) {
				$grid[] = $line;
			}
		}

		if ( ! $grid ) {
			return '';
		}

		$header = array_shift( $grid );
		$lines  = array(
			'| ' . implode( ' | ', $header ) . ' |',
			'|' . str_repeat( '---|', count( $header ) ),
		);

		foreach ( $grid as $row ) {
			$lines[] = '| ' . implode( ' | ', $row ) . ' |';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Our code block: the one that translates better than any converter would.
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 */
	private static function code_block( array $attrs ): string {
		$code = trim( (string) ( $attrs['content'] ?? '' ), "\n" );

		if ( '' === trim( $code ) ) {
			return '';
		}

		$language = (string) ( $attrs['language'] ?? '' );
		$filename = (string) ( $attrs['filename'] ?? '' );
		$fence    = '```' . $language . "\n" . $code . "\n```";

		return '' === $filename ? $fence : '*' . self::escape( $filename ) . "*\n" . $fence;
	}

	/**
	 * Our notice: the heading it carries on the page, then what it says.
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @param array<string, mixed> $attrs Block attributes.
	 */
	private static function notice_block( array $block, array $attrs ): string {
		$title = Blocks::notice_title( (string) ( $attrs['variant'] ?? 'method' ) );
		$body  = self::children( $block );

		if ( '' === $body ) {
			return '';
		}

		$lines = array( '> **' . $title . '**', '>' );

		foreach ( explode( "\n", $body ) as $line ) {
			$lines[] = rtrim( '> ' . $line );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Content the editor never turned into blocks.
	 *
	 * Everything above reads structure from `parse_blocks()`. A post written
	 * before the block editor, or pasted in as markup, has that structure only
	 * in its tags, and flattening it would turn every heading into a line of
	 * prose indistinguishable from the paragraph under it. So this walks the
	 * block-level tags in order, and the text between them is not dropped: it
	 * becomes a paragraph, which is what it reads as.
	 *
	 * @param string $html Classic markup.
	 */
	private static function classic( string $html ): string {
		$html = trim( $html );

		if ( '' === $html ) {
			return '';
		}

		$pattern = '#<(h[1-6]|p|ul|ol|blockquote|pre|figure|table)\b[^>]*>(.*?)</\1>#is';

		if ( ! preg_match_all( $pattern, $html, $found, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			return self::inline( $html );
		}

		$parts  = array();
		$cursor = 0;

		foreach ( $found as $match ) {
			$whole  = (string) $match[0][0];
			$start  = (int) $match[0][1];
			$tag    = strtolower( (string) $match[1][0] );
			$inner  = (string) $match[2][0];
			$before = self::inline( substr( $html, $cursor, $start - $cursor ) );

			if ( '' !== $before ) {
				$parts[] = $before;
			}

			$parts[] = match ( true ) {
				str_starts_with( $tag, 'h' ) => str_repeat( '#', max( 2, min( 6, (int) substr( $tag, 1 ) ) ) ) . ' ' . self::inline( $inner ),
				'ul' === $tag, 'ol' === $tag => self::list_block( array( 'innerHTML' => $whole ), 'ol' === $tag, 0 ),
				'blockquote' === $tag        => self::quote_block( array( 'innerHTML' => $inner ) ),
				'pre' === $tag               => "```\n" . self::plain( $inner ) . "\n```",
				'figure' === $tag            => self::image_block( $whole ),
				'table' === $tag             => self::table_block( $whole ),
				default                      => self::inline( $inner ),
			};

			$cursor = $start + strlen( $whole );
		}

		$tail = self::inline( substr( $html, $cursor ) );

		if ( '' !== $tail ) {
			$parts[] = $tail;
		}

		return implode( "\n\n", array_filter( $parts, static fn ( string $part ): bool => '' !== $part ) );
	}

	/**
	 * Inline markup to Markdown: the only HTML this class reads.
	 *
	 * Block structure comes from `parse_blocks()`; what is left inside a block
	 * is a handful of inline tags, and those are worth converting by hand
	 * rather than by a dependency. Anything not named here loses its tags and
	 * keeps its words.
	 *
	 * @param string $html Inner markup.
	 */
	private static function inline( string $html ): string {
		/*
		 * An iframe becomes the address it pointed at. This is the one place
		 * that has to happen, because an embed reaches us by three different
		 * routes — a core/embed block, a core/html block, and raw markup typed
		 * between two paragraphs of a classic post — and only the first of them
		 * arrives with its URL in an attribute we already read.
		 *
		 * Losing the address would be worse than losing the player: on the page
		 * the consent gate holds the iframe back and offers the link; a file
		 * that silently dropped both would be telling a reader there was
		 * nothing there.
		 */
		$text = (string) preg_replace_callback(
			'#<iframe\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>(?:.*?</iframe>)?#is',
			static fn ( array $frame ): string => sprintf( ' [%s](%s) ', $frame[1], $frame[1] ),
			$html
		);

		$text = (string) preg_replace( '#<br\s*/?>#i', "\n", $text );
		$text = (string) preg_replace( '#</(p|h[1-6]|div|li)>#i', "\n", $text );
		$text = (string) preg_replace( '#<code\b[^>]*>(.*?)</code>#is', '`$1`', $text );
		$text = (string) preg_replace( '#<(strong|b)\b[^>]*>(.*?)</\1>#is', '**$2**', $text );
		$text = (string) preg_replace( '#<(em|i)\b[^>]*>(.*?)</\1>#is', '*$2*', $text );

		$text = (string) preg_replace_callback(
			'#<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is',
			static function ( array $link ): string {
				$url = $link[1];

				// A link the author wrote as /qualcosa/ has to survive the trip.
				if ( str_starts_with( $url, '/' ) ) {
					$url = home_url( $url );
				}

				return sprintf( '[%s](%s)', trim( wp_strip_all_tags( $link[2] ) ), $url );
			},
			$text
		);

		return self::plain( $text );
	}

	/**
	 * Tags out, entities decoded, blank lines collapsed.
	 *
	 * @param string $html Markup.
	 */
	private static function plain( string $html ): string {
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = (string) preg_replace( '/[ \t]+/', ' ', $text );
		$text = (string) preg_replace( '/\n{3,}/', "\n\n", $text );

		return trim( $text );
	}

	/**
	 * Escape the characters that would end a Markdown link or image early.
	 *
	 * @param string $text Text to escape.
	 */
	private static function escape( string $text ): string {
		return str_replace( array( '[', ']' ), array( '\\[', '\\]' ), trim( $text ) );
	}

	/**
	 * Quote a YAML scalar, so that a colon in a title cannot break the header.
	 *
	 * @param string $value Value to quote.
	 */
	private static function quote( string $value ): string {
		// One line, tags and entities gone: exactly what to_plain_text() does.
		$value = Template_Tags::to_plain_text( $value );

		return '"' . str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $value ) . '"';
	}
}
