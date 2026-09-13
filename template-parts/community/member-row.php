<?php
/**
 * One account in the member list.
 *
 * @package Lupin3rd
 *
 * @var array{user?: \WP_User} $args
 */

use Lupin3rd\Community;
use Lupin3rd\I18n;
use Lupin3rd\Marks;
use Lupin3rd\Profile_Fields;

defined( 'ABSPATH' ) || exit;

$l3_user = $args['user'] ?? null;

if ( ! $l3_user instanceof WP_User ) {
	return;
}

$l3_registered = (int) strtotime( $l3_user->user_registered . ' UTC' );
$l3_is_new     = $l3_registered > 0 && ( time() - $l3_registered ) < WEEK_IN_SECONDS;
$l3_role       = Community::role_label( $l3_user );
?>
<a class="member" href="<?php echo esc_url( Community::profile_url( $l3_user ) ); ?>"
	data-post data-role="<?php echo esc_attr( Community::role_key( $l3_user ) ); ?>">

	<?php Marks::render_avatar( Profile_Fields::avatar_seed( $l3_user ), Profile_Fields::initials( $l3_user ) ); ?>

	<span>
		<span class="member-id">
			<span class="member-name"><?php echo esc_html( Profile_Fields::pseudonym( $l3_user ) ); ?></span>

			<?php if ( $l3_is_new ) : ?>
				<span class="badge badge-dashed"><?php esc_html_e( 'nuovo', 'lupin3rd' ); ?></span>
			<?php elseif ( 'member' !== Community::role_key( $l3_user ) ) : ?>
				<span class="badge"><?php echo esc_html( $l3_role ); ?></span>
			<?php endif; ?>

			<?php if ( Profile_Fields::key_is_verified( $l3_user ) ) : ?>
				<span class="badge badge-strong" title="<?php esc_attr_e( 'Chiave PGP controfirmata dalla redazione', 'lupin3rd' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M4 12.5l5 5L20 6.5"></path></svg>
					<?php esc_html_e( 'pgp', 'lupin3rd' ); ?>
				</span>
			<?php endif; ?>
		</span>

		<?php if ( Profile_Fields::bio( $l3_user ) ) : ?>
			<span class="member-note"><?php echo esc_html( wp_trim_words( Profile_Fields::bio( $l3_user ), 14 ) ); ?></span>
		<?php endif; ?>
	</span>

	<?php if ( Profile_Fields::joined( $l3_user ) > 0 ) : ?>
		<span class="member-pull meta"><?php echo esc_html( I18n::instance()->relative_date( $l3_registered ) ); ?></span>
	<?php endif; ?>
</a>
