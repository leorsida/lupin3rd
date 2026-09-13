<?php
/**
 * Navigation between the instalments of a dossier.
 *
 * On the index (the parent) this is the list of instalments in the order the
 * desk set. On an instalment it is the way back to the index plus the
 * neighbours, so a reader who arrived from a search result can tell what they
 * are in the middle of.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Content_Types;
use Lupin3rd\I18n;

defined( 'ABSPATH' ) || exit;

$l3_post   = get_post();
$l3_parent = $l3_post->post_parent ? get_post( $l3_post->post_parent ) : $l3_post;

if ( ! $l3_parent ) {
	return;
}

$l3_instalments = get_posts(
	array(
		'post_type'      => Content_Types::DOSSIER,
		'post_parent'    => $l3_parent->ID,
		'posts_per_page' => -1,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'date'       => 'ASC',
		),
		'post_status'    => 'publish',
	)
);

if ( ! $l3_instalments ) {
	return;
}

$l3_is_index = $l3_post->ID === $l3_parent->ID;
?>

<nav class="widget" aria-label="<?php esc_attr_e( 'Puntate del dossier', 'lupin3rd' ); ?>">
	<p class="widget-title">
		<?php
		echo $l3_is_index
			? esc_html__( 'Le puntate', 'lupin3rd' )
			: esc_html__( 'In questo dossier', 'lupin3rd' );
		?>
	</p>

	<?php if ( ! $l3_is_index ) : ?>
		<p class="note-sm">
			<a href="<?php echo esc_url( (string) get_permalink( $l3_parent ) ); ?>">
				<?php echo esc_html( get_the_title( $l3_parent ) ); ?>
			</a>
		</p>
	<?php endif; ?>

	<ol class="list-rule">
		<?php foreach ( $l3_instalments as $l3_index => $l3_instalment ) : ?>
			<li>
				<?php if ( $l3_instalment->ID === $l3_post->ID ) : ?>
					<span aria-current="page"><?php echo esc_html( get_the_title( $l3_instalment ) ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( (string) get_permalink( $l3_instalment ) ); ?>">
						<?php echo esc_html( get_the_title( $l3_instalment ) ); ?>
					</a>
				<?php endif; ?>
				<span class="meta num">
					<?php echo esc_html( I18n::instance()->post_date( $l3_instalment ) ); ?>
				</span>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
