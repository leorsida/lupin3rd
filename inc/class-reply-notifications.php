<?php
/**
 * Telling the people in a discussion that it moved.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * When a contribution appears under a piece, everybody who already wrote under
 * that piece hears about it.
 *
 * This is the one thing in the community module that brings a reader back to a
 * **public** page. A private inbox would move the same sentences somewhere
 * nobody else can read them; this moves people towards the thread, where what
 * they write is still content.
 *
 * Which makes restraint the whole design. An email nobody wants is worse than
 * no email: it teaches the reader to ignore this address, and it teaches their
 * provider to file it as spam. So:
 *
 * - only once a contribution is **approved**, never when it is filed;
 * - never to the person who just wrote it;
 * - once per person per piece per hour, however many arrive in that hour;
 * - only to accounts, and only to those that left the setting on;
 * - and every message carries a link that turns it off without signing in.
 *
 * Sending happens on a scheduled event rather than inside the request that
 * posted the comment: thirty participants would otherwise be thirty `wp_mail()`
 * calls between the reader pressing «Invia» and the page coming back.
 */
final class Reply_Notifications {

	use Singleton;

	/**
	 * Scheduled event that does the sending.
	 */
	private const EVENT = 'l3_notify_participants';

	/**
	 * Query variable carrying a one-click opt-out.
	 */
	public const UNFOLLOW_VAR = 'l3_unfollow';

	/**
	 * At most one message per person per piece in this window.
	 */
	private const QUIET = HOUR_IN_SECONDS;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		/*
		 * Two doors, because a comment reaches «approved» two different ways.
		 * A member out of the queue is inserted already approved and only fires
		 * `comment_post`; one that was held fires `transition_comment_status`
		 * when the desk lets it through. Watching one of the two would have
		 * covered about half the discussion, silently.
		 */
		add_action( 'comment_post', array( $this, 'on_posted' ), 10, 2 );
		add_action( 'transition_comment_status', array( $this, 'on_approved' ), 10, 3 );

		add_action( self::EVENT, array( $this, 'send' ) );

		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_unfollow' ) );
	}

	/**
	 * A contribution that was published immediately.
	 *
	 * @param int        $comment_id Comment just filed.
	 * @param int|string $approved   Whether it went straight in.
	 */
	public function on_posted( $comment_id, $approved ): void {
		if ( 1 === (int) $approved ) {
			$this->schedule( (int) $comment_id );
		}
	}

	/**
	 * A contribution the desk has just let through.
	 *
	 * @param string $new_status New comment status.
	 * @param string $old_status Previous comment status.
	 * @param mixed  $comment    Comment that moved; WordPress passes a WP_Comment.
	 */
	public function on_approved( $new_status, $old_status, $comment ): void {
		if ( 'approved' !== $new_status || 'approved' === $old_status ) {
			return;
		}

		if ( $comment instanceof \WP_Comment ) {
			$this->schedule( (int) $comment->comment_ID );
		}
	}

	/**
	 * Hand the sending to cron, a moment from now.
	 *
	 * @param int $comment_id Comment to announce.
	 */
	private function schedule( int $comment_id ): void {
		if ( $comment_id <= 0 || wp_next_scheduled( self::EVENT, array( $comment_id ) ) ) {
			return;
		}

		wp_schedule_single_event( time() + 30, self::EVENT, array( $comment_id ) );
	}

	/**
	 * Tell the other participants.
	 *
	 * @param int $comment_id Comment to announce.
	 */
	public function send( $comment_id ): void {
		$comment = get_comment( (int) $comment_id );

		if ( ! $comment instanceof \WP_Comment || '1' !== (string) $comment->comment_approved ) {
			return;
		}

		$post = get_post( (int) $comment->comment_post_ID );

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		foreach ( $this->participants( $post->ID, (int) $comment->user_id ) as $user ) {
			$this->tell( $user, $comment, $post );
		}
	}

	/**
	 * The accounts that already wrote under this piece and want to know.
	 *
	 * @param int $post_id Piece being discussed.
	 * @param int $except  Account that just wrote, who does not need telling.
	 * @return array<int, \WP_User>
	 */
	private function participants( int $post_id, int $except ): array {
		$comments = get_comments(
			array(
				'post_id' => $post_id,
				'status'  => 'approve',
			)
		);

		$seen  = array();
		$users = array();

		foreach ( $comments as $comment ) {
			$user_id = (int) $comment->user_id;

			// Accounts only: an anonymous commenter left an address to be
			// written to about their own comment, not to be subscribed to a
			// thread they never asked to follow.
			if ( $user_id <= 0 || $user_id === $except || isset( $seen[ $user_id ] ) ) {
				continue;
			}

			$seen[ $user_id ] = true;
			$user             = get_userdata( $user_id );

			if ( $user instanceof \WP_User && Profile_Fields::wants_replies( $user ) && is_email( $user->user_email ) ) {
				$users[] = $user;
			}
		}

		return $users;
	}

	/**
	 * One message, if this person has not had one about this piece recently.
	 *
	 * @param \WP_User    $user    Who to tell.
	 * @param \WP_Comment $comment What was written.
	 * @param \WP_Post    $post    Where.
	 */
	private function tell( \WP_User $user, \WP_Comment $comment, \WP_Post $post ): void {
		$key = 'l3_told_' . $user->ID . '_' . $post->ID;

		if ( get_transient( $key ) ) {
			return;
		}

		set_transient( $key, 1, self::QUIET );

		$stop = self::unfollow_url( $user );

		$body = sprintf(
			/* translators: 1: pseudonym of whoever wrote, 2: title of the piece, 3: what they wrote, 4: link to the contribution, 5: link that turns these messages off. */
			__( "%1\$s ha scritto sotto «%2\$s», dove eri intervenuto anche tu.\n\n«%3\$s»\n\nLeggi e rispondi:\n%4\$s\n\n—\nRicevi questo messaggio perché hai commentato quell'articolo. Per non riceverne più:\n%5\$s", 'lupin3rd' ),
			Profile_Fields::pseudonym( (int) $comment->user_id ),
			(string) get_the_title( $post ),
			wp_trim_words( wp_strip_all_tags( (string) $comment->comment_content ), 40 ),
			(string) get_comment_link( $comment ),
			$stop
		);

		wp_mail(
			(string) $user->user_email,
			sprintf(
				/* translators: %s: title of the piece. */
				__( 'Nuovo intervento sotto «%s»', 'lupin3rd' ),
				(string) get_the_title( $post )
			),
			$body,
			array( 'List-Unsubscribe: <' . $stop . '>' )
		);
	}

	/**
	 * A link that turns these messages off without signing in.
	 *
	 * Signed rather than guessable: the account id alone would let anybody
	 * unsubscribe anybody by counting upwards. The signature is derived from
	 * the site's own salt, so it needs nothing stored.
	 *
	 * @param \WP_User $user Account.
	 */
	public static function unfollow_url( \WP_User $user ): string {
		return add_query_arg(
			self::UNFOLLOW_VAR,
			$user->ID . '.' . self::signature( $user->ID ),
			home_url( '/' )
		);
	}

	/**
	 * The signature for an account's opt-out link.
	 *
	 * @param int $user_id Account.
	 */
	private static function signature( int $user_id ): string {
		return substr( hash_hmac( 'sha256', 'unfollow|' . $user_id, wp_salt( 'auth' ) ), 0, 32 );
	}

	/**
	 * Let WordPress read the opt-out variable.
	 *
	 * @param array<int, string> $vars Public query variables.
	 * @return array<int, string>
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = self::UNFOLLOW_VAR;

		return $vars;
	}

	/**
	 * Turn the messages off for whoever followed the link.
	 */
	public function handle_unfollow(): void {
		$token = (string) get_query_var( self::UNFOLLOW_VAR );

		if ( '' === $token ) {
			return;
		}

		/*
		 * explode() always returns at least one element, so the first has no
		 * default; the second is missing whenever the link arrived without a
		 * signature, which is the case this method exists to refuse.
		 */
		$parts   = explode( '.', $token, 2 );
		$user_id = (int) $parts[0];
		$given   = (string) ( $parts[1] ?? '' );

		if ( $user_id > 0 && hash_equals( self::signature( $user_id ), $given ) ) {
			update_user_meta( $user_id, Profile_Fields::NOTIFY_REPLIES, '' );

			wp_safe_redirect( add_query_arg( Account::NOTICE_VAR, 'unfollowed', Authentication::account_url() ) );
			exit;
		}

		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
