<?php
/**
 * What an account is allowed to say about itself.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * The fields of a member profile, and the rules that keep them small.
 *
 * The public promise is one sentence long: «Pubblichiamo pseudonimo, ruolo e
 * data di iscrizione. Mai l'email, mai il paese, mai l'ultimo indirizzo usato
 * per entrare.» Everything here exists to make that sentence true by
 * construction — there is no field to hold a country, so no template can leak
 * one.
 */
final class Profile_Fields {

	/**
	 * The pseudonym on display. The login never changes; this does. See D-019.
	 */
	public const PSEUDONYM = 'l3_pseudonym';

	/**
	 * Two lines, plain text, no links.
	 */
	public const BIO = 'l3_bio';

	/**
	 * The fingerprint the account declares.
	 */
	public const PGP = 'l3_pgp_fingerprint';

	/**
	 * Set by the desk after countersigning. Never by the account itself.
	 */
	public const PGP_VERIFIED = 'l3_pgp_verified';

	/**
	 * Random seed the avatar pattern is drawn from.
	 */
	public const AVATAR_SEED = 'l3_avatar_seed';

	/**
	 * Approved contributions; at three the account leaves the queue.
	 */
	public const CONTRIBUTIONS = 'l3_approved_contributions';

	/**
	 * Whether the join date appears on the public profile.
	 */
	public const SHOW_JOIN_DATE = 'l3_show_join_date';

	/**
	 * Languages the account writes in, as a short list of codes.
	 */
	public const LANGUAGES = 'l3_languages';

	/**
	 * Whether to send an email when somebody answers a contribution.
	 */
	public const NOTIFY_REPLIES = 'l3_notify_replies';

	/**
	 * Option holding pseudonyms retired in the last ninety days.
	 */
	public const RETIRED_OPTION = 'l3_retired_pseudonyms';

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'register_fields' ) );
		add_action( 'user_register', array( $this, 'seed_new_account' ) );

		/*
		 * Templates ask for the display name; this is what makes that the
		 * pseudonym everywhere at once, including in markup the theme does not
		 * own — comment lists, feeds, the editor.
		 */
		add_filter( 'get_the_author_display_name', array( $this, 'use_pseudonym' ), 10, 2 );

		/*
		 * A comment keeps the name typed when it was filed. That would make a
		 * changed pseudonym a half-truth — the profile says one name, the
		 * discussion another — and it is also where an account that no longer
		 * exists has to start reading «account rimosso». Both are the same
		 * lookup, so both live here.
		 */
		add_filter( 'get_comment_author', array( $this, 'use_pseudonym_in_comments' ), 10, 3 );

		if ( is_admin() ) {
			add_action( 'show_user_profile', array( $this, 'render_admin_fields' ) );
			add_action( 'edit_user_profile', array( $this, 'render_admin_fields' ) );
			add_action( 'personal_options_update', array( $this, 'save_admin_fields' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_admin_fields' ) );
		}
	}

	/**
	 * Declare every field, with the sanitiser that owns it.
	 */
	public function register_fields(): void {
		$fields = array(
			self::PSEUDONYM      => array( $this, 'sanitize_pseudonym' ),
			self::BIO            => array( $this, 'sanitize_bio' ),
			self::PGP            => array( $this, 'sanitize_fingerprint' ),
			self::LANGUAGES      => array( $this, 'sanitize_languages' ),
			self::AVATAR_SEED    => 'sanitize_key',
			self::PGP_VERIFIED   => 'rest_sanitize_boolean',
			self::SHOW_JOIN_DATE => 'rest_sanitize_boolean',
			self::NOTIFY_REPLIES => 'rest_sanitize_boolean',
			self::CONTRIBUTIONS  => 'absint',
		);

		foreach ( $fields as $key => $sanitizer ) {
			register_meta(
				'user',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'sanitize_callback' => $sanitizer,
					// Nothing about an account is handed out over REST.
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * Give a new account its seed, its pseudonym and its defaults.
	 *
	 * @param int $user_id Account just created.
	 */
	public function seed_new_account( $user_id ): void {
		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );

		if ( ! $user instanceof \WP_User ) {
			return;
		}

		if ( '' === (string) get_user_meta( $user_id, self::AVATAR_SEED, true ) ) {
			update_user_meta( $user_id, self::AVATAR_SEED, wp_generate_password( 16, false, false ) );
		}

		if ( '' === (string) get_user_meta( $user_id, self::PSEUDONYM, true ) ) {
			update_user_meta( $user_id, self::PSEUDONYM, $user->user_login );
		}

		add_user_meta( $user_id, self::SHOW_JOIN_DATE, '1', true );
		add_user_meta( $user_id, self::NOTIFY_REPLIES, '1', true );
		add_user_meta( $user_id, self::CONTRIBUTIONS, '0', true );
	}

	/**
	 * Answer the pseudonym wherever WordPress would answer the display name.
	 *
	 * @param string $display_name Display name WordPress resolved.
	 * @param int    $user_id      Account.
	 */
	public function use_pseudonym( $display_name, $user_id ): string {
		$pseudonym = (string) get_user_meta( (int) $user_id, self::PSEUDONYM, true );

		return '' !== $pseudonym ? $pseudonym : (string) $display_name;
	}

	/**
	 * Sign a comment with the pseudonym the account uses now.
	 *
	 * @param string           $author     Name stored with the comment.
	 * @param string|int       $comment_id Comment identifier.
	 * @param \WP_Comment|null $comment    Comment being read.
	 */
	public function use_pseudonym_in_comments( $author, $comment_id, $comment ): string {
		if ( ! $comment instanceof \WP_Comment || (int) $comment->user_id <= 0 ) {
			return (string) $author;
		}

		$user = get_userdata( (int) $comment->user_id );

		return $user instanceof \WP_User ? self::pseudonym( $user ) : __( 'account rimosso', 'lupin3rd' );
	}

	// --- Reading ---

	/**
	 * The pseudonym on display, falling back to the login.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function pseudonym( $user ): string {
		$user = self::user( $user );

		if ( ! $user instanceof \WP_User ) {
			return __( 'account rimosso', 'lupin3rd' );
		}

		$pseudonym = (string) get_user_meta( $user->ID, self::PSEUDONYM, true );

		return '' !== $pseudonym ? $pseudonym : $user->user_login;
	}

	/**
	 * Two lines about the account, or an empty string.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function bio( $user ): string {
		$user = self::user( $user );

		return $user instanceof \WP_User ? (string) get_user_meta( $user->ID, self::BIO, true ) : '';
	}

	/**
	 * The declared fingerprint, grouped in blocks of four.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function fingerprint( $user ): string {
		$user = self::user( $user );

		return $user instanceof \WP_User ? (string) get_user_meta( $user->ID, self::PGP, true ) : '';
	}

	/**
	 * Whether the desk has countersigned the declared key.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function key_is_verified( $user ): bool {
		$user = self::user( $user );

		if ( ! $user instanceof \WP_User ) {
			return false;
		}

		return '' !== self::fingerprint( $user ) && (bool) get_user_meta( $user->ID, self::PGP_VERIFIED, true );
	}

	/**
	 * The seed the avatar pattern is drawn from.
	 *
	 * The seed is random and stored, not derived from the email: a pattern
	 * derived from an address is a hash of that address on public display.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function avatar_seed( $user ): string {
		$user = self::user( $user );

		if ( ! $user instanceof \WP_User ) {
			return 'anon';
		}

		$seed = (string) get_user_meta( $user->ID, self::AVATAR_SEED, true );

		return '' !== $seed ? $seed : 'u' . $user->ID;
	}

	/**
	 * The two characters drawn inside the avatar.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function initials( $user ): string {
		return strtolower( substr( self::pseudonym( $user ), 0, 2 ) );
	}

	/**
	 * The join date, as a timestamp, or zero when it is not on display.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function joined( $user ): int {
		$user = self::user( $user );

		if ( ! $user instanceof \WP_User ) {
			return 0;
		}

		if ( ! get_user_meta( $user->ID, self::SHOW_JOIN_DATE, true ) ) {
			return 0;
		}

		return (int) strtotime( $user->user_registered );
	}

	/**
	 * Languages the account writes in.
	 *
	 * @param int|\WP_User $user Account.
	 * @return array<int, string>
	 */
	public static function languages( $user ): array {
		$user = self::user( $user );

		if ( ! $user instanceof \WP_User ) {
			return array();
		}

		$raw = (string) get_user_meta( $user->ID, self::LANGUAGES, true );

		return '' === $raw ? array() : array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
	}

	// --- Writing ---

	/**
	 * Reduce a pseudonym to what the prototype promises: 3–20 characters,
	 * lowercase, no spaces.
	 *
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_pseudonym( $value ): string {
		$value = strtolower( (string) $value );
		$value = preg_replace( '/[^a-z0-9._-]/', '', $value );
		$value = is_string( $value ) ? $value : '';

		return substr( $value, 0, 20 );
	}

	/**
	 * A bio is two lines of text. Not markup, not links.
	 *
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_bio( $value ): string {
		$value = wp_strip_all_tags( (string) $value, true );

		return substr( trim( $value ), 0, 400 );
	}

	/**
	 * Keep a fingerprint to hexadecimal, grouped in blocks of four.
	 *
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_fingerprint( $value ): string {
		$digits = strtoupper( (string) preg_replace( '/[^0-9a-fA-F]/', '', (string) $value ) );

		if ( '' === $digits ) {
			return '';
		}

		$digits = substr( $digits, 0, 40 );

		return trim( chunk_split( $digits, 4, ' ' ) );
	}

	/**
	 * Language codes, comma separated, two letters each.
	 *
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_languages( $value ): string {
		$codes = array_map( 'trim', explode( ',', strtolower( (string) $value ) ) );
		$codes = array_filter( $codes, static fn ( string $code ): bool => (bool) preg_match( '/^[a-z]{2}$/', $code ) );

		return implode( ', ', array_slice( array_unique( $codes ), 0, 4 ) );
	}

	/**
	 * Whether a pseudonym is free: not in use, and not retired in the last
	 * ninety days by somebody else.
	 *
	 * @param string $pseudonym Candidate.
	 * @param int    $for_user  Account allowed to keep its own pseudonym.
	 */
	public static function pseudonym_is_free( string $pseudonym, int $for_user = 0 ): bool {
		if ( strlen( $pseudonym ) < 3 ) {
			return false;
		}

		$retired = (array) get_option( self::RETIRED_OPTION, array() );

		if ( isset( $retired[ $pseudonym ] ) && (int) $retired[ $pseudonym ] > time() ) {
			return false;
		}

		$query = new \WP_User_Query(
			array(
				'meta_key'    => self::PSEUDONYM, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $pseudonym,      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'exclude'     => array_filter( array( $for_user ) ),
				'number'      => 1,
				'fields'      => 'ID',
				'count_total' => false,
			)
		);

		return array() === $query->get_results() && ! username_exists( $pseudonym );
	}

	/**
	 * Hold a pseudonym for ninety days after it is given up.
	 *
	 * @param string $pseudonym Pseudonym being retired.
	 */
	public static function retire_pseudonym( string $pseudonym ): void {
		if ( '' === $pseudonym ) {
			return;
		}

		$retired = (array) get_option( self::RETIRED_OPTION, array() );
		$now     = time();

		// Forget the ones whose hold has expired, so the option stays small.
		$retired = array_filter( $retired, static fn ( $until ): bool => (int) $until > $now );

		$retired[ $pseudonym ] = $now + ( 90 * DAY_IN_SECONDS );

		update_option( self::RETIRED_OPTION, $retired, false );
	}

	// --- wp-admin ---

	/**
	 * Show the profile fields on the user screen, for the desk.
	 *
	 * Typed loosely on purpose: WordPress documents a WP_User here, but the
	 * value arrives through a hook anybody can fire, and the guard below is
	 * what makes the rest of the method safe to read.
	 *
	 * @param mixed $user Account being edited.
	 */
	public function render_admin_fields( $user ): void {
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$can_verify = current_user_can( 'edit_users' );
		?>
		<h2><?php esc_html_e( 'Profilo di comunità', 'lupin3rd' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="l3_pseudonym"><?php esc_html_e( 'Pseudonimo', 'lupin3rd' ); ?></label></th>
				<td>
					<input type="text" class="regular-text" id="l3_pseudonym" name="l3_pseudonym"
						value="<?php echo esc_attr( self::pseudonym( $user ) ); ?>">
					<p class="description"><?php esc_html_e( 'Compare sotto ogni intervento. Il nome di accesso resta quello della registrazione.', 'lupin3rd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="l3_bio"><?php esc_html_e( 'Due righe', 'lupin3rd' ); ?></label></th>
				<td><textarea class="large-text" rows="3" id="l3_bio" name="l3_bio"><?php echo esc_textarea( self::bio( $user ) ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="l3_pgp_fingerprint"><?php esc_html_e( 'Impronta PGP', 'lupin3rd' ); ?></label></th>
				<td>
					<input type="text" class="regular-text code" id="l3_pgp_fingerprint" name="l3_pgp_fingerprint"
						value="<?php echo esc_attr( self::fingerprint( $user ) ); ?>">
					<?php if ( $can_verify ) : ?>
						<p>
							<label>
								<input type="checkbox" name="l3_pgp_verified" value="1" <?php checked( self::key_is_verified( $user ) ); ?>>
								<?php esc_html_e( 'Controfirmata dalla redazione', 'lupin3rd' ); ?>
							</label>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="l3_languages"><?php esc_html_e( 'Lingue', 'lupin3rd' ); ?></label></th>
				<td><input type="text" class="regular-text" id="l3_languages" name="l3_languages" value="<?php echo esc_attr( implode( ', ', self::languages( $user ) ) ); ?>"></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Data di iscrizione', 'lupin3rd' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="l3_show_join_date" value="1" <?php checked( (bool) get_user_meta( $user->ID, self::SHOW_JOIN_DATE, true ) ); ?>>
						<?php esc_html_e( 'Mostrala sul profilo pubblico', 'lupin3rd' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Interventi pubblicati', 'lupin3rd' ); ?></th>
				<td>
					<?php
					printf(
						/* translators: 1: number of approved contributions, 2: the threshold after which comments appear at once. */
						esc_html__( '%1$d su %2$d prima di uscire dalla coda di moderazione.', 'lupin3rd' ),
						(int) Community::contributions( $user->ID ),
						(int) Community::MODERATION_THRESHOLD
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the profile fields from the user screen.
	 *
	 * @param int $user_id Account being saved.
	 */
	public function save_admin_fields( $user_id ): void {
		$user_id = (int) $user_id;

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		check_admin_referer( 'update-user_' . $user_id );

		$pseudonym = $this->sanitize_pseudonym( sanitize_text_field( wp_unslash( $_POST['l3_pseudonym'] ?? '' ) ) );
		$current   = self::pseudonym( $user_id );

		if ( '' !== $pseudonym && $pseudonym !== $current && self::pseudonym_is_free( $pseudonym, $user_id ) ) {
			self::retire_pseudonym( $current );
			update_user_meta( $user_id, self::PSEUDONYM, $pseudonym );
		}

		update_user_meta( $user_id, self::BIO, $this->sanitize_bio( sanitize_textarea_field( wp_unslash( $_POST['l3_bio'] ?? '' ) ) ) );
		update_user_meta( $user_id, self::LANGUAGES, $this->sanitize_languages( sanitize_text_field( wp_unslash( $_POST['l3_languages'] ?? '' ) ) ) );
		update_user_meta( $user_id, self::SHOW_JOIN_DATE, isset( $_POST['l3_show_join_date'] ) ? '1' : '' );

		$fingerprint = $this->sanitize_fingerprint( sanitize_text_field( wp_unslash( $_POST['l3_pgp_fingerprint'] ?? '' ) ) );

		if ( self::fingerprint( $user_id ) !== $fingerprint ) {
			// A changed key is an unverified key until the desk signs it again.
			update_user_meta( $user_id, self::PGP_VERIFIED, '' );
		}

		update_user_meta( $user_id, self::PGP, $fingerprint );

		if ( current_user_can( 'edit_users' ) ) {
			update_user_meta( $user_id, self::PGP_VERIFIED, isset( $_POST['l3_pgp_verified'] ) && '' !== $fingerprint ? '1' : '' );
		}
	}

	/**
	 * Resolve whatever a caller passed into a user object.
	 *
	 * @param int|\WP_User $user Account.
	 */
	private static function user( $user ): ?\WP_User {
		if ( $user instanceof \WP_User ) {
			return $user;
		}

		$resolved = get_userdata( (int) $user );

		return $resolved instanceof \WP_User ? $resolved : null;
	}
}
