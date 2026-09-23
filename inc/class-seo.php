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
		add_action( 'wp_head', array( $this, 'offer_the_markdown_twin' ), 3 );
		add_action( 'template_redirect', array( $this, 'send_the_markdown_link' ), 5 );
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
	 * Announce the Markdown twin of this page, in the markup.
	 *
	 * Declaring is the whole strategy: no request is answered differently
	 * because of who asked. A reader agent that wants the plain text follows
	 * this link, or /llms.txt; one that does not, reads the HTML like anybody
	 * else. Serving different bodies at one address by sniffing the user agent
	 * — a string the client makes up about itself — would be cloaking, and
	 * would break the first time a cache sat in front of the site.
	 */
	public function offer_the_markdown_twin(): void {
		$url = is_singular() ? Markdown::url( get_post() ) : '';

		if ( '' === $url ) {
			return;
		}

		printf( '<link rel="alternate" type="text/markdown" href="%s">' . "\n", esc_url( $url ) );
	}

	/**
	 * The same announcement, for whoever reads only the headers.
	 */
	public function send_the_markdown_link(): void {
		if ( headers_sent() || ! is_singular() ) {
			return;
		}

		$url = Markdown::url( get_post() );

		if ( '' === $url ) {
			return;
		}

		header( 'Link: <' . esc_url_raw( $url ) . '>; rel="alternate"; type="text/markdown"', false );
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
		/*
		 * The front page first, and before is_singular(). With a static page on
		 * the front, both are true, and asking the wrong one first is how the
		 * home page ended up with no description at all: the page carries no
		 * excerpt, so the singular branch returned an empty string and the
		 * branch that knows what the site is never ran.
		 */
		if ( is_front_page() ) {
			return Template_Tags::to_plain_text( (string) get_bloginfo( 'description' ) );
		}

		if ( is_singular() ) {
			/*
			 * The hand-written excerpt when there is one, and the opening of
			 * the piece when there is not. Summary() is what the cards already
			 * use, so the description a search engine shows and the one a
			 * reader sees in a list are the same sentence rather than two
			 * things to keep in step.
			 */
			return Template_Tags::summary( get_post(), 30 );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$described = Template_Tags::to_plain_text( (string) term_description() );

			if ( '' !== $described ) {
				return $described;
			}

			$term = get_queried_object();

			/* translators: %s: section or tag name. */
			return $term instanceof \WP_Term ? sprintf( __( 'Tutto quello che abbiamo pubblicato su %s.', 'lupin3rd' ), $term->name ) : '';
		}

		if ( is_post_type_archive() ) {
			$type = get_post_type_object( (string) get_query_var( 'post_type' ) );

			return $type instanceof \WP_Post_Type ? Template_Tags::to_plain_text( (string) $type->description ) : '';
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
