<?php
/**
 * Primary navigation markup.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Emits the navigation as a flat row of anchors.
 *
 * The prototype's navigation is `<nav><a>…</a></nav>`, and the stylesheet
 * targets `.topnav nav a` directly. WordPress produces `<li><a>` pairs, which
 * outside a list render with bullet markers and carry a list semantic the
 * design does not have. One level only: this navigation has no submenus, and a
 * dropdown would be a design decision, not a walker feature.
 *
 * Not a singleton: wp_nav_menu() instantiates a walker per call.
 */
final class Nav_Walker extends \Walker_Nav_Menu {

	/**
	 * Open an item. There is no wrapper: the anchor is the item.
	 *
	 * @param string    $output      Accumulated markup, by reference.
	 * @param \WP_Post  $data_object Menu item.
	 * @param int       $depth       Nesting depth.
	 * @param \stdClass $args        wp_nav_menu arguments.
	 * @param int       $current_object_id Current item id.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ): void {
		if ( $depth > 0 ) {
			return;
		}

		$item    = $data_object;
		$classes = (array) ( $item->classes ?? array() );
		$current = in_array( 'current-menu-item', $classes, true )
			|| in_array( 'current_page_item', $classes, true )
			|| in_array( 'current-menu-parent', $classes, true );

		$output .= sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $item->url ?? '' ),
			$current ? ' aria-current="page"' : '',
			$item->target ? sprintf( ' target="%s" rel="noopener"', esc_attr( $item->target ) ) : '',
			esc_html( $item->title ?? '' )
		);
	}

	/**
	 * Nothing to close: start_el emitted a complete element.
	 *
	 * @param string    $output      Accumulated markup, by reference.
	 * @param \WP_Post  $data_object Menu item.
	 * @param int       $depth       Nesting depth.
	 * @param \stdClass $args        wp_nav_menu arguments.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ): void {}

	/**
	 * Submenus are not part of this design.
	 *
	 * @param string    $output Accumulated markup, by reference.
	 * @param int       $depth  Nesting depth.
	 * @param \stdClass $args   wp_nav_menu arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {}

	/**
	 * Submenus are not part of this design.
	 *
	 * @param string    $output Accumulated markup, by reference.
	 * @param int       $depth  Nesting depth.
	 * @param \stdClass $args   wp_nav_menu arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ): void {}
}
