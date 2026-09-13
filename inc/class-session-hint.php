<?php
/**
 * The one thing the page is allowed to know about who is reading it.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Public HTML is identical for every reader (D-017), which leaves the header
 * with a problem: it has to say «Accedi» to a stranger and show a chip to a
 * member, and it cannot ask PHP which one is in front of it without making
 * every page unique.
 *
 * So it does not ask. PHP renders the signed-out header always, and drops a
 * small cookie beside it carrying only what the header needs to draw: a
 * pseudonym, two initials, a pattern name, a path. The script reads that and
 * swaps the two states.
 *
 * Three things about this cookie, in order of how much trouble they save:
 *
 * 1. It is **not** `HttpOnly`, because a script has to read it. That is why it
 *    carries nothing worth stealing. The session itself stays in WordPress's
 *    own cookie, which is `HttpOnly` and is never read by script.
 * 2. It is **not a credential**. The reader can edit it, and editing it
 *    changes what their own header says and nothing else. Nothing anywhere is
 *    authorised by it.
 * 3. It is display data, so it goes into the page as text and never as markup.
 *    `session.js` writes it with `textContent`; the day somebody reaches for
 *    `innerHTML` there, this cookie becomes a cross-site scripting hole that
 *    the reader opens on themselves.
 */
final class Session_Hint {

	use Singleton;

	/**
	 * Cookie the header reads.
	 */
	public const COOKIE = 'l3_hint';

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'wp_login', array( $this, 'remember' ), 10, 2 );
		add_action( 'clear_auth_cookie', array( $this, 'forget' ) );

		/*
		 * Sessions opened before this code existed have no hint, and a changed
		 * pseudonym makes an old one wrong. Both are fixed on the next page the
		 * reader loads rather than on their next sign-in.
		 */
		add_action( 'init', array( $this, 'refresh' ) );
	}

	/**
	 * Write the hint when a session opens.
	 *
	 * @param string $login Login name, as core passes it.
	 * @param mixed  $user  Account that signed in.
	 */
	public function remember( $login, $user = null ): void {
		if ( $user instanceof \WP_User ) {
			$this->write( $user );
		}
	}

	/**
	 * Keep the hint in step with the session and with the profile.
	 */
	public function refresh(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		$user = wp_get_current_user();

		/*
		 * Sanitised although it is only ever compared, never printed: the value
		 * we write is url-encoded JSON, so it passes through untouched, and the
		 * day somebody uses this variable for something else the safe habit is
		 * already in place.
		 */
		$current = isset( $_COOKIE[ self::COOKIE ] )
			? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) )
			: '';

		/*
		 * Both sides pass through the same filter, so whatever it does to a
		 * brace or a quote it does to both and the comparison stays honest.
		 */
		if ( $user->exists() && $current !== sanitize_text_field( $this->value( $user ) ) ) {
			$this->write( $user );
		}
	}

	/**
	 * Drop the hint when the session ends.
	 */
	public function forget(): void {
		if ( headers_sent() ) {
			return;
		}

		setcookie( self::COOKIE, '', $this->options( time() - YEAR_IN_SECONDS ) );
		unset( $_COOKIE[ self::COOKIE ] );
	}

	/**
	 * Send the hint.
	 *
	 * @param \WP_User $user Account to describe.
	 */
	private function write( \WP_User $user ): void {
		if ( headers_sent() ) {
			return;
		}

		$value = $this->value( $user );

		setcookie( self::COOKIE, $value, $this->options( time() + ( 14 * DAY_IN_SECONDS ) ) );
		$_COOKIE[ self::COOKIE ] = $value;
	}

	/**
	 * What the header needs to draw itself, and nothing else.
	 *
	 * @param \WP_User $user Account to describe.
	 */
	private function value( \WP_User $user ): string {
		$seed = Profile_Fields::avatar_seed( $user );

		/*
		 * Plain JSON, not url-encoded: setcookie() encodes the value itself —
		 * that is what setrawcookie() exists to avoid — so encoding it here
		 * would send it encoded twice. The reader's script would then decode
		 * once, get a string that still looks like %7B%22name, fail to parse it
		 * and leave the header saying «Accedi» to somebody who is signed in.
		 * Which is exactly what it did.
		 */
		return (string) wp_json_encode(
			array(
				'name'    => Profile_Fields::pseudonym( $user ),
				'mark'    => Profile_Fields::initials( $user ),
				'pattern' => Marks::avatar_variant( $seed ),
				'profile' => wp_make_link_relative( Community::profile_url( $user ) ),
			)
		);
	}

	/**
	 * Cookie options.
	 *
	 * Not `HttpOnly`: the header script is the only thing that reads it, and it
	 * carries nothing a thief would want. `Lax` because it never travels with a
	 * cross-site request, and it has no business doing so.
	 *
	 * @param int $expires When it should go away.
	 * @return array<string, mixed>
	 */
	private function options( int $expires ): array {
		return array(
			'expires'  => $expires,
			'path'     => (string) ( wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/' ),
			'domain'   => '',
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		);
	}
}
