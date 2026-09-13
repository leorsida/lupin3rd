<?php
/**
 * Archive filtering.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Makes the archive filters work on the server as well as in the browser.
 *
 * The prototype filters client-side, which is quick and pleasant but means a
 * reader without JavaScript sees no filtering and a crawler sees every post
 * regardless of the chip that looks pressed. So each filter is also a real URL
 * that runs a real query; the client-side filter only saves a round trip.
 */
final class Archive_Filters {

	use Singleton;

	/**
	 * Query variables the archive understands.
	 *
	 * @var array<int, string>
	 */
	private const VARS = array( 'l3_format', 'l3_category', 'l3_difficulty' );

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'pre_get_posts', array( $this, 'apply' ) );
	}

	/**
	 * Let WordPress read our query variables.
	 *
	 * @param array<int, string> $vars Public query variables.
	 * @return array<int, string>
	 */
	public function register_query_vars( array $vars ): array {
		return array_merge( $vars, self::VARS );
	}

	/**
	 * Whether this query is one the archive filters govern.
	 *
	 * The general archive is the blog index, which WordPress reports as
	 * is_home() and not is_archive() — the distinction that made the archive
	 * show news only. A post type archive is excluded: /tutorial/ already means
	 * one type, and widening it would make the URL lie.
	 *
	 * @param \WP_Query $query Query being prepared.
	 */
	private function governs( \WP_Query $query ): bool {
		if ( is_admin() || ! $query->is_main_query() ) {
			return false;
		}

		if ( $query->is_post_type_archive() || $query->is_feed() ) {
			return false;
		}

		return $query->is_home() || $query->is_category() || $query->is_tag()
			|| $query->is_date() || $query->is_author();
	}

	/**
	 * Apply the filters to the main archive query.
	 *
	 * @param \WP_Query $query Query being prepared.
	 */
	public function apply( \WP_Query $query ): void {
		if ( ! $this->governs( $query ) ) {
			return;
		}

		$format = (string) $query->get( 'l3_format' );

		/*
		 * The archive means everything the desk has published, in all three
		 * formats. Without this it means posts, because that is what a blog
		 * index defaults to.
		 */
		$query->set(
			'post_type',
			( $format && in_array( $format, Content_Types::editorial_types(), true ) )
				? $format
				: Content_Types::editorial_types()
		);

		$tax_query = array();

		$category = (string) $query->get( 'l3_category' );
		if ( $category ) {
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => $category,
			);
		}

		$difficulty = (string) $query->get( 'l3_difficulty' );
		if ( $difficulty ) {
			$tax_query[] = array(
				'taxonomy' => Content_Types::DIFFICULTY,
				'field'    => 'slug',
				'terms'    => $difficulty,
			);
		}

		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}
	}

	/**
	 * Return the value currently selected for a filter.
	 *
	 * @param string $name One of format, category, difficulty.
	 */
	public static function current( string $name ): string {
		return (string) get_query_var( 'l3_' . $name, '' );
	}

	/**
	 * Build the URL for a filter chip.
	 *
	 * @param string $name  One of format, category, difficulty.
	 * @param string $value Term slug or post type; empty for "all".
	 */
	public static function url( string $name, string $value ): string {
		$base = Template_Tags::archive_url();
		$args = array();

		foreach ( array( 'format', 'category', 'difficulty' ) as $filter ) {
			$current = $filter === $name ? $value : self::current( $filter );

			if ( $current ) {
				$args[ 'l3_' . $filter ] = $current;
			}
		}

		return $args ? add_query_arg( $args, $base ) : $base;
	}
}
