<?php
/**
 * Who wrote this.
 *
 * The site publishes under pseudonyms, so the name shown is the one the
 * account uses now, never the login it registered with. No photograph: the
 * avatar is the same pattern, drawn from the same stored seed, that the
 * comments and the member profile use — one identity, one source.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Community;
use Lupin3rd\Marks;
use Lupin3rd\Profile_Fields;

defined( 'ABSPATH' ) || exit;

$l3_author = get_user_by( 'id', (int) get_post_field( 'post_author', get_post() ) );

if ( ! $l3_author ) {
	return;
}

$l3_name = Profile_Fields::pseudonym( $l3_author );
$l3_bio  = Profile_Fields::bio( $l3_author ) ?: (string) get_the_author_meta( 'description', $l3_author->ID );
?>
<div class="card authorcard">
	<?php Marks::render_avatar( Profile_Fields::avatar_seed( $l3_author ), Profile_Fields::initials( $l3_author ) ); ?>

	<div>
		<p class="meta"><?php esc_html_e( 'Autore', 'lupin3rd' ); ?></p>
		<h3><a href="<?php echo esc_url( Community::profile_url( $l3_author ) ); ?>"><?php echo esc_html( $l3_name ); ?></a></h3>
		<?php if ( $l3_bio ) : ?>
			<p><?php echo esc_html( $l3_bio ); ?></p>
		<?php endif; ?>
	</div>
</div>
