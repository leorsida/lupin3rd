<?php
/**
 * Roles, moderation gate and the shape of the community.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * An account here does one thing: it signs what you write under an article and
 * answers for it. There is no wall, no score, no network of friendships, and
 * every function that does not serve that signature is out of scope.
 *
 * Two roles are registered. The prototype shows a third badge, «redazione»,
 * which is the core editor role under a public label: duplicating editor would
 * mean maintaining a capability map WordPress already maintains, and every
 * plugin asking `edit_others_posts` would stop recognising the desk. See D-018.
 */
final class Community {

	use Singleton;

	/**
	 * Role of somebody who reads, comments and keeps private bookmarks.
	 */
	public const ROLE_MEMBER = 'l3_member';

	/**
	 * Role of somebody who also proposes drafts, corrections and translations.
	 */
	public const ROLE_CONTRIBUTOR = 'l3_contributor';

	/**
	 * Public base of a member profile: /membri/<pseudonym>/.
	 */
	public const AUTHOR_BASE = 'membri';

	/**
	 * Approved contributions after which comments stop being held.
	 */
	public const MODERATION_THRESHOLD = 3;

	/**
	 * Option holding the version of the registered roles.
	 */
	private const ROLES_OPTION = 'l3_roles_version';

	/**
	 * Bumped whenever the capability lists below change.
	 */
	private const ROLES_VERSION = 1;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'install_roles' ) );
		add_action( 'init', array( $this, 'set_author_base' ) );

		/*
		 * The first three contributions are read before they appear. The
		 * counter this reads is maintained below, on the status transition,
		 * because counting on every comment insert would mean a COUNT(*) on
		 * the busiest write path the site has.
		 */
		add_filter( 'pre_comment_approved', array( $this, 'hold_first_contributions' ), 10, 2 );
		add_action( 'transition_comment_status', array( $this, 'recount_contributions' ), 10, 3 );
		add_action( 'transition_comment_status', array( $this, 'record_approval' ), 10, 3 );

		// The member list filters on the server, like every other filter here.
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );

		// Members have no business in wp-admin, and no reason to see its bar.
		add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar' ) );
		add_action( 'admin_init', array( $this, 'keep_members_out_of_wp_admin' ) );
	}

	/**
	 * Register the two community roles, once per capability change.
	 */
	public function install_roles(): void {
		if ( (int) get_option( self::ROLES_OPTION ) === self::ROLES_VERSION ) {
			return;
		}

		remove_role( self::ROLE_MEMBER );
		remove_role( self::ROLE_CONTRIBUTOR );

		add_role(
			self::ROLE_MEMBER,
			__( 'Membro', 'lupin3rd' ),
			array( 'read' => true )
		);

		add_role(
			self::ROLE_CONTRIBUTOR,
			__( 'Collaboratore', 'lupin3rd' ),
			array(

				/*
				 * A contributor writes drafts and reads the ones under review.
				 * `edit_others_posts` is deliberately absent: reading a draft
				 * and editing somebody else's are not the same permission, and
				 * WordPress conflates them only because nothing else asked.
				 */
				'read'              => true,
				'edit_posts'        => true,
				'delete_posts'      => true,
				'l3_read_drafts'    => true,
				'l3_propose_change' => true,
			)
		);

		update_option( self::ROLES_OPTION, self::ROLES_VERSION, true );

		// The author base below is a rewrite rule, and roles ship with it.
		flush_rewrite_rules( false );
	}

	/**
	 * Serve member profiles from /membri/, not /author/.
	 *
	 * The base is a property of the rewrite object rather than an option, so
	 * the theme states it in code: nobody can change it by accident, and it
	 * cannot drift away from the URLs the colophon promises.
	 */
	public function set_author_base(): void {
		global $wp_rewrite;

		if ( $wp_rewrite instanceof \WP_Rewrite ) {
			$wp_rewrite->author_base = self::AUTHOR_BASE;
		}
	}

	/**
	 * Hold a comment until the account has three approved contributions.
	 *
	 * @param int|string|\WP_Error $approved    Current decision.
	 * @param array<string, mixed> $commentdata Comment being filed.
	 * @return int|string|\WP_Error
	 */
	public function hold_first_contributions( $approved, $commentdata ) {
		if ( is_wp_error( $approved ) || 'spam' === $approved || 'trash' === $approved ) {
			return $approved;
		}

		$user_id = (int) ( $commentdata['user_id'] ?? 0 );

		if ( 0 === $user_id ) {
			// An anonymous comment is held by WordPress's own settings.
			return $approved;
		}

		if ( user_can( $user_id, 'moderate_comments' ) ) {
			return $approved;
		}

		return self::contributions( $user_id ) >= self::MODERATION_THRESHOLD ? $approved : 0;
	}

	/**
	 * Keep the approved-contribution counter honest after a moderation decision.
	 *
	 * @param string      $new_status New comment status.
	 * @param string      $old_status Previous comment status.
	 * @param \WP_Comment $comment    Comment that moved.
	 */
	public function recount_contributions( $new_status, $old_status, $comment ): void {
		$user_id = (int) $comment->user_id;

		if ( 0 === $user_id || $new_status === $old_status ) {
			return;
		}

		$count = get_comments(
			array(
				'user_id' => $user_id,
				'status'  => 'approve',
				'count'   => true,
			)
		);

		update_user_meta( $user_id, Profile_Fields::CONTRIBUTIONS, (int) $count );
	}

	/**
	 * Number of contributions this account has had published.
	 *
	 * @param int $user_id Account.
	 */
	public static function contributions( int $user_id ): int {
		return (int) get_user_meta( $user_id, Profile_Fields::CONTRIBUTIONS, true );
	}

	/**
	 * Whether this account still has its contributions read before they appear.
	 *
	 * @param int $user_id Account.
	 */
	public static function is_queued( int $user_id ): bool {
		if ( user_can( $user_id, 'moderate_comments' ) ) {
			return false;
		}

		return self::contributions( $user_id ) < self::MODERATION_THRESHOLD;
	}

	/**
	 * The label a reader sees next to a pseudonym.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function role_label( $user ): string {
		$user = $user instanceof \WP_User ? $user : get_userdata( (int) $user );

		if ( ! $user instanceof \WP_User ) {
			return '';
		}

		if ( in_array( self::ROLE_CONTRIBUTOR, $user->roles, true ) ) {
			return __( 'collaboratore', 'lupin3rd' );
		}

		if ( $user->has_cap( 'edit_others_posts' ) ) {
			return __( 'redazione', 'lupin3rd' );
		}

		return __( 'membro', 'lupin3rd' );
	}

	/**
	 * The machine-readable role, for the filter bar on /comunita/.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function role_key( $user ): string {
		$user = $user instanceof \WP_User ? $user : get_userdata( (int) $user );

		if ( ! $user instanceof \WP_User ) {
			return '';
		}

		if ( in_array( self::ROLE_CONTRIBUTOR, $user->roles, true ) ) {
			return 'contributor';
		}

		return $user->has_cap( 'edit_others_posts' ) ? 'desk' : 'member';
	}

	/**
	 * Public URL of a member profile.
	 *
	 * @param int|\WP_User $user Account.
	 */
	public static function profile_url( $user ): string {
		$user = $user instanceof \WP_User ? $user : get_userdata( (int) $user );

		return $user instanceof \WP_User ? get_author_posts_url( $user->ID, $user->user_nicename ) : '';
	}

	/**
	 * Hide the admin bar from everybody who cannot edit anything.
	 *
	 * @param bool $show Whether WordPress means to show it.
	 */
	public function hide_admin_bar( $show ): bool {
		return current_user_can( 'edit_posts' ) ? (bool) $show : false;
	}

	/**
	 * Send a member who lands on wp-admin to their own account area.
	 */
	public function keep_members_out_of_wp_admin(): void {
		if ( wp_doing_ajax() || current_user_can( 'edit_posts' ) ) {
			return;
		}

		/*
		 * admin-post.php loads wp-admin/admin.php, so it fires admin_init even
		 * though it is not a screen. The public forms — the newsletter, the
		 * comment reports — post to it, and a redirect here would swallow them
		 * silently for every signed-in member.
		 */
		$script = basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ?? '' ) ) );

		if ( in_array( $script, array( 'admin-post.php', 'admin-ajax.php' ), true ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/account/' ) );
		exit;
	}

	/**
	 * Comment meta the desk sets when a correction actually reaches the page.
	 */
	public const CORRECTION_META = 'l3_correction_accepted';

	/**
	 * The four numbers at the top of a public profile.
	 *
	 * Counted from approved comments and published posts, and cached for ten
	 * minutes: a profile is a page, not a dashboard, and nobody is watching
	 * these numbers move.
	 *
	 * @param int $user_id Account.
	 * @return array<string, int>
	 */
	public static function profile_stats( int $user_id ): array {
		$key    = 'l3_profile_stats_' . $user_id;
		$cached = get_transient( $key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$comments = get_comments(
			array(
				'user_id' => $user_id,
				'status'  => 'approve',
			)
		);

		$posts       = array();
		$corrections = 0;

		foreach ( $comments as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$posts[ (int) $comment->comment_post_ID ] = true;

			if ( get_comment_meta( (int) $comment->comment_ID, self::CORRECTION_META, true ) ) {
				++$corrections;
			}
		}

		$authored = count_user_posts( $user_id, Content_Types::editorial_types(), true );

		$stats = array(
			'contributions' => count( $comments ),
			'threads'       => count( $posts ),
			'corrections'   => $corrections,
			'published'     => (int) $authored,
		);

		set_transient( $key, $stats, 10 * MINUTE_IN_SECONDS );

		return $stats;
	}

	/**
	 * The most recent approved contributions of one account.
	 *
	 * @param int $user_id Account.
	 * @param int $number  How many.
	 * @return array<int, \WP_Comment>
	 */
	public static function contributions_by( int $user_id, int $number = 4 ): array {
		$comments = get_comments(
			array(
				'user_id' => $user_id,
				'status'  => 'approve',
				'number'  => $number,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);

		return array_values( array_filter( $comments, static fn ( $comment ): bool => $comment instanceof \WP_Comment ) );
	}

	/**
	 * Comment meta holding when the desk let a contribution through.
	 *
	 * About the desk's own speed, not about the reader: it is the difference
	 * between two timestamps the site already has, and it is what makes the
	 * «attesa media in moderazione» on /comunita/ a measurement instead of a
	 * claim.
	 */
	public const APPROVED_AT_META = 'l3_approved_at';

	/**
	 * Query variable the member list filters on.
	 */
	public const ROLE_VAR = 'l3_role';

	/**
	 * Let WordPress read the role filter.
	 *
	 * @param array<int, string> $vars Public query variables.
	 * @return array<int, string>
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = self::ROLE_VAR;

		return $vars;
	}

	/**
	 * Write down when a contribution was let through.
	 *
	 * @param string      $new_status New comment status.
	 * @param string      $old_status Previous comment status.
	 * @param \WP_Comment $comment    Comment that moved.
	 */
	public function record_approval( $new_status, $old_status, $comment ): void {
		if ( 'approved' !== $new_status || 'approved' === $old_status ) {
			return;
		}

		if ( ! $comment instanceof \WP_Comment ) {
			return;
		}

		add_comment_meta( (int) $comment->comment_ID, self::APPROVED_AT_META, time(), true );
	}

	/**
	 * The four numbers at the top of /comunita/.
	 *
	 * «Account attivi» counts accounts with at least one published
	 * contribution, not accounts that signed in this year: the site keeps no
	 * record of when anybody signed in, and counting dormant registrations to
	 * make the number bigger is exactly what the prototype says it does not do.
	 * See D-025.
	 *
	 * @return array<string, int>
	 */
	public static function stats(): array {
		$cached = get_transient( 'l3_community_stats' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$active = new \WP_User_Query(
			array(
				'meta_key'     => Profile_Fields::CONTRIBUTIONS, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'   => 0,                             // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_compare' => '>',
				'meta_type'    => 'NUMERIC',
				'fields'       => 'ID',
				'number'       => -1,
			)
		);

		$recent = get_comments(
			array(
				'status'     => 'approve',
				'date_query' => array( array( 'after' => '30 days ago' ) ),
				'count'      => true,
			)
		);

		$corrections = get_comments(
			array(
				'status'     => 'approve',
				'meta_key'   => self::CORRECTION_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'date_query' => array( array( 'year' => (int) gmdate( 'Y' ) ) ),
				'count'      => true,
			)
		);

		$stats = array(
			'members'     => count( $active->get_results() ),
			'recent'      => (int) $recent,
			'corrections' => (int) $corrections,
			'wait'        => self::median_wait(),
		);

		set_transient( 'l3_community_stats', $stats, HOUR_IN_SECONDS );

		return $stats;
	}

	/**
	 * Median hours a contribution waited before appearing, over the last fifty.
	 *
	 * The median and not the mean: one comment held over a weekend would drag
	 * an average into a number that describes nothing that happened.
	 */
	private static function median_wait(): int {
		$comments = get_comments(
			array(
				'status'     => 'approve',
				'number'     => 50,
				'orderby'    => 'comment_date_gmt',
				'order'      => 'DESC',
				'meta_key'   => self::APPROVED_AT_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);

		$waits = array();

		foreach ( $comments as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$filed    = (int) strtotime( $comment->comment_date_gmt . ' UTC' );
			$approved = (int) get_comment_meta( (int) $comment->comment_ID, self::APPROVED_AT_META, true );

			if ( $filed > 0 && $approved > $filed ) {
				$waits[] = $approved - $filed;
			}
		}

		if ( ! $waits ) {
			return 0;
		}

		sort( $waits );

		$middle = (int) floor( count( $waits ) / 2 );

		return (int) round( $waits[ $middle ] / HOUR_IN_SECONDS );
	}

	/**
	 * Accounts that registered most recently.
	 *
	 * @param int    $number How many.
	 * @param string $role   Role key to filter on: member, contributor, desk.
	 * @return array<int, \WP_User>
	 */
	public static function recent_members( int $number = 8, string $role = '' ): array {
		$query = new \WP_User_Query(
			array(
				'orderby' => 'registered',
				'order'   => 'DESC',
				/*
				 * Asked for more than are needed because the role filter runs
				 * in PHP: «desk» is a capability, not a role slug, so it cannot
				 * be a WP_User_Query argument without duplicating core's
				 * capability map here.
				 */
				'number'  => '' === $role ? $number : $number * 6,
			)
		);

		$members = array();

		foreach ( $query->get_results() as $user ) {
			if ( ! $user instanceof \WP_User ) {
				continue;
			}

			if ( '' !== $role && self::role_key( $user ) !== $role ) {
				continue;
			}

			$members[] = $user;

			if ( count( $members ) >= $number ) {
				break;
			}
		}

		return $members;
	}

	/**
	 * The most recent approved contributions, whoever wrote them.
	 *
	 * @param int $number How many.
	 * @return array<int, \WP_Comment>
	 */
	public static function recent_contributions( int $number = 6 ): array {
		$comments = get_comments(
			array(
				'status'  => 'approve',
				'number'  => $number,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);

		return array_values(
			array_filter(
				$comments,
				static fn ( $comment ): bool => $comment instanceof \WP_Comment && (int) $comment->user_id > 0
			)
		);
	}

	/**
	 * Accounts with the most published contributions.
	 *
	 * @param int $number How many rows.
	 * @return array<int, \WP_User>
	 */
	public static function most_active( int $number = 5 ): array {
		$query = new \WP_User_Query(
			array(
				'meta_key'     => Profile_Fields::CONTRIBUTIONS, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'   => 0,                             // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_compare' => '>',
				'meta_type'    => 'NUMERIC',
				'orderby'      => 'meta_value_num',
				'order'        => 'DESC',
				'number'       => $number,
			)
		);

		return array_values(
			array_filter( $query->get_results(), static fn ( $user ): bool => $user instanceof \WP_User )
		);
	}

	/**
	 * The pieces being discussed most.
	 *
	 * @param int $number How many.
	 * @return array<int, \WP_Post>
	 */
	public static function liveliest_posts( int $number = 4 ): array {
		$posts = get_posts(
			array(
				'post_type'      => Content_Types::editorial_types(),
				'posts_per_page' => $number,
				'orderby'        => 'comment_count',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		return array_values(
			array_filter(
				$posts,
				static fn ( $post ): bool => $post instanceof \WP_Post && (int) $post->comment_count > 0
			)
		);
	}
}
