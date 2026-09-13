<?php
/**
 * The tag cloud, curated by hand upstream.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;

$l3_tags = get_terms(
	array(
		'taxonomy'   => 'post_tag',
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 10,
		'hide_empty' => true,
	)
);

if ( is_wp_error( $l3_tags ) || ! $l3_tags ) {
	return;
}
?>
<div class="widget">
	<p class="widget-title"><?php esc_html_e( 'Tag', 'lupin3rd' ); ?></p>
	<div class="row-wrap">
		<?php foreach ( $l3_tags as $l3_tag ) : ?>
			<a class="tag" href="<?php echo esc_url( (string) get_term_link( $l3_tag ) ); ?>">
				<?php echo esc_html( $l3_tag->name ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</div>
