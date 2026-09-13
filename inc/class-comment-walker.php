<?php
/**
 * Comment markup.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the discussion in the prototype's markup.
 *
 * Not a singleton: wp_list_comments() instantiates a walker per call, which is
 * how WordPress wants it.
 */
final class Comment_Walker extends \Walker_Comment {

	/**
	 * Open a comment.
	 *
	 * `.comment` is a two-column grid: the avatar, then everything else. It
	 * therefore takes exactly two children. Emitting the head, the body and the
	 * tools as three siblings puts the head in the 44px avatar column, which is
	 * what broke the layout the first time.
	 *
	 * @param \WP_Comment          $comment Comment being rendered.
	 * @param int                  $depth   Nesting depth.
	 * @param array<string, mixed> $args    wp_list_comments arguments.
	 */
	protected function html5_comment( $comment, $depth, $args ) {
		$classes = 'comment' . ( $depth > 1 ? ' comment-nested' : '' );
		$removed = '' === trim( (string) $comment->comment_author );
		$name    = $removed ? __( 'account rimosso', 'lupin3rd' ) : get_comment_author( $comment );
		$url     = $removed ? '' : (string) get_comment_author_url( $comment );
		?>
		<article id="comment-<?php comment_ID(); ?>" <?php comment_class( $classes, $comment ); ?>>
			<?php
			// Full size: the avatar column is 44px and the prototype fills it.
			Marks::render_avatar( $name, mb_strtoupper( mb_substr( $name, 0, 2 ) ) ); ?>

			<div>
				<div class="comment-head">
					<?php if ( $url ) : ?>
						<a class="comment-name" href="<?php echo esc_url( $url ); ?>" rel="nofollow ugc"><?php echo esc_html( $name ); ?></a>
					<?php else : ?>
						<span class="comment-name"><?php echo esc_html( $name ); ?></span>
					<?php endif; ?>

					<?php if ( $comment->user_id && user_can( (int) $comment->user_id, 'edit_posts' ) ) : ?>
						<span class="badge badge-strong"><?php esc_html_e( 'redazione', 'lupin3rd' ); ?></span>
					<?php endif; ?>

					<time class="meta" datetime="<?php echo esc_attr( (string) get_comment_time( 'c' ) ); ?>">
						<?php echo esc_html( I18n::instance()->date( (int) get_comment_time( 'U', true ) ) ); ?>
					</time>
				</div>

				<?php if ( '0' === $comment->comment_approved ) : ?>
					<p class="comment-reply-to"><?php esc_html_e( 'In coda di moderazione. La leggiamo a mano.', 'lupin3rd' ); ?></p>
				<?php endif; ?>

				<div class="comment-body"><?php comment_text( $comment ); ?></div>

				<div class="comment-tools">
					<?php
					comment_reply_link(
						array_merge(
							$args,
							array(
								'depth'      => $depth,
								'max_depth'  => (int) ( $args['max_depth'] ?? 3 ),
								'reply_text' => __( 'Rispondi', 'lupin3rd' ),
								'before'     => '<span class="comment-tool">',
								'after'      => '</span>',
							)
						),
						$comment
					);
					?>

					<?php if ( Comment_Reports::just_reported( (int) $comment->comment_ID ) ) : ?>
						<span class="comment-tool" aria-live="polite"><?php esc_html_e( 'segnalato alla moderazione', 'lupin3rd' ); ?></span>
					<?php else : ?>
						<?php
						/*
						 * A form, not a link: a link that reports gets followed by every
						 * crawler that meets it. It submits without JavaScript, and the
						 * form itself has no box, so the row of tools stays one row.
						 */
						?>
						<form class="comment-report" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="<?php echo esc_attr( Comment_Reports::ACTION ); ?>">
							<input type="hidden" name="comment" value="<?php echo esc_attr( (string) $comment->comment_ID ); ?>">
							<?php wp_nonce_field( Comment_Reports::ACTION . '_' . $comment->comment_ID, '_wpnonce', false ); ?>
							<button class="comment-tool" type="submit"><?php esc_html_e( 'Segnala', 'lupin3rd' ); ?></button>
						</form>
					<?php endif; ?>
				</div>
			</div>
		<?php
	}

	/**
	 * Close the comment.
	 *
	 * Only the article: html5_comment() already closed the wrapper it opened.
	 * Closing it twice emits one stray </div>, which the parser resolves by
	 * closing whatever is open above — the comment list, the container, the
	 * article — and everything after the comments ends up a direct child of
	 * <main>, full width.
	 *
	 * @param string               $output      Accumulated markup, by reference.
	 * @param \WP_Comment          $data_object Comment being closed.
	 * @param int                  $depth       Nesting depth.
	 * @param array<string, mixed> $args        wp_list_comments arguments.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = array() ) {
		$output .= "</article>\n";
	}
}
