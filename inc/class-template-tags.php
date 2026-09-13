<?php
/**
 * Template helpers.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Everything a template needs that is more than a loop.
 *
 * Templates hold markup and iteration. Any decision more complicated than
 * "print this" lives here, so that a rule can be changed in one place rather
 * than in eleven template files.
 */
final class Template_Tags {

	use Singleton;

	/**
	 * Nearly a pure library: one filter, because the archive heading is
	 * presentation and belongs beside the helper that names it.
	 */
	protected function register_hooks(): void {
		/*
		 * Drop WordPress's "Tag:" / "Categoria:" prefix. The kicker above the
		 * heading already says what kind of archive this is, and at the display
		 * size this theme sets, a repeated label reads as a mistake.
		 */
		add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
	}

	/**
	 * The kind of archive being viewed, for the kicker above its heading.
	 */
	public static function archive_kicker(): string {
		if ( is_category() ) {
			return __( 'Categoria', 'lupin3rd' );
		}

		if ( is_tag() ) {
			return __( 'Tag', 'lupin3rd' );
		}

		if ( is_tax( Content_Types::DIFFICULTY ) ) {
			return __( 'Difficoltà', 'lupin3rd' );
		}

		if ( is_tax() ) {
			$taxonomy = get_taxonomy( (string) get_query_var( 'taxonomy' ) );

			return $taxonomy ? $taxonomy->labels->singular_name : __( 'Archivio', 'lupin3rd' );
		}

		if ( is_post_type_archive() ) {
			return Content_Types::label( (string) get_query_var( 'post_type' ) );
		}

		if ( is_author() ) {
			return __( 'Membro', 'lupin3rd' );
		}

		if ( is_date() ) {
			return __( 'Data', 'lupin3rd' );
		}

		return __( 'Archivio', 'lupin3rd' );
	}

	/**
	 * URL of the general archive.
	 *
	 * The archive is the blog index. WordPress gives it a URL of its own only
	 * when a page is assigned as the posts page; until somebody does that, the
	 * archive lives at the site root. Linking to a hard-coded /archivio/ would
	 * produce a 404 on a fresh install, which is how a theme earns a bad
	 * reputation in its first five minutes.
	 */
	public static function archive_url(): string {
		$page = (int) get_option( 'page_for_posts' );

		if ( $page > 0 ) {
			return (string) get_permalink( $page );
		}

		return home_url( '/' );
	}

	/**
	 * Primary category of a post, or null when it has none.
	 *
	 * @param int|\WP_Post|null $post Post to read.
	 */
	public static function primary_category( $post = null ): ?\WP_Term {
		$terms = get_the_terms( $post ?: get_the_ID(), 'category' );

		return ( is_array( $terms ) && $terms ) ? $terms[0] : null;
	}

	/**
	 * Render the metadata line under a card: category, separator, date.
	 *
	 * @param int|\WP_Post|null $post   Post to read.
	 * @param bool              $linked Whether the category may be a link.
	 *                                  Pass false inside a card that is itself
	 *                                  an anchor: a nested <a> is invalid HTML,
	 *                                  and browsers recover from it by breaking
	 *                                  the outer anchor into several elements.
	 */
	public static function post_meta( $post = null, bool $linked = true ): void {
		$post     = get_post( $post );
		$category = self::primary_category( $post );
		$date     = I18n::instance()->post_date( $post );

		echo '<p class="post-meta meta">';

		if ( $category && $linked ) {
			printf(
				'<a href="%s">%s</a> &middot; ',
				esc_url( (string) get_category_link( $category ) ),
				esc_html( $category->name )
			);
		} elseif ( $category ) {
			printf( '%s &middot; ', esc_html( $category->name ) );
		}

		printf(
			'<time datetime="%s">%s</time>',
			esc_attr( (string) get_the_date( 'c', $post ) ),
			esc_html( $date )
		);

		echo '</p>';
	}

	/**
	 * Render the difficulty meter: filled blocks, never colour.
	 *
	 * @param int|\WP_Post|null $post Post to read.
	 */
	public static function difficulty( $post = null ): void {
		$terms = get_the_terms( $post ?: get_the_ID(), Content_Types::DIFFICULTY );

		if ( ! is_array( $terms ) || ! $terms ) {
			return;
		}

		$levels = array_keys( Content_Types::difficulty_levels() );
		$index  = array_search( $terms[0]->slug, $levels, true );
		$filled = false === $index ? 1 : (int) $index + 1;

		/*
		 * The level is encoded with filled blocks rather than colour, so it
		 * survives colour blindness and monochrome rendering. The blocks are
		 * decoration for a screen reader — the level is written out beside
		 * them — so they are hidden from it rather than announced twice.
		 */
		printf(
			'<span class="diff"><span class="sr-only">%s</span>',
			esc_html__( 'Difficoltà:', 'lupin3rd' )
		);

		echo '<span aria-hidden="true">';
		$total = count( $levels );

		for ( $i = 1; $i <= $total; $i++ ) {
			printf( '<i%s></i>', $i <= $filled ? ' class="on"' : '' );
		}
		echo '</span>';

		printf( ' %s</span>', esc_html( $terms[0]->name ) );
	}

	/**
	 * Render a post's tags as pills.
	 *
	 * @param int|\WP_Post|null $post  Post to read.
	 * @param int               $limit Maximum tags to print.
	 */
	public static function tags( $post = null, int $limit = 6 ): void {
		$terms = get_the_terms( $post ?: get_the_ID(), 'post_tag' );

		if ( ! is_array( $terms ) || ! $terms ) {
			return;
		}

		echo '<div class="row-wrap">';

		foreach ( array_slice( $terms, 0, $limit ) as $term ) {
			printf(
				'<a class="tag" href="%s">%s</a>',
				esc_url( (string) get_term_link( $term ) ),
				esc_html( $term->name )
			);
		}

		echo '</div>';
	}

	/**
	 * Estimated reading time in minutes, never less than one.
	 *
	 * @param int|\WP_Post|null $post Post to measure.
	 */
	public static function reading_minutes( $post = null ): int {
		$post = get_post( $post );

		if ( ! $post ) {
			return 1;
		}

		$words = str_word_count( wp_strip_all_tags( (string) $post->post_content ) );

		return max( 1, (int) ceil( $words / 200 ) );
	}

	/**
	 * Short summary for a card. Prefers the hand-written excerpt.
	 *
	 * @param int|\WP_Post|null $post  Post to read.
	 * @param int               $words Length in words.
	 */
	public static function summary( $post = null, int $words = 28 ): string {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$excerpt = has_excerpt( $post )
			? get_the_excerpt( $post )
			: self::to_plain_text( (string) $post->post_content );

		return wp_trim_words( $excerpt, $words, '…' );
	}

	/**
	 * Flatten markup to readable plain text.
	 *
	 * WordPress strips the tags and nothing else, so a heading that
	 * runs straight into the paragraph below it comes out as one glued word:
	 * "</h2><p>" becomes "scontiIl". Block-level tags become a space first.
	 *
	 * @param string $html Rendered or raw post content.
	 */
	public static function to_plain_text( string $html ): string {
		$html = str_replace( array( '<', '&nbsp;' ), array( ' <', ' ' ), $html );
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * The attributes a card carries so the client-side filters can read it.
	 *
	 * Behaviour binds to data attributes only, never to a class.
	 *
	 * @param int|\WP_Post|null $post Post to describe.
	 */
	public static function filter_attributes( $post = null ): string {
		$post       = get_post( $post );
		$category   = self::primary_category( $post );
		$difficulty = get_the_terms( $post, Content_Types::DIFFICULTY );

		$attributes = array(
			'data-post'       => '',
			'data-type'       => $post ? $post->post_type : 'post',
			'data-category'   => $category ? $category->slug : '',
			'data-difficulty' => ( is_array( $difficulty ) && $difficulty ) ? $difficulty[0]->slug : '',
		);

		$out = '';

		foreach ( $attributes as $name => $value ) {
			$out .= '' === $value
				? ' ' . $name
				: sprintf( ' %s="%s"', $name, esc_attr( $value ) );
		}

		return $out;
	}

	/**
	 * Render the breadcrumb trail.
	 *
	 * @param array<int, array{label: string, url: string}> $crumbs Trail, current page last and without a URL.
	 */
	public static function breadcrumb( array $crumbs ): void {
		if ( ! $crumbs ) {
			return;
		}

		printf( '<nav class="breadcrumb meta" aria-label="%s">', esc_attr__( 'Percorso', 'lupin3rd' ) );

		$last = count( $crumbs ) - 1;

		foreach ( $crumbs as $i => $crumb ) {
			if ( $i > 0 ) {
				echo '<span aria-hidden="true">/</span>';
			}

			if ( $i === $last || empty( $crumb['url'] ) ) {
				printf( '<span aria-current="page">%s</span>', esc_html( $crumb['label'] ) );
			} else {
				printf( '<a href="%s">%s</a>', esc_url( $crumb['url'] ), esc_html( $crumb['label'] ) );
			}
		}

		echo '</nav>';
	}

	/**
	 * Render pagination in the prototype's markup.
	 */
	public static function pagination(): void {
		$links = paginate_links(
			array(
				'type'      => 'array',
				'mid_size'  => 1,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
			)
		);

		if ( ! is_array( $links ) || count( $links ) < 2 ) {
			return;
		}

		printf( '<nav class="pagination" aria-label="%s">', esc_attr__( 'Pagine', 'lupin3rd' ) );

		foreach ( $links as $link ) {
			echo wp_kses_post( $link );
		}

		echo '</nav>';
	}

	/**
	 * Build a table of contents from the h2 headings in a piece.
	 *
	 * The ids are added to the rendered content by the same pass, so the
	 * anchors always exist even when the author never set one.
	 *
	 * @param string $content Rendered post content.
	 * @return array{content: string, headings: array<int, array{id: string, text: string}>}
	 */
	public static function extract_headings( string $content ): array {
		$headings = array();
		$used     = array();

		$content = (string) preg_replace_callback(
			'/<h2\b([^>]*)>(.*?)<\/h2>/is',
			static function ( array $found_heading ) use ( &$headings, &$used ): string {
				$attributes = $found_heading[1];
				$text       = html_entity_decode(
					wp_strip_all_tags( $found_heading[2] ),
					ENT_QUOTES | ENT_HTML5,
					'UTF-8'
				);

				if ( preg_match( '/\bid=["\']([^"\']+)["\']/', $attributes, $found ) ) {
					$id = $found[1];
				} else {
					$base = sanitize_title( $text );
					$id   = '' !== $base ? $base : 'sezione';
					$n    = 2;

					while ( in_array( $id, $used, true ) ) {
						$id = $base . '-' . $n;
						++$n;
					}
					$attributes .= sprintf( ' id="%s"', esc_attr( $id ) );
				}

				$used[]     = $id;
				$headings[] = array(
					'id'   => $id,
					'text' => $text,
				);

				return sprintf( '<h2%s>%s</h2>', $attributes, $found_heading[2] );
			},
			$content
		);

		return array(
			'content'  => $content,
			'headings' => $headings,
		);
	}

	/**
	 * Pieces to read after this one.
	 *
	 * Neighbours first: anything sharing a category or a tag, most recent
	 * first. A reader who finishes a piece on firmware wants more firmware, not
	 * the newest thing on the site. When the shared terms do not yield enough,
	 * the list is topped up with recent pieces rather than left short, because
	 * a row of one card reads like a mistake.
	 *
	 * Instalments of a dossier are excluded: the side column already navigates
	 * those, and offering them here would send the reader round in a circle.
	 *
	 * @param int|\WP_Post|null $post  Piece being read.
	 * @param int               $limit How many to return.
	 * @return array<int, \WP_Post>
	 */
	public static function related( $post = null, int $limit = 3 ): array {
		$post = get_post( $post );

		if ( ! $post ) {
			return array();
		}

		$terms = array();

		foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
			$ids = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

			if ( ! is_wp_error( $ids ) ) {
				$terms = array_merge( $terms, $ids );
			}
		}

		$common = array(
			'post_type'           => Content_Types::editorial_types(),
			'post_status'         => 'publish',
			'post_parent'         => 0,
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'post__not_in'        => array( $post->ID, (int) $post->post_parent ),
		);

		$found = array();

		if ( $terms ) {
			$found = get_posts(
				$common + array(
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						'relation' => 'OR',
						array(
							'taxonomy' => 'category',
							'field'    => 'term_id',
							'terms'    => $terms,
						),
						array(
							'taxonomy' => 'post_tag',
							'field'    => 'term_id',
							'terms'    => $terms,
						),
					),
				)
			);
		}

		if ( count( $found ) < $limit ) {
			$exclude = array_merge(
				$common['post__not_in'],
				wp_list_pluck( $found, 'ID' )
			);

			$found = array_merge(
				$found,
				get_posts(
					array_merge(
						$common,
						array(
							'posts_per_page' => $limit - count( $found ),
							'post__not_in'   => $exclude,
						)
					)
				)
			);
		}

		return $found;
	}

	/**
	 * Tags whose slug most resembles the one that was asked for.
	 *
	 * Somebody who mistypes a URL usually knows roughly what they were after,
	 * so the 404 offers the nearest tags rather than an apology. Levenshtein
	 * over at most sixty terms: small enough to run on a page nobody caches.
	 *
	 * @param int $limit Maximum suggestions.
	 * @return array<int, \WP_Term>
	 */
	public static function tag_suggestions( int $limit = 5 ): array {
		$path     = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Read as a slug, sanitised below.
		$segments = array_filter( explode( '/', $path ) );
		$slug     = sanitize_title( (string) ( $segments ? (string) array_pop( $segments ) : '' ) );

		$terms = get_terms(
			array(
				'taxonomy'   => 'post_tag',
				'hide_empty' => true,
				'number'     => 60,
			)
		);

		if ( is_wp_error( $terms ) || ! $terms || '' === $slug ) {
			return is_wp_error( $terms ) ? array() : array_slice( (array) $terms, 0, $limit );
		}

		usort(
			$terms,
			static fn ( \WP_Term $a, \WP_Term $b ): int =>
				levenshtein( $slug, $a->slug ) <=> levenshtein( $slug, $b->slug )
		);

		return array_slice( $terms, 0, $limit );
	}
}
