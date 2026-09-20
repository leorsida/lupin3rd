<?php
/**
 * Closing an account, and everything that has to stop showing it.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * A closed account is not a deleted one, and the difference is deliberate.
 *
 * Nothing is destroyed and nothing is overwritten: the row keeps its address,
 * its biography and its fingerprint, and the desk keeps the record of who
 * wrote what. What changes is that the account can no longer sign in and no
 * longer appears anywhere a reader can look — its contributions stay in the
 * discussion, signed «account rimosso».
 *
 * Two things follow from that, and both are load-bearing.
 *
 * It is reversible. `reopen()` puts the previous role back and the account
 * returns exactly as it was, which is why `close()` records the role it found
 * rather than assuming one. Nobody has to be told that a mis-click is final.
 *
 * And hiding is not half of the job, it is the job. When the data was going to
 * be overwritten, a place that forgot to check showed a blank; now it shows
 * the real pseudonym. Every reader-facing surface that names an account is
 * listed in `docs/PIANO-CANCELLAZIONE-ACCOUNT.md` §5, and the e2e tests exist
 * because that list is the kind of thing that rots.
 *
 * The state is a role, `Community::ROLE_CLOSED`, and not a meta flag. WordPress
 * already prints a role column in the user list and already builds the
 * per-role filter links above it, so the desk sees a closed account, and can
 * list them all, without this file drawing anything.
 */
final class Retirement {

	use Singleton;

	/**
	 * When the account was closed. Written for the desk, never read for logic.
	 */
	public const CLOSED_AT = 'l3_closed_at';

	/**
	 * The role the account had before. What makes reopening possible.
	 */
	public const PREVIOUS_ROLE = 'l3_closed_role';

	/**
	 * Reopening, from the user screen in wp-admin.
	 */
	public const ACTION_REOPEN = 'l3_reopen_account';

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		/*
		 * Priority 30, after core's wp_authenticate_username_password() at 20,
		 * so that what arrives here is the account and not the credentials.
		 * Authentication::refuse_readers_at_the_back_door() sits on the same
		 * hook at the same priority and is registered first; both return a
		 * WP_Error, and either one is enough.
		 */
		add_filter( 'authenticate', array( $this, 'refuse_a_closed_account' ), 30 );

		/*
		 * One rule instead of four call sites: no WP_User_Query on the front
		 * end returns a closed account. Anything added later gets it for free,
		 * which matters because the list of places that name an account is
		 * exactly the kind of list that goes stale.
		 *
		 * wp-admin is the exception, and deliberately so: the desk has to see
		 * them. That is also why the sitemap's user provider is covered here —
		 * it runs a WP_User_Query on the front end like everything else.
		 */
		add_action( 'pre_get_users', array( $this, 'skip_closed_accounts' ) );

		add_action( 'template_redirect', array( $this, 'gone_for_a_closed_profile' ) );

		add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'fill_column' ), 10, 3 );
		add_action( 'edit_user_profile', array( $this, 'show_notice' ) );
		add_action( 'admin_post_' . self::ACTION_REOPEN, array( $this, 'handle_reopen' ) );
	}

	// --- Reading ---

	/**
	 * Whether this account has been closed.
	 *
	 * Reads the role rather than the meta: the role is what actually takes the
	 * capabilities away, so anything else would be a second truth that could
	 * disagree with the first. A WP_User carries its roles already, which
	 * matters because a comment thread asks this once per comment.
	 *
	 * @param int|\WP_User|null $user Account.
	 */
	public static function is_closed( $user ): bool {
		if ( ! $user instanceof \WP_User ) {
			$user = get_userdata( (int) $user );
		}

		if ( ! $user instanceof \WP_User ) {
			return false;
		}

		return in_array( Community::ROLE_CLOSED, (array) $user->roles, true );
	}

	// --- Not here any more ---

	/**
	 * Keep closed accounts out of every front-end user query.
	 *
	 * A query that genuinely wants them — `Profile_Fields::pseudonym_is_free()`
	 * is the only one — says so with `l3_include_closed`. Without that opt-out
	 * a closed account's pseudonym would read as free and somebody else could
	 * take it, which is the one thing this whole mechanism must not allow.
	 *
	 * Typed `mixed` and then checked, not typed `\WP_User_Query` and trusted:
	 * what a hook hands a callback is whatever the last filter returned, and a
	 * docblock that promises otherwise turns the guard below into dead code in
	 * the analyser's eyes while leaving it very much alive at runtime.
	 *
	 * @param mixed $query Query about to run.
	 */
	public function skip_closed_accounts( $query ): void {
		if ( ! $query instanceof \WP_User_Query || is_admin() ) {
			return;
		}

		if ( ! empty( $query->query_vars['l3_include_closed'] ) ) {
			return;
		}

		$excluded = isset( $query->query_vars['role__not_in'] ) ? (array) $query->query_vars['role__not_in'] : array();

		$excluded[] = Community::ROLE_CLOSED;

		$query->query_vars['role__not_in'] = array_unique( $excluded );
	}

	/**
	 * A closed member profile is Gone, not missing.
	 *
	 * 404 says «no such page», and a search engine will keep trying it for
	 * months. 410 says it was here and is not any more, which is the true
	 * statement and the one that gets it dropped from the index.
	 *
	 * The status has to be sent before anything is printed, so this runs on
	 * `template_redirect` rather than inside author.php, which has already
	 * called get_header() by the time it knows whose profile it is.
	 */
	public function gone_for_a_closed_profile(): void {
		if ( ! is_author() || ! self::is_closed( get_queried_object() ) ) {
			return;
		}

		status_header( 410 );
		nocache_headers();

		$template = get_404_template();

		if ( '' !== $template ) {
			include $template;
		}

		exit;
	}

	// --- Writing ---

	/**
	 * Close an account.
	 *
	 * @param \WP_User $user Account to close.
	 */
	public static function close( \WP_User $user ): bool {
		if ( self::is_closed( $user ) ) {
			return true;
		}

		/*
		 * The desk does not leave this way. An editor's account owns published
		 * articles, and deciding what happens to them is a job for somebody
		 * who can reassign them — not for a form with a password field.
		 */
		if ( user_can( $user, 'edit_posts' ) ) {
			return false;
		}

		$previous = (string) ( $user->roles[0] ?? Community::ROLE_MEMBER );

		update_user_meta( $user->ID, self::PREVIOUS_ROLE, $previous );
		update_user_meta( $user->ID, self::CLOSED_AT, time() );

		$user->set_role( Community::ROLE_CLOSED );

		/*
		 * Every device, not just this one. Somebody who closes an account from
		 * a laptop should not stay signed in on a phone they no longer have.
		 */
		$tokens = \WP_Session_Tokens::get_instance( $user->ID );
		$tokens->destroy_all();

		return true;
	}

	/**
	 * Put a closed account back the way it was.
	 *
	 * @param \WP_User $user Account to reopen.
	 */
	public static function reopen( \WP_User $user ): bool {
		if ( ! self::is_closed( $user ) ) {
			return false;
		}

		$previous = (string) get_user_meta( $user->ID, self::PREVIOUS_ROLE, true );

		if ( '' === $previous || ! get_role( $previous ) ) {
			$previous = Community::ROLE_MEMBER;
		}

		$user->set_role( $previous );

		delete_user_meta( $user->ID, self::PREVIOUS_ROLE );
		delete_user_meta( $user->ID, self::CLOSED_AT );

		return true;
	}

	// --- The front door ---

	/**
	 * Refuse a closed account, in the same words as a wrong password.
	 *
	 * Saying «this account was closed» would answer a question the person
	 * typing has no right to ask: whether somebody was ever here.
	 *
	 * @param \WP_User|\WP_Error|null $user Result so far.
	 * @return \WP_User|\WP_Error|null
	 */
	public function refuse_a_closed_account( $user ) {
		if ( ! $user instanceof \WP_User || ! self::is_closed( $user ) ) {
			return $user;
		}

		return new \WP_Error(
			'l3_account_closed',
			__( 'Non riusciamo a farti entrare con questi dati.', 'lupin3rd' )
		);
	}

	// --- wp-admin ---

	/**
	 * A «Chiuso il» column beside the role.
	 *
	 * @param array<string, string> $columns Column headings.
	 * @return array<string, string>
	 */
	public function add_column( $columns ): array {
		$columns = (array) $columns;

		$columns['l3_closed'] = __( 'Chiuso il', 'lupin3rd' );

		return $columns;
	}

	/**
	 * Fill it.
	 *
	 * @param string $output  What another handler already produced.
	 * @param string $column  Column being filled.
	 * @param int    $user_id Account on this row.
	 */
	public function fill_column( $output, $column, $user_id ): string {
		if ( 'l3_closed' !== $column ) {
			return (string) $output;
		}

		$closed = (int) get_user_meta( (int) $user_id, self::CLOSED_AT, true );

		if ( $closed <= 0 ) {
			return '—';
		}

		return esc_html( wp_date( (string) get_option( 'date_format' ), $closed ) );
	}

	/**
	 * Say so at the top of the account's own screen, and offer the way back.
	 *
	 * @param mixed $user Account being edited, as the hook hands it over.
	 */
	public function show_notice( $user ): void {
		if ( ! $user instanceof \WP_User || ! self::is_closed( $user ) ) {
			return;
		}

		$closed = (int) get_user_meta( $user->ID, self::CLOSED_AT, true );

		?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'Account chiuso.', 'lupin3rd' ); ?></strong>
				<?php
				if ( $closed > 0 ) {
					printf(
						/* translators: %s: date the account was closed. */
						esc_html__( 'Chiuso il %s dal titolare.', 'lupin3rd' ),
						esc_html( wp_date( (string) get_option( 'date_format' ), $closed ) )
					);
				}
				?>
				<?php esc_html_e( 'Non può entrare e non compare da nessuna parte sul sito. Niente è stato cancellato: riaprendolo torna com\'era.', 'lupin3rd' ); ?>
			</p>
			<?php if ( current_user_can( 'edit_users' ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_REOPEN ); ?>">
					<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user->ID ); ?>">
					<?php wp_nonce_field( self::ACTION_REOPEN . '_' . $user->ID ); ?>
					<p>
						<button type="submit" class="button">
							<?php esc_html_e( 'Riapri l\'account', 'lupin3rd' ); ?>
						</button>
					</p>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle the reopening.
	 */
	public function handle_reopen(): void {
		$user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;

		if ( ! current_user_can( 'edit_users' ) || $user_id <= 0 ) {
			wp_die( esc_html__( 'Non hai i permessi per farlo.', 'lupin3rd' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION_REOPEN . '_' . $user_id );

		$user = get_userdata( $user_id );

		if ( $user instanceof \WP_User ) {
			self::reopen( $user );
		}

		wp_safe_redirect( admin_url( 'user-edit.php?user_id=' . $user_id ) );
		exit;
	}
}
