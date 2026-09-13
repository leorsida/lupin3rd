<?php
/**
 * One approved contribution, with the article it sits under.
 *
 * @package Lupin3rd
 *
 * @var array{comment?: \WP_Comment, byline?: bool} $args
 */

use Lupin3rd\Community;
use Lupin3rd\I18n;
use Lupin3rd\Marks;
use Lupin3rd\Profile_Fields;

defined( 'ABSPATH' ) || exit;

$l3_comment = $args['comment'] ?? null;

if ( ! $l3_comment instanceof WP_Comment ) {
	return;
}

$l3_byline = ! empty( $args['byline'] );
$l3_user   = (int) $l3_comment->user_id > 0 ? get_userdata( (int) $l3_comment->user_id ) : null;
$l3_post   = get_post( (int) $l3_comment->comment_post_ID );
?>
<article class="activity">
	<?php
	if ( $l3_user instanceof WP_User ) {
		Marks::render_avatar(
			Profile_Fields::avatar_seed( $l3_user ),
			Profile_Fields::initials( $l3_user ),
			'avatar-sm'
		);
	}
	?>

	<div>
		<?php if ( $l3_byline && $l3_user instanceof WP_User ) : ?>
			<div class="activity-head">
				<a class="comment-name" href="<?php echo esc_url( Community::profile_url( $l3_user ) ); ?>">
					<?php echo esc_html( Profile_Fields::pseudonym( $l3_user ) ); ?>
				</a>
				<span class="badge"><?php echo esc_html( Community::role_label( $l3_user ) ); ?></span>
				<span class="meta"><?php echo esc_html( I18n::instance()->relative_date( (string) $l3_comment->comment_date_gmt ) ); ?></span>
			</div>
		<?php else : ?>
			<span class="meta"><?php echo esc_html( I18n::instance()->relative_date( (string) $l3_comment->comment_date_gmt ) ); ?></span>
		<?php endif; ?>

		<p class="activity-quote">&laquo;<?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) $l3_comment->comment_content ), 42 ) ); ?>&raquo;</p>

		<?php if ( $l3_post instanceof WP_Post ) : ?>
			<p class="activity-on">
				<?php
				printf(
					/* translators: %s: link to the article the contribution sits under. */
					esc_html__( 'su %s', 'lupin3rd' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( (string) get_comment_link( $l3_comment ) ),
						esc_html( get_the_title( $l3_post ) )
					)
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</article>
