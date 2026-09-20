<?php
/**
 * Metadata for search engines and social platforms.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Title, description, canonical, robots and Open Graph, in the theme.
 *
 * No SEO suite: the theme already owns its markup, so owning its metadata
 * means fewer moving parts and no settings screen that can be misconfigured.
 * WordPress's native sitemap covers the rest.
 */
final class Seo {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_filter( 'document_title_parts', array( $this, 'title_parts' ) );
		add_filter( 'document_title_separator', static fn (): string => '—' );
		add_action( 'wp_head', array( $this, 'print_meta' ), 2 );
		add_filter( 'wp_robots', array( $this, 'robots' ) );
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'drop_user_sitemap' ), 10, 2 );
	}

	/**
	 * Keep the title honest: no padding, no keyword stuffing.
	 *
	 * @param array<string, string> $parts Title parts.
	 * @return array<string, string>
	 */
	public function title_parts( array $parts ): array {
		unset( $parts['tagline'] );

		if ( is_front_page() ) {
			$parts['title'] = get_bloginfo( 'name' );
			$parts['site']  = __( 'Hack, inchieste, privacy e crypto', 'lupin3rd' );
		}

		return $parts;
	}

	/**
	 * Print the description, canonical and social tags.
	 */
	public function print_meta(): void {
		$description = $this->description();
		$canonical   = $this->canonical();

		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		}

		if ( $canonical ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
		}

		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( get_locale() ) );
		printf( '<meta property="og:type" content="%s">' . "\n", is_singular() ? 'article' : 'website' );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( wp_get_document_title() ) );

		if ( $description ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
		}

		if ( $canonical ) {
			printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $canonical ) );
		}

		$image = is_singular() ? Marks::social_image( get_post() ) : '';

		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
			echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		} else {
			echo '<meta name="twitter:card" content="summary">' . "\n";
		}

		if ( is_singular() ) {
			printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( (string) get_the_date( 'c' ) ) );
			printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( (string) get_the_modified_date( 'c' ) ) );
		}
	}

	/**
	 * Keep search results and author archives out of the index.
	 *
	 * @param array<string, mixed> $robots Robots directives.
	 * @return array<string, mixed>
	 */
	public function robots( array $robots ): array {
		if ( is_search() || is_author() || is_404() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = false;
		}

		if ( is_paged() ) {
			$robots['noindex'] = true;
		}

		$robots['max-image-preview'] = 'large';

		return $robots;
	}

	/**
	 * Remove authors from the sitemap: the desk is not enumerable.
	 *
	 * @param mixed  $provider Sitemap provider.
	 * @param string $name     Provider name.
	 * @return mixed
	 */
	public function drop_user_sitemap( $provider, string $name ) {
		return 'users' === $name ? false : $provider;
	}

	/**
	 * The description for the current view.
	 *
	 * Written text only: an excerpt the desk wrote, a term description they
	 * wrote. Never the first 160 characters of the body, which reads like
	 * filler because it is.
	 */
	public function description(): string {
		if ( is_singular() ) {
			$post = get_post();

			return $post && has_excerpt( $post )
				? wp_strip_all_tags( get_the_excerpt( $post ) )
				: '';
		}

		if ( is_category() || is_tag() || is_tax() ) {
			return wp_strip_all_tags( (string) term_description() );
		}

		if ( is_front_page() ) {
			return (string) get_bloginfo( 'description' );
		}

		return '';
	}

	/**
	 * The canonical URL for the current view.
	 */
	public function canonical(): string {
		if ( is_singular() ) {
			return (string) get_permalink();
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();

			return $term instanceof \WP_Term ? (string) get_term_link( $term ) : '';
		}

		if ( is_front_page() ) {
			return home_url( '/' );
		}

		if ( is_post_type_archive() ) {
			return (string) get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
		}

		return '';
	}
}
