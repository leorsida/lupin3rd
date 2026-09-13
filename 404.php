<?php
/**
 * Not found.
 *
 * Suggests the tags closest to the requested slug, because somebody who
 * mistyped a URL usually knows roughly what they were after.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Content_Types;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

$l3_suggestions = Template_Tags::tag_suggestions();

$l3_recent = new WP_Query(
	array(
		'post_type'      => Content_Types::editorial_types(),
		'posts_per_page' => 3,
		'post_parent'    => 0,
		'post_status'    => 'publish',
		'no_found_rows'  => true,
	)
);
?>

<section class="section">
	<div class="container stack">
		<header class="stack">
			<p class="kicker">404</p>
			<h1><?php esc_html_e( 'Questo indirizzo non porta da nessuna parte', 'lupin3rd' ); ?></h1>
			<p class="lead"><?php esc_html_e( 'Può essere un refuso, o un pezzo che non è mai esistito. Gli URL pubblicati non cambiano mai, quindi se avevi un link che funzionava, funziona ancora.', 'lupin3rd' ); ?></p>
		</header>

		<?php if ( $l3_suggestions ) : ?>
			<div>
				<p class="widget-title"><?php esc_html_e( 'Forse cercavi', 'lupin3rd' ); ?></p>
				<div class="row-wrap">
					<?php foreach ( $l3_suggestions as $l3_term ) : ?>
						<a class="tag" href="<?php echo esc_url( (string) get_term_link( $l3_term ) ); ?>">
							<?php echo esc_html( $l3_term->name ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $l3_recent->have_posts() ) : ?>
			<div class="stack">
				<h2><?php esc_html_e( 'Intanto, gli ultimi tre', 'lupin3rd' ); ?></h2>
				<div class="grid-3">
					<?php
					while ( $l3_recent->have_posts() ) :
						$l3_recent->the_post();
						get_template_part( 'template-parts/content/card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
