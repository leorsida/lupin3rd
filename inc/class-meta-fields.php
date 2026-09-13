<?php
/**
 * Custom fields.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every custom field in code.
 *
 * No field plugin: the content model lives in the theme repository, where it
 * can be read, reviewed and versioned like the rest of the code.
 */
final class Meta_Fields {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ), 20 );
	}

	/**
	 * Register the fields for each post type.
	 */
	public function register(): void {
		foreach ( $this->news_fields() as $key => $args ) {
			$this->register_field( 'post', $key, $args );
		}

		foreach ( $this->tutorial_fields() as $key => $args ) {
			$this->register_field( Content_Types::TUTORIAL, $key, $args );
		}

		foreach ( $this->dossier_fields() as $key => $args ) {
			$this->register_field( Content_Types::DOSSIER, $key, $args );
		}
	}

	/**
	 * Fields on a news item.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function news_fields(): array {
		return array(
			'l3_primary_source'      => array(
				'type'        => 'string',
				'description' => 'URL of the primary source being reported on.',
				'sanitize'    => 'esc_url_raw',
			),
			'l3_verification_status' => array(
				'type'        => 'string',
				'description' => 'One of verified, partial, unverified.',
				'sanitize'    => array( $this, 'sanitize_verification_status' ),
			),
			'l3_last_reviewed'       => array(
				'type'        => 'string',
				'description' => 'Date the claims were last checked, as Y-m-d.',
				'sanitize'    => array( $this, 'sanitize_date' ),
			),
		);
	}

	/**
	 * Fields on a tutorial.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function tutorial_fields(): array {
		return array(
			'l3_estimated_time' => array(
				'type'        => 'integer',
				'description' => 'Estimated time in minutes; feeds HowTo.totalTime.',
				'sanitize'    => 'absint',
			),
			'l3_prerequisites'  => array(
				'type'        => 'string',
				'description' => 'One prerequisite per line; feeds HowTo.supply.',
				'sanitize'    => array( $this, 'sanitize_lines' ),
			),
			'l3_lab_repository' => array(
				'type'        => 'string',
				'description' => 'URL of the repository holding the lab.',
				'sanitize'    => 'esc_url_raw',
			),
			'l3_sample_hash'    => array(
				'type'        => 'string',
				'description' => 'SHA-256 of the sample file.',
				'sanitize'    => array( $this, 'sanitize_hash' ),
			),
		);
	}

	/**
	 * Fields on a dossier.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function dossier_fields(): array {
		return array(
			'l3_method_note' => array(
				'type'        => 'string',
				'description' => 'Methodology note. Mandatory on a dossier.',
				'sanitize'    => 'wp_kses_post',
			),
		);
	}

	/**
	 * Register one field.
	 *
	 * @param string               $post_type Post type to attach it to.
	 * @param string               $key       Meta key.
	 * @param array<string, mixed> $args      Field definition.
	 */
	private function register_field( string $post_type, string $key, array $args ): void {
		register_post_meta(
			$post_type,
			$key,
			array(
				'type'              => $args['type'],
				'description'       => $args['description'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $args['sanitize'],
				'auth_callback'     => static fn (): bool => current_user_can( 'edit_posts' ),
			)
		);
	}

	/**
	 * Keep the verification status to the three values that mean something.
	 *
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_verification_status( $value ): string {
		$value = sanitize_key( (string) $value );

		return in_array( $value, array( 'verified', 'partial', 'unverified' ), true ) ? $value : '';
	}

	/**
	 * Accept a Y-m-d date and nothing else.
	 *
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_date( $value ): string {
		$value = sanitize_text_field( (string) $value );

		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	/**
	 * Accept a hex digest and nothing else.
	 *
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_hash( $value ): string {
		$value = strtolower( trim( (string) $value ) );

		return preg_match( '/^[a-f0-9]{32,128}$/', $value ) ? $value : '';
	}

	/**
	 * Clean a newline-separated list.
	 *
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_lines( $value ): string {
		$lines = array_filter( array_map( 'sanitize_text_field', preg_split( '/\R/', (string) $value ) ?: array() ) );

		return implode( "\n", $lines );
	}

	/**
	 * Read a field, with a default.
	 *
	 * @param string $key     Meta key.
	 * @param mixed  $fallback Value to return when the field is empty.
	 * @param int    $post_id Post to read from; current post when 0.
	 * @return mixed
	 */
	public static function get( string $key, $fallback = '', int $post_id = 0 ) {
		$post_id = $post_id > 0 ? $post_id : (int) get_the_ID();

		if ( ! $post_id ) {
			return $fallback;
		}

		$value = get_post_meta( $post_id, $key, true );

		return ( '' === $value || null === $value ) ? $fallback : $value;
	}
}
