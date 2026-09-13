<?php
/**
 * Feeds.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Feeds per taxonomy, and no feed that would enumerate the desk.
 */
final class Feeds {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'pre_get_posts', array( $this, 'include_all_types' ) );
		add_action( 'template_redirect', array( $this, 'block_author_feeds' ) );
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
	}

	/**
	 * The main feed carries every editorial format, not only posts.
	 *
	 * @param \WP_Query $query Query being prepared.
	 */
	public function include_all_types( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_feed() ) {
			return;
		}

		if ( $query->is_tax() || $query->is_category() || $query->is_tag() || $query->is_home() ) {
			$query->set( 'post_type', Content_Types::editorial_types() );
		}
	}

	/**
	 * A feed per author would enumerate the desk. There is none.
	 */
	public function block_author_feeds(): void {
		if ( is_feed() && is_author() ) {
			wp_die(
				esc_html__( 'Non pubblichiamo feed per autore.', 'lupin3rd' ),
				esc_html__( 'Feed non disponibile', 'lupin3rd' ),
				array( 'response' => 404 )
			);
		}
	}
}
