<?php
/**
 * Head cleanup and cache friendliness.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Removes what WordPress prints by habit, and keeps public pages identical for
 * every reader so that they can be served from a file.
 */
final class Performance {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

		add_action( 'init', array( $this, 'disable_emoji' ) );
		add_filter( 'wp_resource_hints', array( $this, 'drop_external_hints' ), 10, 2 );
		add_filter( 'style_loader_src', array( $this, 'drop_version_query' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_block_library' ), 100 );
	}

	/**
	 * Remove the emoji detection script and its stylesheet.
	 *
	 * It is a third-party-shaped request the site does not need and a reader
	 * never asked for.
	 */
	public function disable_emoji(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}

	/**
	 * Drop resource hints pointing anywhere but this origin.
	 *
	 * @param array<int, mixed> $hints Hint URLs.
	 * @param string            $relation Relation type.
	 * @return array<int, mixed>
	 */
	public function drop_external_hints( array $hints, string $relation ): array {
		if ( 'dns-prefetch' !== $relation ) {
			return $hints;
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return array_values(
			array_filter(
				$hints,
				static function ( $hint ) use ( $host ): bool {
					$url = is_array( $hint ) ? ( $hint['href'] ?? '' ) : $hint;

					return str_contains( (string) $url, (string) $host );
				}
			)
		);
	}

	/**
	 * Remove the version query on core stylesheets.
	 *
	 * The theme's own assets are versioned by modification time instead, which
	 * is accurate rather than approximate.
	 *
	 * @param string $src Stylesheet URL.
	 */
	public function drop_version_query( string $src ): string {
		return str_contains( $src, 'lupin3rd' ) ? $src : remove_query_arg( 'ver', $src );
	}

	/**
	 * Drop the block library stylesheet on the front end.
	 *
	 * The design system styles every block the desk actually uses. Loading
	 * WordPress's own 200 KB on top would only give the theme something to
	 * override.
	 */
	public function dequeue_block_library(): void {
		if ( is_admin() ) {
			return;
		}

		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}
