<?php
/**
 * Reporting a comment to the desk.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a reader flag a comment, and tells the desk about it.
 *
 * A report is a signal, never a verdict: it raises a counter and sends a note,
 * and it never hides anything by itself. Every comment on this site was read by
 * a person before it appeared, so letting reports unpublish one would hand a
 * group of readers the power to remove what they dislike — brigading with extra
 * steps. A human decides, the same human who let it through.
 *
 * Nothing about the reporter is stored: no address, no identifier, no count per
 * person. That means the same reader can report twice and the counter will say
 * two. The desk reads the comment, not the number.
 */
final class Comment_Reports {

	use Singleton;

	/**
	 * Action name for the public form.
	 */
	public const ACTION = 'l3_report_comment';

	/**
	 * Query argument naming the comment just reported.
	 */
	public const NOTICE_VAR = 'l3_reported';

	/**
	 * Comment meta holding how many times it has been reported.
	 */
	public const COUNT_KEY = 'l3_report_count';

	/**
	 * Comment meta holding when the last report arrived.
	 */
	private const TIME_KEY = 'l3_report_last';

	/**
	 * Where the counter stops. A robot can keep pressing; the desk only needs
	 * to know that something is being reported, not how enthusiastically.
	 */
	private const CEILING = 25;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_form' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle_form' ) );

		add_filter( 'manage_edit-comments_columns', array( $this, 'add_column' ) );
		add_action( 'manage_comments_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Record a report and tell the desk.
	 */
	public function handle_form(): void {
		$comment_id = isset( $_POST['comment'] ) ? absint( wp_unslash( $_POST['comment'] ) ) : 0;
		$nonce      = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';
		$comment    = $comment_id ? get_comment( $comment_id ) : null;

		if ( ! $comment || ! wp_verify_nonce( $nonce, self::ACTION . '_' . $comment_id ) ) {
			$back = wp_get_referer();

			wp_safe_redirect( is_string( $back ) && '' !== $back ? $back : home_url( '/' ) );
			exit;
		}

		$count = (int) get_comment_meta( $comment_id, self::COUNT_KEY, true );

		if ( $count < self::CEILING ) {
			update_comment_meta( $comment_id, self::COUNT_KEY, $count + 1 );
			update_comment_meta( $comment_id, self::TIME_KEY, time() );

			if ( 0 === $count ) {
				$this->notify( $comment );
			}
		}

		$this->redirect_back( $comment_id );
	}

	/**
	 * Whether the reader has just reported this comment.
	 *
	 * @param int $comment_id Comment being rendered.
	 */
	public static function just_reported( int $comment_id ): bool {
		$reported = isset( $_GET[ self::NOTICE_VAR ] ) ? absint( wp_unslash( $_GET[ self::NOTICE_VAR ] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Deciding what a button says.

		return $reported === $comment_id;
	}

	/**
	 * Add the reports column to the comments screen.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function add_column( array $columns ): array {
		$columns['l3_reports'] = __( 'Segnalazioni', 'lupin3rd' );

		return $columns;
	}

	/**
	 * Print the reports column.
	 *
	 * @param string $column     Column being rendered.
	 * @param int    $comment_id Comment in the row.
	 */
	public function render_column( $column, $comment_id ): void {
		if ( 'l3_reports' !== $column ) {
			return;
		}

		$count = (int) get_comment_meta( (int) $comment_id, self::COUNT_KEY, true );

		if ( ! $count ) {
			echo '—';

			return;
		}

		$last = (int) get_comment_meta( (int) $comment_id, self::TIME_KEY, true );

		printf(
			'<strong>%s</strong>%s',
			esc_html( (string) $count ),
			$last
				? '<br><span class="description">' . esc_html( I18n::instance()->date( $last ) ) . '</span>'
				: ''
		);
	}

	/**
	 * Tell the desk, once, that a comment has been reported.
	 *
	 * Only the first report sends mail: the point is to bring a human to the
	 * comment, and they do not need telling twice.
	 *
	 * @param \WP_Comment $comment Comment reported.
	 */
	private function notify( \WP_Comment $comment ): void {
		$link = admin_url( 'comment.php?action=editcomment&c=' . $comment->comment_ID );

		Mail::send(
			'comment_reported',
			(string) get_option( 'admin_email' ),
			__( 'Un commento è stato segnalato', 'lupin3rd' ),
			sprintf(
				/* translators: 1: comment author, 2: piece title, 3: admin URL. */
				__( "Qualcuno ha segnalato un commento di %1\$s sotto «%2\$s».\n\nLeggilo qui:\n%3\$s\n\nLa segnalazione non ha nascosto niente: il commento è ancora in pagina.", 'lupin3rd' ),
				(string) $comment->comment_author,
				(string) get_the_title( (int) $comment->comment_post_ID ),
				$link
			),
			array(),
			array(
				'comment' => $comment,
				'link'    => $link,
			)
		);
	}

	/**
	 * Send the reader back to the comment they reported.
	 *
	 * @param int $comment_id Comment reported.
	 */
	private function redirect_back( int $comment_id ): void {
		/*
		 * The comment link carries a fragment, and a query argument appended
		 * after one lands inside the fragment instead of the query string.
		 */
		$parts = explode( '#', (string) get_comment_link( $comment_id ), 2 );
		$url   = add_query_arg( self::NOTICE_VAR, $comment_id, $parts[0] );

		if ( isset( $parts[1] ) ) {
			$url .= '#' . $parts[1];
		}

		wp_safe_redirect( $url );
		exit;
	}
}
