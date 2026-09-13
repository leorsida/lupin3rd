<?php
/**
 * Closing the surfaces a public WordPress leaves open.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * A magazine that publishes about surveillance is a target, and its readers
 * have a reason to care how it behaves.
 *
 * What is enforced here is what a theme can enforce. The rest — a separate
 * admin hostname, executable uploads refused by the web server, request logs
 * without full addresses — lives in configuration, and is listed in
 * docs/llmwiki/10-security-and-privacy.md so that nobody assumes otherwise.
 */
final class Security {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'wp_headers', array( $this, 'remove_pingback_header' ) );
		add_filter( 'rest_authentication_errors', array( $this, 'close_rest_to_anonymous_users' ) );
		add_filter( 'rest_endpoints', array( $this, 'remove_user_endpoints' ) );
		add_action( 'template_redirect', array( $this, 'block_author_enumeration' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'strip_upload_metadata' ) );
		add_filter( 'pre_get_avatar', array( $this, 'replace_gravatar' ), 10, 3 );
		add_filter( 'get_avatar_url', array( $this, 'block_gravatar_url' ) );
		add_filter( 'comment_flood_filter', '__return_false' );
	}

	/**
	 * Drop the pingback header that advertises XML-RPC.
	 *
	 * @param array<string, string> $headers Response headers.
	 * @return array<string, string>
	 */
	public function remove_pingback_header( array $headers ): array {
		unset( $headers['X-Pingback'] );

		return $headers;
	}

	/**
	 * Refuse REST requests from anonymous callers.
	 *
	 * @param \WP_Error|null|true $result Current authentication result.
	 * @return \WP_Error|null|true
	 */
	public function close_rest_to_anonymous_users( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		if ( is_user_logged_in() ) {
			return $result;
		}

		/**
		 * Filters whether anonymous REST access is allowed.
		 *
		 * A block or an integration that genuinely needs an open endpoint can
		 * open that endpoint, deliberately, here.
		 *
		 * @param bool $allowed Whether to allow the request.
		 */
		if ( apply_filters( 'lupin3rd_allow_anonymous_rest', false ) ) {
			return $result;
		}

		return new \WP_Error(
			'l3_rest_forbidden',
			__( 'API non disponibile agli utenti non autenticati.', 'lupin3rd' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Remove the user endpoints entirely.
	 *
	 * @param array<string, mixed> $endpoints REST endpoints.
	 * @return array<string, mixed>
	 */
	public function remove_user_endpoints( array $endpoints ): array {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );

		return $endpoints;
	}

	/**
	 * Stop ?author=1 from revealing a pseudonym.
	 */
	public function block_author_enumeration(): void {
		if ( is_admin() || ! isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}

	/**
	 * Strip metadata from an uploaded image before thumbnails are generated.
	 *
	 * A magazine that publishes tutorials about EXIF should not leak it.
	 *
	 * @param array<string, mixed> $file Uploaded file descriptor.
	 * @return array<string, mixed>
	 */
	public function strip_upload_metadata( array $file ): array {
		$types = array( 'image/jpeg', 'image/png', 'image/webp' );

		if ( ! isset( $file['type'], $file['tmp_name'] ) || ! in_array( $file['type'], $types, true ) ) {
			return $file;
		}

		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			return $file;
		}

		$contents = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $contents ) {
			return $file;
		}

		$image = @imagecreatefromstring( $contents ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( false === $image ) {
			return $file;
		}

		// Re-encoding drops every metadata block: EXIF, GPS, XMP, IPTC.
		imagealphablending( $image, false );
		imagesavealpha( $image, true );

		switch ( $file['type'] ) {
			case 'image/png':
				imagepng( $image, $file['tmp_name'], 9 );
				break;
			case 'image/webp':
				imagewebp( $image, $file['tmp_name'], 90 );
				break;
			default:
				imagejpeg( $image, $file['tmp_name'], 90 );
		}

		imagedestroy( $image );

		return $file;
	}

	/**
	 * Replace Gravatar with a locally generated pattern.
	 *
	 * Gravatar would send a hash of the reader's email address to a third party
	 * on every page view.
	 *
	 * @param string|null          $avatar Current avatar markup.
	 * @param mixed                $id_or_email User identifier.
	 * @param array<string, mixed> $args   Avatar arguments.
	 */
	public function replace_gravatar( $avatar, $id_or_email, $args ) {
		$seed    = '';
		$initial = '';

		if ( $id_or_email instanceof \WP_Comment ) {
			$seed    = $id_or_email->comment_author ?: 'anon';
			$initial = $id_or_email->comment_author;
		} elseif ( is_numeric( $id_or_email ) ) {
			$user    = get_userdata( (int) $id_or_email );
			$seed    = $user ? $user->user_login : 'anon';
			$initial = $seed;
		} elseif ( is_string( $id_or_email ) ) {
			$seed    = $id_or_email;
			$initial = $id_or_email;
		}

		$size = isset( $args['size'] ) && (int) $args['size'] <= 32 ? 'avatar-sm' : '';

		ob_start();
		Marks::render_avatar( $seed ?: 'anon', strtoupper( substr( $initial ?: 'anon', 0, 2 ) ), $size );

		return (string) ob_get_clean();
	}

	/**
	 * Never hand out a gravatar.com URL.
	 *
	 * @param string $url Avatar URL.
	 */
	public function block_gravatar_url( string $url ): string {
		return str_contains( $url, 'gravatar.com' ) ? '' : $url;
	}
}
