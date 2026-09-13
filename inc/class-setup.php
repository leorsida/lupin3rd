<?php
/**
 * Theme support, menus and sidebars.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Declares what the theme supports and what WordPress may render for it.
 */
final class Setup {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'after_setup_theme', array( $this, 'add_theme_support' ) );
		add_action( 'init', array( $this, 'register_menus' ) );
		add_action( 'after_switch_theme', array( $this, 'flush_rewrite_rules' ) );
		add_filter( 'body_class', array( $this, 'drop_colliding_body_classes' ) );
	}

	/**
	 * Component names WordPress also puts on the body element.
	 *
	 * The design system names its components plainly — `.tag`, `.post`,
	 * `.card`, `.row` — and WordPress puts equally plain names on <body>. Where
	 * the two meet, a whole page inherits a component's styling: on a tag
	 * archive the body matched `.tag`, became `display: inline-flex`, and the
	 * page collapsed into the width of a pill.
	 *
	 * Nothing is lost by dropping these: the specific classes beside them
	 * (`archive`, `tag-forense`, `tag-17`) carry everything a stylesheet or a
	 * plugin would target.
	 *
	 * @var array<int, string>
	 */
	private const COLLIDING_BODY_CLASSES = array( 'tag', 'post', 'card', 'row', 'meta', 'mark', 'section', 'container' );

	/**
	 * Remove body classes that collide with a component name.
	 *
	 * @param array<int, string> $classes Body classes.
	 * @return array<int, string>
	 */
	public function drop_colliding_body_classes( array $classes ): array {
		/**
		 * Filters the component names that must not survive on the body.
		 *
		 * Extend this when the design system gains a plainly named component
		 * that WordPress might also emit.
		 *
		 * @param array<int, string> $names Class names to strip.
		 */
		$colliding = (array) apply_filters( 'l3_colliding_body_classes', self::COLLIDING_BODY_CLASSES );

		return array_values( array_diff( $classes, $colliding ) );
	}

	/**
	 * Declare theme support.
	 */
	public function add_theme_support(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'align-wide' );

		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
		);

		// The editor has to look like the published page.
		add_editor_style( 'css/editor.css' );

		/*
		 * Featured images are optional: an article without one gets a
		 * generative mark instead. See Marks.
		 */
		add_image_size( 'l3-feature', 1280, 720, true );
		add_image_size( 'l3-card', 640, 400, true );
	}

	/**
	 * Register the navigation menus.
	 */
	public function register_menus(): void {
		register_nav_menus(
			array(
				'primary'  => __( 'Navigazione principale', 'lupin3rd' ),
				'sections' => __( 'Piè di pagina — sezioni', 'lupin3rd' ),
				'desk'     => __( 'Piè di pagina — redazione', 'lupin3rd' ),
				'channels' => __( 'Piè di pagina — canali', 'lupin3rd' ),
			)
		);
	}

	/**
	 * Flush rewrite rules once, when the theme is activated.
	 *
	 * Never on a normal request: it is one of the most expensive things a
	 * theme can do.
	 */
	public function flush_rewrite_rules(): void {
		Content_Types::instance()->register();
		flush_rewrite_rules();
	}
}
