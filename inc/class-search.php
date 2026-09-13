<?php
/**
 * Search.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Search over the three editorial post types, with a small result set the
 * overlay can filter in the browser.
 */
final class Search {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'pre_get_posts', array( $this, 'widen_post_types' ) );
	}

	/**
	 * Search tutorials and dossiers too, not only posts.
	 *
	 * @param \WP_Query $query Query being prepared.
	 */
	public function widen_post_types( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		$query->set( 'post_type', Content_Types::editorial_types() );
	}

	/**
	 * Recent pieces, used to populate the search overlay.
	 *
	 * The overlay filters this list in the browser for an instant answer; the
	 * form still submits to a real search page, which is what a reader without
	 * JavaScript and every crawler gets.
	 *
	 * @param int $limit How many to return.
	 * @return array<int, \WP_Post>
	 */
	public static function recent( int $limit = 12 ): array {
		$query = new \WP_Query(
			array(
				'post_type'           => Content_Types::editorial_types(),
				'posts_per_page'      => $limit,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'post_status'         => 'publish',
			)
		);

		return $query->posts;
	}

	/**
	 * Keywords a search-overlay row is matched against.
	 *
	 * @param int|\WP_Post|null $post Post to describe.
	 */
	public static function keywords( $post = null ): string {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$parts = array( $post->post_title );

		foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
			$terms = get_the_terms( $post, $taxonomy );

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$parts[] = $term->name;
				}
			}
		}

		return strtolower( implode( ' ', $parts ) );
	}
}
