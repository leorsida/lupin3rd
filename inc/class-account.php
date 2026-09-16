<?php
/**
 * The private half of an account.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * /account/ is the one page on this site that is about the reader rather than
 * about what the desk published. It is never cached, never public, and never
 * takes an account identifier from the request: every handler here writes to
 * `get_current_user_id()` and nothing else. That is not a check that can fail —
 * there is no parameter to tamper with, so there is nothing to get wrong.
 *
 * The prototype puts these fields in a modal opened from the public profile.
 * Here they are a page, because a modal is a control that needs a script and
 * this form has to work without one.
 */
final class Account {

	use Singleton;

	/**
	 * Form action that saves the public profile.
	 */
	public const ACTION_PROFILE = 'l3_save_profile';

	/**
	 * Form action that changes the address or the password.
	 */
	public const ACTION_CREDENTIALS = 'l3_save_credentials';

	/**
	 * Query argument carrying the outcome back to the page.
	 */
	public const NOTICE_VAR = 'l3_account';

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'admin_post_' . self::ACTION_PROFILE, array( $this, 'save_profile' ) );
		add_action( 'admin_post_' . self::ACTION_CREDENTIALS, array( $this, 'save_credentials' ) );

		/*
		 * No nopriv twin for either: a form on a page only a signed-in reader
		 * can open has no anonymous caller to serve, and registering one would
		 * be inventing a door nobody asked for.
		 */
		add_action( 'template_redirect', array( $this, 'close_to_strangers' ) );
	}

	/**
	 * Send a stranger who asks for /account/ to the sign-in page.
	 */
	public function close_to_strangers(): void {
		if ( is_user_logged_in() || ! is_page_template( 'page-templates/template-account.php' ) ) {
			return;
		}

		wp_safe_redirect( Authentication::sign_in_url( (string) get_permalink() ) );
		exit;
	}

	/**
	 * Save what the account says about itself in public.
	 */
	public function save_profile(): void {
		$user_id = $this->reader( self::ACTION_PROFILE );
		$fields  = Profile_Fields::instance();

		$pseudonym = $fields->sanitize_pseudonym( sanitize_text_field( wp_unslash( $_POST['l3_pseudonym'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in reader().
		$current   = Profile_Fields::pseudonym( $user_id );

		if ( '' !== $pseudonym && $pseudonym !== $current ) {
			if ( ! Profile_Fields::pseudonym_is_free( $pseudonym, $user_id ) ) {
				$this->go_back( 'taken' );
			}

			/*
			 * The old one is held for ninety days rather than released. A
			 * pseudonym that somebody else can pick up the same afternoon is a
			 * way of inheriting a reputation somebody else earned.
			 */
			Profile_Fields::retire_pseudonym( $current );
			update_user_meta( $user_id, Profile_Fields::PSEUDONYM, $pseudonym );
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $pseudonym,
					'nickname'     => $pseudonym,
				)
			);
		}

		update_user_meta( $user_id, Profile_Fields::BIO, $fields->sanitize_bio( sanitize_textarea_field( wp_unslash( $_POST['l3_bio'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in reader().
		update_user_meta( $user_id, Profile_Fields::LANGUAGES, $fields->sanitize_languages( sanitize_text_field( wp_unslash( $_POST['l3_languages'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in reader().
		update_user_meta( $user_id, Profile_Fields::SHOW_JOIN_DATE, isset( $_POST['l3_show_join_date'] ) ? '1' : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in reader().
		update_user_meta( $user_id, Profile_Fields::NOTIFY_REPLIES, isset( $_POST['l3_notify_replies'] ) ? '1' : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in reader().

		$fingerprint = $fields->sanitize_fingerprint( sanitize_text_field( wp_unslash( $_POST['l3_pgp'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in reader().

		if ( Profile_Fields::fingerprint( $user_id ) !== $fingerprint ) {
			// A changed key is an unverified key until the desk signs it again.
			update_user_meta( $user_id, Profile_Fields::PGP_VERIFIED, '' );
			update_user_meta( $user_id, Profile_Fields::PGP, $fingerprint );
		}

		$this->go_back( 'saved' );
	}

	/**
	 * Change the address or the password, with the current password as proof.
	 */
	public function save_credentials(): void {
		$user_id = $this->reader( self::ACTION_CREDENTIALS );
		$user    = get_userdata( $user_id );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- A password is hashed and compared, never rendered.
		$proof = isset( $_POST['l3_current_password'] ) ? (string) wp_unslash( $_POST['l3_current_password'] ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Same.
		$fresh = isset( $_POST['l3_new_password'] ) ? (string) wp_unslash( $_POST['l3_new_password'] ) : '';
		$email = sanitize_email( wp_unslash( $_POST['l3_email'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in reader().

		/*
		 * The current password, and not merely the session. A stolen session is
		 * a bad afternoon; a stolen session that can change the address and the
		 * password is a lost account.
		 */
		if ( ! $user instanceof \WP_User || ! wp_check_password( $proof, $user->user_pass, $user_id ) ) {
			$this->go_back( 'wrong_password' );
		}

		if ( '' !== $email && $email !== $user->user_email ) {
			if ( ! is_email( $email ) || email_exists( $email ) ) {
				$this->go_back( 'email' );
			}

			wp_update_user(
				array(
					'ID'         => $user_id,
					'user_email' => $email,
				)
			);
		}

		if ( '' !== $fresh ) {
			if ( strlen( $fresh ) < Authentication::MIN_PASSWORD ) {
				$this->go_back( 'short_password' );
			}

			wp_set_password( $fresh, $user_id );

			/*
			 * wp_set_password() signs every device out, this one included. Give
			 * the reader their own session back rather than dropping them on
			 * the sign-in page for having changed their password.
			 */
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, true, is_ssl() );
		}

		$this->go_back( 'saved' );
	}

	/**
	 * The signed-in reader, or no further.
	 *
	 * @param string $action Form action being handled.
	 */
	private function reader( string $action ): int {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $action ) || ! is_user_logged_in() ) {
			$this->go_back( 'error' );
		}

		return get_current_user_id();
	}

	/**
	 * Back to the account page, with the outcome.
	 *
	 * @param string $outcome What happened.
	 */
	private function go_back( string $outcome ): void {
		wp_safe_redirect( add_query_arg( self::NOTICE_VAR, $outcome, Authentication::account_url() ) );
		exit;
	}

	/**
	 * The message to show for the outcome carried in the URL, if any.
	 *
	 * @return array{tone: string, text: string}|null
	 */
	public static function notice(): ?array {
		$outcome = isset( $_GET[ self::NOTICE_VAR ] ) ? sanitize_key( wp_unslash( (string) $_GET[ self::NOTICE_VAR ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a message, not acting on it.

		return match ( $outcome ) {
			'saved' => array(
				'tone' => 'ok',
				'text' => __( 'Salvato.', 'lupin3rd' ),
			),
			'unfollowed' => array(
				'tone' => 'ok',
				'text' => __( 'Fatto: non ti avvisiamo più quando una discussione a cui hai partecipato si muove. La casella qui sotto lo riaccende.', 'lupin3rd' ),
			),
			'taken' => array(
				'tone' => 'error',
				'text' => __( 'Quello pseudonimo è già di qualcuno, o è stato lasciato da meno di novanta giorni.', 'lupin3rd' ),
			),
			'wrong_password' => array(
				'tone' => 'error',
				'text' => __( 'La password attuale non corrisponde. Non è stato cambiato niente.', 'lupin3rd' ),
			),
			'short_password' => array(
				'tone' => 'error',
				/* translators: %d: minimum number of characters. */
				'text' => sprintf( __( 'La nuova password deve essere lunga almeno %d caratteri.', 'lupin3rd' ), Authentication::MIN_PASSWORD ),
			),
			'email' => array(
				'tone' => 'error',
				'text' => __( 'Quell\'indirizzo non è valido, o è già usato da un altro account.', 'lupin3rd' ),
			),
			'error' => array(
				'tone' => 'error',
				'text' => __( 'Non ha funzionato. Ricarica la pagina e riprova.', 'lupin3rd' ),
			),
			default => null,
		};
	}
}
