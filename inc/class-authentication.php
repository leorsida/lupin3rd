<?php
/**
 * The front door: registration, sign-in and sign-out, in the theme.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * A reader never sees wp-login.php.
 *
 * Both forms are real POSTs to admin-post.php with a nonce, so they work with
 * JavaScript off — the script only saves a round trip. Neither goes through
 * REST: the API is closed to anonymous callers by Security, and opening a hole
 * in it for the two most attacked endpoints on any WordPress site would be an
 * odd way to start.
 *
 * wp-login.php is not redirected away, it is made useless to anybody but the
 * desk: the form still renders, and an account without `edit_posts` is refused
 * there even when the password is right, with a message pointing at /accesso/.
 * A blanket redirect would have read better in the log and been worse in
 * practice — it removes the one door that still opens when the theme is
 * broken, which is exactly when somebody needs to get in. See D-021.
 */
final class Authentication {

	use Singleton;

	/**
	 * Form action that opens an account.
	 */
	public const ACTION_REGISTER = 'l3_register';

	/**
	 * Form action that opens a session.
	 */
	public const ACTION_SIGN_IN = 'l3_sign_in';

	/**
	 * Action that ends one.
	 */
	public const ACTION_SIGN_OUT = 'l3_sign_out';

	/**
	 * Query argument carrying the outcome back to the page.
	 */
	public const NOTICE_VAR = 'l3_auth';

	/**
	 * Query argument naming the panel to open.
	 */
	public const PANEL_VAR = 'l3_panel';

	/**
	 * Ten characters, as the form promises.
	 */
	public const MIN_PASSWORD = 10;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		foreach ( array( self::ACTION_REGISTER, self::ACTION_SIGN_IN ) as $action ) {
			add_action( 'admin_post_' . $action, array( $this, 'route' ) );
			add_action( 'admin_post_nopriv_' . $action, array( $this, 'route' ) );
		}

		add_action( 'admin_post_' . self::ACTION_SIGN_OUT, array( $this, 'handle_sign_out' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION_SIGN_OUT, array( $this, 'handle_sign_out' ) );

		// Nobody but the desk gets in through wp-login.php.
		add_filter( 'authenticate', array( $this, 'refuse_readers_at_the_back_door' ), 30, 1 );
		add_action( 'login_init', array( $this, 'send_registration_to_the_front_door' ) );
		add_filter( 'login_message', array( $this, 'explain_the_back_door' ) );

		// Every link WordPress generates points at the theme's own page.
		add_filter( 'login_url', array( $this, 'filter_sign_in_url' ), 10, 2 );
		add_filter( 'register_url', array( $this, 'filter_sign_in_url' ) );
	}

	// --- URLs ---

	/**
	 * The page holding both forms.
	 *
	 * @param string $redirect Where to land afterwards.
	 */
	public static function sign_in_url( string $redirect = '' ): string {
		$url = home_url( '/accesso/' );

		return '' === $redirect ? $url : add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
	}

	/**
	 * The private area, when it is published; the home page until it is.
	 */
	public static function account_url(): string {
		$page = get_page_by_path( 'account' );

		return $page instanceof \WP_Post ? (string) get_permalink( $page ) : home_url( '/' );
	}

	/**
	 * A link that ends the session.
	 *
	 * With the nonce everywhere the page is rendered for one reader. Without
	 * it in the header, which is in every cached page and must be the same for
	 * everybody: there the script appends the nonce from the session cookie.
	 *
	 * @param string $redirect Where to land afterwards.
	 * @param bool   $signed   Whether to put the nonce in the URL.
	 */
	public static function sign_out_url( string $redirect = '', bool $signed = true ): string {
		$url = add_query_arg( 'action', self::ACTION_SIGN_OUT, admin_url( 'admin-post.php' ) );

		if ( '' !== $redirect ) {
			$url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
		}

		return $signed ? wp_nonce_url( $url, self::ACTION_SIGN_OUT ) : $url;
	}

	/**
	 * Whether anybody may open an account right now.
	 *
	 * Deliberately not the `users_can_register` option: that checkbox also
	 * opens wp-login.php?action=register, which is a second front door this
	 * theme does not want. Closing registration is a one-line filter.
	 */
	public static function registration_is_open(): bool {
		/**
		 * Filters whether the registration form accepts anything.
		 *
		 * @param bool $open Whether registration is open.
		 */
		return (bool) apply_filters( 'lupin3rd_registration_open', true );
	}

	/**
	 * Point WordPress's own sign-in links at the theme's page.
	 *
	 * @param string $url      URL core built.
	 * @param string $redirect Where core means to land afterwards.
	 */
	public function filter_sign_in_url( $url, $redirect = '' ): string {
		return self::sign_in_url( (string) $redirect );
	}

	// --- The forms ---

	/**
	 * Dispatch a submitted form to the handler that owns it.
	 */
	public function route(): void {
		$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( (string) $_POST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Each handler verifies its own nonce before doing anything.

		if ( self::ACTION_REGISTER === $action ) {
			$this->handle_registration();
			return;
		}

		$this->handle_sign_in();
	}

	/**
	 * Open an account.
	 */
	public function handle_registration(): void {
		$this->check( self::ACTION_REGISTER, 'registrazione' );

		if ( ! self::registration_is_open() ) {
			$this->go_back( 'closed', 'registrazione' );
		}

		$pseudonym = Profile_Fields::instance()->sanitize_pseudonym(
			sanitize_text_field( wp_unslash( $_POST['l3_pseudonym'] ?? '' ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in check().
		);
		$email     = sanitize_email( wp_unslash( $_POST['l3_email'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in check().
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- A password is hashed and compared, never rendered; sanitising it would change what the reader typed.
		$password = isset( $_POST['l3_password'] ) ? (string) wp_unslash( $_POST['l3_password'] ) : '';
		$rules    = isset( $_POST['l3_rules'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in check().

		if ( ! $rules ) {
			$this->go_back( 'rules', 'registrazione' );
		}

		if ( strlen( $pseudonym ) < 3 ) {
			$this->go_back( 'pseudonym', 'registrazione' );
		}

		if ( ! Profile_Fields::pseudonym_is_free( $pseudonym ) ) {
			$this->go_back( 'taken', 'registrazione' );
		}

		if ( ! is_email( $email ) || email_exists( $email ) ) {
			$this->go_back( 'email', 'registrazione' );
		}

		if ( strlen( $password ) < self::MIN_PASSWORD ) {
			$this->go_back( 'password', 'registrazione' );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $pseudonym,
				'user_pass'    => $password,
				'user_email'   => $email,
				'display_name' => $pseudonym,
				'nickname'     => $pseudonym,
				'role'         => Community::ROLE_MEMBER,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$this->go_back( 'error', 'registrazione' );
		}

		$fingerprint = Profile_Fields::instance()->sanitize_fingerprint(
			sanitize_text_field( wp_unslash( $_POST['l3_pgp'] ?? '' ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in check().
		);

		if ( '' !== $fingerprint ) {
			update_user_meta( (int) $user_id, Profile_Fields::PGP, $fingerprint );
		}

		/*
		 * Signed in through wp_signon() and not by setting the cookie here:
		 * that path fires `wp_login`, refreshes the session token and runs the
		 * authentication filters, and all three are somebody else's contract to
		 * keep. Firing a core hook by hand is how a theme ends up owning a
		 * promise it did not write.
		 */
		$signed = wp_signon(
			array(
				'user_login'    => $pseudonym,
				'user_password' => $password,
				'remember'      => true,
			),
			is_ssl()
		);

		if ( is_wp_error( $signed ) ) {
			// The account exists; only the session did not open. Say exactly that.
			$this->go_back( 'created', 'accesso' );
		}

		wp_set_current_user( $signed->ID );
		$this->leave( 'welcome' );
	}

	/**
	 * Open a session for somebody who already has an account.
	 */
	public function handle_sign_in(): void {
		$this->check( self::ACTION_SIGN_IN, 'accesso' );

		$identity = sanitize_text_field( wp_unslash( $_POST['l3_identity'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in check().
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- A password is hashed and compared, never rendered; sanitising it would change what the reader typed.
		$password = isset( $_POST['l3_password'] ) ? (string) wp_unslash( $_POST['l3_password'] ) : '';

		$user = $this->resolve( $identity );

		if ( ! $user instanceof \WP_User || '' === $password ) {
			$this->go_back( 'refused', 'accesso' );
		}

		$signed = wp_signon(
			array(
				'user_login'    => $user->user_login,
				'user_password' => $password,
				'remember'      => true,
			),
			is_ssl()
		);

		if ( is_wp_error( $signed ) ) {
			/*
			 * One message for a wrong password and for an account that does not
			 * exist. Two messages would turn this form into a way of asking
			 * whether a given pseudonym is taken.
			 */
			$this->go_back( 'refused', 'accesso' );
		}

		wp_set_current_user( $signed->ID );
		$this->leave( 'back' );
	}

	/**
	 * End a session.
	 */
	public function handle_sign_out(): void {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::ACTION_SIGN_OUT ) ) {
			/*
			 * Almost always this is the header's link followed without the
			 * script that signs it. Sending the reader to /accesso/, which
			 * carries the real button, turns a control that did nothing into a
			 * control that takes one more click — and leaves the refusal itself
			 * intact for the case it exists for.
			 */
			wp_safe_redirect( self::sign_in_url() );
			exit;
		}

		wp_logout();

		$destination = isset( $_REQUEST['redirect_to'] )
			? wp_validate_redirect( rawurldecode( sanitize_text_field( wp_unslash( (string) $_REQUEST['redirect_to'] ) ) ), home_url( '/' ) )
			: home_url( '/' );

		wp_safe_redirect( $destination );
		exit;
	}

	// --- wp-login.php ---

	/**
	 * Refuse a reader who found the back door, however right their password.
	 *
	 * @param null|\WP_User|\WP_Error $user Result so far.
	 * @return null|\WP_User|\WP_Error
	 */
	public function refuse_readers_at_the_back_door( $user ) {
		if ( ! $user instanceof \WP_User || ! $this->is_wp_login() ) {
			return $user;
		}

		if ( $user->has_cap( 'edit_posts' ) ) {
			return $user;
		}

		return new \WP_Error(
			'l3_use_the_front_door',
			sprintf(
				/* translators: %s: URL of the theme's sign-in page. */
				__( 'I lettori entrano da <a href="%s">accesso</a>. Questa pagina è della redazione.', 'lupin3rd' ),
				esc_url( self::sign_in_url() )
			)
		);
	}

	/**
	 * Send anybody asking wp-login.php to register to the theme's page.
	 */
	public function send_registration_to_the_front_door(): void {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which screen was asked for.

		if ( 'register' !== $action ) {
			return;
		}

		wp_safe_redirect( self::sign_in_url() );
		exit;
	}

	/**
	 * Say on wp-login.php what that page is for.
	 *
	 * @param string $message Message core means to print.
	 */
	public function explain_the_back_door( $message ): string {
		return (string) $message . '<p class="message">' . sprintf(
			/* translators: %s: URL of the theme's sign-in page. */
			wp_kses_post( __( 'Questa pagina è della redazione. I lettori entrano da <a href="%s">accesso</a>.', 'lupin3rd' ) ),
			esc_url( self::sign_in_url() )
		) . '</p>';
	}

	// --- Plumbing ---

	/**
	 * Find an account from a pseudonym or an address.
	 *
	 * @param string $identity What the reader typed.
	 */
	private function resolve( string $identity ): ?\WP_User {
		if ( '' === $identity ) {
			return null;
		}

		if ( is_email( $identity ) ) {
			$user = get_user_by( 'email', $identity );

			return $user instanceof \WP_User ? $user : null;
		}

		$query = new \WP_User_Query(
			array(
				'meta_key'    => Profile_Fields::PSEUDONYM, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $identity,                 // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'      => 1,
				'count_total' => false,
			)
		);

		$found = $query->get_results();

		if ( $found && $found[0] instanceof \WP_User ) {
			return $found[0];
		}

		$user = get_user_by( 'login', $identity );

		return $user instanceof \WP_User ? $user : null;
	}

	/**
	 * Nonce and honeypot, in one place, before anything is read.
	 *
	 * @param string $action Form action being handled.
	 * @param string $panel  Panel to reopen if this ends badly.
	 */
	private function check( string $action, string $panel ): void {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			$this->go_back( 'error', $panel );
		}

		// A field no reader ever sees; filled in, it is a robot.
		$trap = isset( $_POST['l3_website'] ) ? trim( sanitize_text_field( wp_unslash( (string) $_POST['l3_website'] ) ) ) : '';

		if ( '' !== $trap ) {
			$this->go_back( 'error', $panel );
		}
	}

	/**
	 * Where the reader asked to land, when that is somewhere on this site.
	 */
	private function destination(): string {
		$asked = isset( $_POST['redirect_to'] ) ? rawurldecode( sanitize_text_field( wp_unslash( (string) $_POST['redirect_to'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in check().

		return '' === $asked ? self::account_url() : wp_validate_redirect( $asked, self::account_url() );
	}

	/**
	 * Leave the form, signed in.
	 *
	 * @param string $outcome What happened.
	 */
	private function leave( string $outcome ): void {
		wp_safe_redirect( add_query_arg( self::NOTICE_VAR, $outcome, $this->destination() ) );
		exit;
	}

	/**
	 * Go back to the form, with the outcome and the panel that produced it.
	 *
	 * @param string $outcome What happened.
	 * @param string $panel   Panel to reopen.
	 */
	private function go_back( string $outcome, string $panel ): void {
		$url = add_query_arg(
			array(
				self::NOTICE_VAR => $outcome,
				self::PANEL_VAR  => $panel,
			),
			self::sign_in_url()
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Whether this request is being served by wp-login.php.
	 */
	private function is_wp_login(): bool {
		$script = basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ?? '' ) ) );

		return 'wp-login.php' === $script;
	}

	/**
	 * The message to show for the outcome carried in the URL, if any.
	 *
	 * @return array{tone: string, text: string}|null
	 */
	public static function notice(): ?array {
		$outcome = isset( $_GET[ self::NOTICE_VAR ] ) ? sanitize_key( wp_unslash( (string) $_GET[ self::NOTICE_VAR ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a message, not acting on it.

		return match ( $outcome ) {
			'welcome' => array(
				'tone' => 'ok',
				'text' => __( 'Account creato. Sei dentro: i primi tre interventi passano dalla moderazione.', 'lupin3rd' ),
			),
			'back' => array(
				'tone' => 'ok',
				'text' => __( 'Bentornato. Sessione aperta su questo browser.', 'lupin3rd' ),
			),
			'created' => array(
				'tone' => 'ok',
				'text' => __( 'Account creato. Entra con lo pseudonimo e la password che hai appena scelto.', 'lupin3rd' ),
			),
			'refused' => array(
				'tone' => 'error',
				'text' => __( 'Pseudonimo o password non corrispondono.', 'lupin3rd' ),
			),
			'taken' => array(
				'tone' => 'error',
				'text' => __( 'Quello pseudonimo è già di qualcuno. Scegline un altro.', 'lupin3rd' ),
			),
			'pseudonym' => array(
				'tone' => 'error',
				'text' => __( 'Lo pseudonimo va da 3 a 20 caratteri, minuscole, niente spazi.', 'lupin3rd' ),
			),
			'email' => array(
				'tone' => 'error',
				'text' => __( 'Quell\'indirizzo non è valido, o è già usato da un altro account.', 'lupin3rd' ),
			),
			'password' => array(
				'tone' => 'error',
				/* translators: %d: minimum number of characters. */
				'text' => sprintf( __( 'La password deve essere lunga almeno %d caratteri.', 'lupin3rd' ), self::MIN_PASSWORD ),
			),
			'rules' => array(
				'tone' => 'error',
				'text' => __( 'Senza la spunta sulle linee guida non possiamo aprire l\'account.', 'lupin3rd' ),
			),
			'closed' => array(
				'tone' => 'error',
				'text' => __( 'Le registrazioni sono chiuse in questo momento.', 'lupin3rd' ),
			),
			'error' => array(
				'tone' => 'error',
				'text' => __( 'Non ha funzionato. Ricarica la pagina e riprova.', 'lupin3rd' ),
			),
			default => null,
		};
	}

	/**
	 * Which panel the page should open on.
	 *
	 * Signing in, unless the URL asks otherwise. Most of the people who arrive
	 * here already have an account: a new reader opens this page once, everyone
	 * else opens it every time they come back on a new browser.
	 */
	public static function panel(): string {
		$panel = isset( $_GET[ self::PANEL_VAR ] ) ? sanitize_key( wp_unslash( (string) $_GET[ self::PANEL_VAR ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Choosing a tab, not acting on it.

		return 'registrazione' === $panel ? 'registrazione' : 'accesso';
	}
}
