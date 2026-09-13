<?php
/**
 * Who wrote this.
 *
 * The site publishes under pseudonyms, so the name shown is the display name
 * the desk chose, never the account login. No photograph: the avatar is the
 * same deterministic pattern the comments use.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Marks;

defined( 'ABSPATH' ) || exit;

$l3_author = get_user_by( 'id', (int) get_post_field( 'post_author', get_post() ) );

if ( ! $l3_author ) {
	return;
}

$l3_name = (string) $l3_author->display_name;
$l3_bio  = (string) get_the_author_meta( 'description', $l3_author->ID );
?>
<div class="card authorcard">
	<?php Marks::render_avatar( $l3_name, mb_strtoupper( mb_substr( $l3_name, 0, 2 ) ) ); ?>

	<div>
		<p class="meta"><?php esc_html_e( 'Autore', 'lupin3rd' ); ?></p>
		<h3><?php echo esc_html( $l3_name ); ?></h3>
		<?php if ( $l3_bio ) : ?>
			<p><?php echo esc_html( $l3_bio ); ?></p>
		<?php endif; ?>
	</div>
</div>
