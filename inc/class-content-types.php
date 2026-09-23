<?php
/**
 * Post types and taxonomies.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the content model: three post types, three taxonomies.
 *
 * Public slugs stay Italian because they are content and they are indexed.
 * Every identifier around them is English.
 */
final class Content_Types {

	use Singleton;

	/**
	 * Post type holding a step-by-step piece with a reproducible lab.
	 */
	public const TUTORIAL = 'tutorial';

	/**
	 * Post type holding a serialised investigation.
	 */
	public const DOSSIER = 'dossier';

	/**
	 * Taxonomy holding the three difficulty levels.
	 */
	public const DIFFICULTY = 'difficulty';

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );

		/*
		 * The public colophon promises /categoria/<slug>/. WordPress defaults
		 * to /category/, and the base is an option somebody can change by
		 * accident, so the theme states it in code instead. The tag base is
		 * already /tag/ and is left alone.
		 */
		add_filter( 'pre_option_category_base', static fn (): string => 'categoria' );
	}

	/**
	 * Register every post type and taxonomy.
	 */
	public function register(): void {
		$this->register_tutorial();
		$this->register_dossier();
		$this->register_difficulty();
		$this->extend_category();
	}

	/**
	 * Return the post types that carry editorial content.
	 *
	 * @return array<int, string>
	 */
	public static function editorial_types(): array {
		return array( 'post', self::TUTORIAL, self::DOSSIER );
	}

	/**
	 * Human label for a post type, used in card metadata.
	 *
	 * @param string $type Post type name.
	 */
	public static function label( string $type ): string {
		return match ( $type ) {
			self::TUTORIAL => __( 'Tutorial', 'lupin3rd' ),
			self::DOSSIER  => __( 'Dossier', 'lupin3rd' ),
			default        => __( 'Notizia', 'lupin3rd' ),
		};
	}

	/**
	 * Register the tutorial post type.
	 */
	private function register_tutorial(): void {
		register_post_type(
			self::TUTORIAL,
			array(
				'labels'        => array(
					'name'          => __( 'Tutorial', 'lupin3rd' ),
					'singular_name' => __( 'Tutorial', 'lupin3rd' ),
					'add_new_item'  => __( 'Aggiungi tutorial', 'lupin3rd' ),
					'edit_item'     => __( 'Modifica tutorial', 'lupin3rd' ),
				),

				/*
				 * Not decoration: Seo reads this for the meta description of
				 * the archive, which otherwise has none — an archive is built
				 * by a template and has no text of its own to summarise.
				 */
				'description'   => __( 'Guide pratiche passo per passo: reverse engineering, hardware, privacy e sicurezza, con i prerequisiti dichiarati e il perimetro legale scritto in chiaro.', 'lupin3rd' ),
				'public'        => true,
				'has_archive'   => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-editor-code',
				'menu_position' => 6,
				'supports'      => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'comments', 'custom-fields' ),
				'taxonomies'    => array( 'category', 'post_tag' ),
				'rewrite'       => array(
					'slug'       => 'tutorial',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Register the dossier post type.
	 *
	 * Hierarchical: the parent is the index, the children are the instalments,
	 * ordered by hand.
	 */
	private function register_dossier(): void {
		register_post_type(
			self::DOSSIER,
			array(
				'labels'        => array(
					'name'          => __( 'Dossier', 'lupin3rd' ),
					'singular_name' => __( 'Dossier', 'lupin3rd' ),
					'add_new_item'  => __( 'Aggiungi dossier', 'lupin3rd' ),
					'edit_item'     => __( 'Modifica dossier', 'lupin3rd' ),
				),
				'description'   => __( 'Inchieste in più puntate: una domanda alla volta, le fonti in fondo a ogni puntata e le correzioni che restano.', 'lupin3rd' ),
				'public'        => true,
				'hierarchical'  => true,
				'has_archive'   => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-portfolio',
				'menu_position' => 7,
				'supports'      => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'comments', 'page-attributes', 'custom-fields' ),
				'taxonomies'    => array( 'category', 'post_tag' ),
				'rewrite'       => array(
					'slug'       => 'dossier',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Register the difficulty taxonomy.
	 *
	 * Terms are rendered as filled blocks, never as colour, so the level
	 * survives colour blindness and monochrome printing.
	 */
	private function register_difficulty(): void {
		register_taxonomy(
			self::DIFFICULTY,
			array( self::TUTORIAL ),
			array(
				'labels'            => array(
					'name'          => __( 'Difficoltà', 'lupin3rd' ),
					'singular_name' => __( 'Difficoltà', 'lupin3rd' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'difficolta',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Attach the native taxonomies to the custom post types.
	 */
	private function extend_category(): void {
		foreach ( array( self::TUTORIAL, self::DOSSIER ) as $type ) {
			register_taxonomy_for_object_type( 'category', $type );
			register_taxonomy_for_object_type( 'post_tag', $type );
		}
	}

	/**
	 * Return the three difficulty levels, weakest first.
	 *
	 * @return array<string, string>
	 */
	public static function difficulty_levels(): array {
		return array(
			'base'     => __( 'Base', 'lupin3rd' ),
			'medio'    => __( 'Medio', 'lupin3rd' ),
			'avanzato' => __( 'Avanzato', 'lupin3rd' ),
		);
	}
}
