<?php
/**
 * The Thursday dispatch: subscriptions, kept in house.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Collects newsletter subscriptions without a third party.
 *
 * The colophon promises no third party in the page and no tracking in the
 * email, which rules out every hosted list service: the addresses live in this
 * database and nowhere else. They are stored as a private post type rather
 * than a table of their own, so they are backed up, exported and deleted with
 * the rest of the site, and so uninstalling the theme leaves nothing orphaned
 * behind it.
 *
 * A subscription starts unconfirmed and stays that way until somebody follows
 * the link in the email, so an address cannot be signed up by a stranger. The
 * form works with JavaScript off: it is a real POST to admin-post.php that
 * redirects back to the page it came from.
 */
final class Newsletter {

	use Singleton;

	/**
	 * Post type holding one address.
	 */
	public const POST_TYPE = 'l3_subscriber';

	/**
	 * Action name for the public form.
	 */
	public const ACTION = 'l3_subscribe';

	/**
	 * Query argument carrying the outcome back to the page.
	 */
	public const NOTICE_VAR = 'l3_newsletter';

	/**
	 * Query argument carrying a confirmation token.
	 */
	public const CONFIRM_VAR = 'l3_confirm';

	/**
	 * Meta key holding the confirmation token.
	 */
	private const TOKEN_KEY = 'l3_confirm_token';

	/**
	 * Meta key holding the account this address belongs to, when it has one.
	 */
	private const USER_KEY = 'l3_subscriber_user';

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_form' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle_form' ) );
		add_action( 'template_redirect', array( $this, 'handle_confirmation' ) );

		/*
		 * Somebody can subscribe long before they open an account, or the other
		 * way round. Either event links the two.
		 */
		add_action( 'user_register', array( $this, 'link_account' ) );
	}

	/**
	 * Register the post type holding the list.
	 *
	 * Not public, not searchable, not in REST, and readable in the dashboard
	 * only by whoever can manage the site: it is a list of people's addresses.
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => __( 'Iscritti al dispaccio', 'lupin3rd' ),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'tools.php',
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => 'l3_subscriber',
				'map_meta_cap'        => true,

				/*
				 * Primitive capabilities only. With map_meta_cap on, WordPress
				 * derives the per-post ones — edit_post, read_post, delete_post
				 * — from these; naming a per-post capability here makes core
				 * check it without a post, which is a notice on every request
				 * and, printed before a redirect, a broken redirect.
				 */
				'capabilities'        => array(
					'create_posts'           => 'do_not_allow',
					'edit_posts'             => 'manage_options',
					'edit_others_posts'      => 'manage_options',
					'edit_published_posts'   => 'manage_options',
					'edit_private_posts'     => 'manage_options',
					'publish_posts'          => 'manage_options',
					'read_private_posts'     => 'manage_options',
					'delete_posts'           => 'manage_options',
					'delete_others_posts'    => 'manage_options',
					'delete_published_posts' => 'manage_options',
					'delete_private_posts'   => 'manage_options',
				),
			)
		);
	}

	/**
	 * Handle the subscription form.
	 */
	public function handle_form(): void {
		$referer = wp_get_referer();
		$referer = is_string( $referer ) && '' !== $referer ? $referer : home_url( '/' );
		$nonce   = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			$this->redirect( $referer, 'error' );
		}

		/*
		 * A field no reader ever sees. A form filled in faster than a human
		 * could, or with this field filled at all, is a robot: it gets the same
		 * answer as everybody else and nothing is written.
		 */
		$trap = isset( $_POST['l3_website'] ) ? trim( sanitize_text_field( wp_unslash( (string) $_POST['l3_website'] ) ) ) : '';

		if ( '' !== $trap ) {
			$this->redirect( $referer, 'pending' );
		}

		$email = isset( $_POST['l3_email'] ) ? sanitize_email( wp_unslash( (string) $_POST['l3_email'] ) ) : '';

		if ( ! is_email( $email ) ) {
			$this->redirect( $referer, 'invalid' );
		}

		$this->redirect( $referer, $this->subscribe( $email ) );
	}

	/**
	 * Record an address, or find the record it already has.
	 *
	 * @param string $email Address to subscribe.
	 * @return string One of pending, again, confirmed, error.
	 */
	public function subscribe( string $email ): string {
		$existing = self::find( $email );

		if ( $existing && 'publish' === $existing->post_status ) {
			return 'confirmed';
		}

		if ( $existing ) {
			$this->send_confirmation( $existing );

			return 'again';
		}

		$id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $email,
				'post_name'    => md5( strtolower( $email ) ),
				'post_status'  => 'draft',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return 'error';
		}

		$subscriber = get_post( $id );

		if ( ! $subscriber ) {
			return 'error';
		}

		update_post_meta( $id, self::TOKEN_KEY, wp_generate_password( 32, false ) );
		$this->match_account( $id, $email );
		$this->send_confirmation( $subscriber );

		return 'pending';
	}

	/**
	 * Turn a confirmation link into a confirmed subscription.
	 */
	public function handle_confirmation(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The token in the link is the credential; a nonce would tie a link sent by email to a browser session that does not exist yet.
		$token = isset( $_GET[ self::CONFIRM_VAR ] ) ? sanitize_text_field( wp_unslash( (string) $_GET[ self::CONFIRM_VAR ] ) ) : '';

		if ( '' === $token ) {
			return;
		}

		$matches = get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => array( 'draft', 'publish' ),
				'posts_per_page'   => 1,
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_key'         => self::TOKEN_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $token, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$outcome = 'error';

		if ( $matches ) {
			$subscriber = $matches[0];

			wp_update_post(
				array(
					'ID'          => $subscriber->ID,
					'post_status' => 'publish',
				)
			);

			delete_post_meta( $subscriber->ID, self::TOKEN_KEY );
			$this->match_account( $subscriber->ID, (string) $subscriber->post_title );

			$outcome = 'confirmed';
		}

		$this->redirect( home_url( '/' ), $outcome );
	}

	/**
	 * Link a newly registered account to an address already on the list.
	 *
	 * @param int $user_id Account just created.
	 */
	public function link_account( int $user_id ): void {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		$subscriber = self::find( (string) $user->user_email );

		if ( $subscriber ) {
			update_post_meta( $subscriber->ID, self::USER_KEY, $user_id );
		}
	}

	/**
	 * Whether an address is on the list and confirmed.
	 *
	 * @param string $email Address to look for.
	 */
	public static function is_subscribed( string $email ): bool {
		$subscriber = self::find( $email );

		return $subscriber && 'publish' === $subscriber->post_status;
	}

	/**
	 * Find the record for an address, whatever its state.
	 *
	 * The slug is the hash of the lowercased address, which makes the lookup an
	 * indexed one and keeps the list free of duplicates that differ only in
	 * capitalisation.
	 *
	 * @param string $email Address to look for.
	 */
	public static function find( string $email ): ?\WP_Post {
		$matches = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'draft', 'publish' ),
				'name'           => md5( strtolower( $email ) ),
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		return $matches ? $matches[0] : null;
	}

	/**
	 * Note which account an address belongs to, when it belongs to one.
	 *
	 * @param int    $subscriber_id Record to annotate.
	 * @param string $email         Address it holds.
	 */
	private function match_account( int $subscriber_id, string $email ): void {
		$user = get_user_by( 'email', $email );

		if ( $user ) {
			update_post_meta( $subscriber_id, self::USER_KEY, $user->ID );
		}
	}

	/**
	 * Send the confirmation link.
	 *
	 * Best effort: if the site cannot send mail the record stays unconfirmed
	 * and the desk can see it in the dashboard, which is better than telling
	 * somebody they are subscribed when they are not.
	 *
	 * @param \WP_Post $subscriber Record to confirm.
	 */
	private function send_confirmation( \WP_Post $subscriber ): void {
		$token = (string) get_post_meta( $subscriber->ID, self::TOKEN_KEY, true );

		if ( '' === $token ) {
			$token = wp_generate_password( 32, false );
			update_post_meta( $subscriber->ID, self::TOKEN_KEY, $token );
		}

		$link = add_query_arg( self::CONFIRM_VAR, $token, home_url( '/' ) );

		wp_mail(
			(string) $subscriber->post_title,
			__( 'Conferma l\'iscrizione al dispaccio', 'lupin3rd' ),
			sprintf(
				/* translators: 1: confirmation URL, 2: site name. */
				__( "Qualcuno ha chiesto di iscrivere questo indirizzo al dispaccio settimanale di %2\$s.\n\nSe sei stato tu, conferma da qui:\n%1\$s\n\nSe non sei stato tu, non fare niente: senza conferma l'indirizzo non riceve nulla e viene cancellato.", 'lupin3rd' ),
				$link,
				(string) get_bloginfo( 'name' )
			)
		);
	}

	/**
	 * Send the reader back where they came from, with the outcome.
	 *
	 * @param string $url     Where to go.
	 * @param string $outcome What happened.
	 */
	private function redirect( string $url, string $outcome ): void {
		wp_safe_redirect( add_query_arg( self::NOTICE_VAR, $outcome, remove_query_arg( self::NOTICE_VAR, $url ) ) . '#dispaccio' );
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
			'pending', 'again' => array(
				'tone' => 'ok',
				'text' => __( 'Fatto. Controlla la posta: l\'iscrizione vale solo dopo che hai seguito il link.', 'lupin3rd' ),
			),
			'confirmed' => array(
				'tone' => 'ok',
				'text' => __( 'Iscrizione confermata. Il primo dispaccio arriva giovedì.', 'lupin3rd' ),
			),
			'invalid' => array(
				'tone' => 'error',
				'text' => __( 'Quell\'indirizzo non sembra un indirizzo. Riprova.', 'lupin3rd' ),
			),
			'error' => array(
				'tone' => 'error',
				'text' => __( 'Non ha funzionato. Riprova fra poco.', 'lupin3rd' ),
			),
			default => null,
		};
	}
}
