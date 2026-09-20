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

		/*
		 * Priority 1: core's redirect_canonical() sits on the same hook at 10 and
		 * is registered first, so at the default priority this guard would run
		 * after the 301 to /author/<pseudonym>/ had already been sent.
		 */
		add_action( 'template_redirect', array( $this, 'block_author_enumeration' ), 1 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'strip_upload_metadata' ) );

		/*
		 * Core stores the commenter's address and browser string with every
		 * comment, and keeps both for as long as the comment exists. Writing
		 * nothing is the only version of that promise with no clock attached,
		 * and it costs an anti-spam signal this site had already decided not
		 * to use. The whole argument is in docs/llmwiki/17-privacy.md.
		 */
		add_filter( 'pre_comment_user_ip', '__return_empty_string' );
		add_filter( 'pre_comment_user_agent', '__return_empty_string' );
		add_filter( 'pre_get_avatar', array( $this, 'replace_gravatar' ), 10, 3 );
		add_filter( 'get_avatar_url', array( $this, 'block_gravatar_url' ) );
		add_filter( 'comment_flood_filter', '__return_false' );

		/*
		 * REST reads comment_author straight off the row, without passing
		 * through get_comment_author() and therefore without the filter that
		 * renames a closed account. The comment feeds do go through it — they
		 * call get_comment_author_rss(), which wraps get_comment_author() —
		 * so this is the only hole of the two, and it was measured rather than
		 * assumed. See docs/PIANO-CANCELLAZIONE-ACCOUNT.md §5.
		 */
		add_filter( 'rest_prepare_comment', array( $this, 'name_the_author_in_rest' ), 10, 2 );

		/*
		 * Bracket the admin bar's construction: everything core builds between
		 * these two gets no avatar.
		 *
		 * The closing hook is `wp_before_admin_bar_render`, which fires once
		 * `admin_bar_menu` has finished, rather than a high priority on
		 * `admin_bar_menu` itself. The nodes that carry an avatar sit at 0 and
		 * 9991 in WordPress 7.1, and there is no reason those numbers cannot
		 * move: a closing priority is a guess about core's internals, while
		 * «after that action» is a fact.
		 */
		add_action( 'admin_bar_menu', array( $this, 'start_hiding_avatars' ), -9999 );
		add_action( 'wp_before_admin_bar_render', array( $this, 'stop_hiding_avatars' ) );
	}

	/**
	 * No face in the admin bar.
	 *
	 * This site does not have avatars of people: what `replace_gravatar()`
	 * draws is a pattern derived from a random seed, which says nothing about
	 * anybody and is there so that a comment thread is scannable. In the admin
	 * bar it says nothing at all — the name is right next to it — and it
	 * arrives as markup that core expects to be a 26px `<img>`, which is why it
	 * lands where it should not.
	 *
	 * Core handles an empty avatar properly: `wp_admin_bar_my_account_item()`
	 * drops the `with-avatar` class and leaves the greeting alone.
	 *
	 * It is also the theme's `.avatar` rules that make the thing a bordered
	 * box in the first place: they apply inside `#wpadminbar` like anywhere
	 * else, and core sizes that slot for a 28px image.
	 */
	public function start_hiding_avatars(): void {
		add_filter( 'pre_get_avatar', array( $this, 'no_avatar' ), 99 );
	}

	/**
	 * Put them back for the rest of the request — comment threads need them.
	 */
	public function stop_hiding_avatars(): void {
		remove_filter( 'pre_get_avatar', array( $this, 'no_avatar' ), 99 );
	}

	/**
	 * An empty string short-circuits `get_avatar()`; null would let it carry on.
	 */
	public function no_avatar(): string {
		return '';
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
	public function replace_gravatar( $avatar, $id_or_email, $args ): string {
		$seed    = '';
		$initial = '';
		$user    = null;

		if ( $id_or_email instanceof \WP_User ) {
			$user = $id_or_email;
		} elseif ( $id_or_email instanceof \WP_Comment ) {
			$user = (int) $id_or_email->user_id > 0 ? get_userdata( (int) $id_or_email->user_id ) : null;

			if ( ! $user instanceof \WP_User ) {
				$seed    = $id_or_email->comment_author ?: 'anon';
				$initial = $id_or_email->comment_author;
			}
		} elseif ( is_numeric( $id_or_email ) ) {
			$user = get_userdata( (int) $id_or_email );
		} elseif ( is_string( $id_or_email ) ) {
			$seed    = $id_or_email;
			$initial = $id_or_email;
		}

		/*
		 * For an account the seed is the stored random one, never the login and
		 * never the address: a pattern derived from an email address is that
		 * address, hashed, on public display — which is the whole objection to
		 * Gravatar.
		 */
		if ( $user instanceof \WP_User ) {
			$seed    = Profile_Fields::avatar_seed( $user );
			$initial = Profile_Fields::pseudonym( $user );
		}

		/*
		 * A closed account keeps its seed in the database — nothing is
		 * overwritten — so the pattern would go on being drawn, and a pattern
		 * is an identifier: the same shape beside every comment somebody ever
		 * left is exactly what the pseudonym no longer says.
		 */
		if ( $user instanceof \WP_User && Retirement::is_closed( $user ) ) {
			$seed    = 'anon';
			$initial = '';
		}

		$size = isset( $args['size'] ) && (int) $args['size'] <= 32 ? 'avatar-sm' : '';

		ob_start();
		Marks::render_avatar( $seed ?: 'anon', strtolower( substr( $initial ?: 'anon', 0, 2 ) ), $size );

		return (string) ob_get_clean();
	}

	/**
	 * Let REST answer with the same author name as every other surface.
	 *
	 * Both parameters are `mixed` because that is what a filter can hand over,
	 * and the return type follows: this passes back whatever it was given when
	 * it is not the pair it knows how to rewrite.
	 *
	 * @param mixed $response Response so far.
	 * @param mixed $comment  Comment being described.
	 * @return mixed
	 */
	public function name_the_author_in_rest( $response, $comment ) {
		if ( ! $response instanceof \WP_REST_Response || ! $comment instanceof \WP_Comment ) {
			return $response;
		}

		$data = $response->get_data();

		if ( is_array( $data ) && isset( $data['author_name'] ) ) {
			$data['author_name'] = get_comment_author( $comment );

			$response->set_data( $data );
		}

		return $response;
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
