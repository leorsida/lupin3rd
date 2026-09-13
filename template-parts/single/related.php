<?php
/**
 * What to read after this piece.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

$l3_related = Template_Tags::related( get_post() );

if ( ! $l3_related ) {
	return;
}
?>
<section class="section" aria-labelledby="l3-related">
	<div class="container stack">
		<div class="row-between">
			<h2 id="l3-related"><?php esc_html_e( 'Continua da qui', 'lupin3rd' ); ?></h2>
			<a class="btn btn-ghost btn-arrow btn-sm" href="<?php echo esc_url( Template_Tags::archive_url() ); ?>">
				<?php esc_html_e( 'Archivio', 'lupin3rd' ); ?>
			</a>
		</div>

		<div class="grid-3">
			<?php
			foreach ( $l3_related as $l3_post ) :
				setup_postdata( $GLOBALS['post'] = $l3_post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found -- The loop the card partial expects.
				get_template_part( 'template-parts/content/card' );
			endforeach;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
