<?php
/**
 * Structured data.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * JSON-LD describing the site and each piece.
 *
 * A Person never carries an email address or a last-seen timestamp: a public
 * profile on this site shows contributions and a declared key, and nothing
 * that would help somebody find the person behind the pseudonym.
 */
final class Schema {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'wp_head', array( $this, 'print_graph' ), 3 );
	}

	/**
	 * Print the JSON-LD graph for the current view.
	 */
	public function print_graph(): void {
		$graph = array_values( array_filter( array_merge( $this->site_nodes(), $this->view_nodes() ) ) );

		if ( ! $graph ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			)
		);
	}

	/**
	 * Organization and WebSite, on every page.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function site_nodes(): array {
		$home = home_url( '/' );

		return array(
			array(
				'@type'       => 'Organization',
				'@id'         => $home . '#organization',
				'name'        => get_bloginfo( 'name' ),
				'url'         => $home,
				'description' => get_bloginfo( 'description' ),
				'foundingDate' => '1999',
			),
			array(
				'@type'         => 'WebSite',
				'@id'           => $home . '#website',
				'url'           => $home,
				'name'          => get_bloginfo( 'name' ),
				'inLanguage'    => I18n::instance()->language(),
				'publisher'     => array( '@id' => $home . '#organization' ),
				'license'       => 'https://creativecommons.org/licenses/by-sa/4.0/',
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => $home . '?s={search_term_string}',
					),
					'query-input' => 'required name=search_term_string',
				),
			),
		);
	}

	/**
	 * Nodes specific to the current view.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function view_nodes(): array {
		if ( ! is_singular( Content_Types::editorial_types() ) ) {
			return array();
		}

		$post = get_post();

		if ( ! $post ) {
			return array();
		}

		$url  = (string) get_permalink( $post );
		$home = home_url( '/' );

		$article = array(
			'@type'            => $this->article_type( $post->post_type ),
			'@id'              => $url . '#article',
			'headline'         => Template_Tags::to_plain_text( get_the_title( $post ) ),
			'url'              => $url,
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'inLanguage'       => I18n::instance()->language(),
			'publisher'        => array( '@id' => $home . '#organization' ),
			'isAccessibleForFree' => true,
			'license'          => 'https://creativecommons.org/licenses/by-sa/4.0/',
		);

		if ( has_excerpt( $post ) ) {
			$article['description'] = Template_Tags::to_plain_text( get_the_excerpt( $post ) );
		}

		$image = Marks::social_image( $post );

		if ( $image ) {
			$article['image'] = $image;
		}

		$author = get_the_author_meta( 'display_name', (int) $post->post_author );

		if ( $author ) {
			$article['author'] = array(
				'@type' => 'Person',
				'name'  => $author,
			);
		}

		$nodes = array( $article );

		if ( Content_Types::TUTORIAL === $post->post_type ) {
			$nodes[] = $this->how_to( $post, $url );
		}

		return array_filter( $nodes );
	}

	/**
	 * Map a post type to its schema.org article type.
	 *
	 * @param string $post_type Post type name.
	 */
	private function article_type( string $post_type ): string {
		return match ( $post_type ) {
			Content_Types::TUTORIAL => 'TechArticle',
			Content_Types::DOSSIER  => 'Article',
			default                 => 'NewsArticle',
		};
	}

	/**
	 * Build the HowTo node for a tutorial, from its h2 headings.
	 *
	 * @param \WP_Post $post Tutorial.
	 * @param string   $url  Permalink.
	 * @return array<string, mixed>|null
	 */
	private function how_to( \WP_Post $post, string $url ): ?array {
		$rendered = apply_filters( 'the_content', $post->post_content );
		$parsed   = Template_Tags::extract_headings( (string) $rendered );

		if ( ! $parsed['headings'] ) {
			return null;
		}

		$steps = array();

		foreach ( $parsed['headings'] as $position => $heading ) {
			$steps[] = array(
				'@type'    => 'HowToStep',
				'position' => $position + 1,
				'name'     => $heading['text'],
				'url'      => $url . '#' . $heading['id'],
			);
		}

		$how_to = array(
			'@type' => 'HowTo',
			'@id'   => $url . '#howto',
			'name'  => Template_Tags::to_plain_text( get_the_title( $post ) ),
			'step'  => $steps,
		);

		$minutes = (int) Meta_Fields::get( 'l3_estimated_time', 0, $post->ID );

		if ( $minutes > 0 ) {
			$how_to['totalTime'] = 'PT' . $minutes . 'M';
		}

		$prerequisites = (string) Meta_Fields::get( 'l3_prerequisites', '', $post->ID );

		if ( $prerequisites ) {
			$how_to['supply'] = array_map(
				static fn ( string $line ): array => array(
					'@type' => 'HowToSupply',
					'name'  => $line,
				),
				array_filter( preg_split( '/\R/', $prerequisites ) ?: array() )
			);
		}

		return $how_to;
	}
}
