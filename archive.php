<?php
/**
 * Archives: post type, category, tag, taxonomy, date.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

$l3_title = get_the_archive_title();
?>

<section class="section">
	<div class="container stack">
		<?php
		Template_Tags::breadcrumb(
			array(
				array(
					'label' => __( 'Home', 'lupin3rd' ),
					'url'   => home_url( '/' ),
				),
				array(
					'label' => wp_strip_all_tags( $l3_title ),
					'url'   => '',
				),
			)
		);
		?>

		<div class="row-between">
			<div class="mw-46">
				<?php
				/*
				 * On a post type archive the kicker is the post type and the
				 * title is the same word. Print it only when it says something
				 * the title does not — "Tag", "Categoria", "Difficoltà".
				 */
				$l3_kicker = Template_Tags::archive_kicker();
				?>
				<h1><?php echo esc_html( wp_strip_all_tags( $l3_title ) ); ?></h1>
				<?php if ( 0 !== strcasecmp( $l3_kicker, wp_strip_all_tags( $l3_title ) ) ) : ?>
					<p class="kicker"><?php echo esc_html( $l3_kicker ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( get_the_archive_description() ) : ?>
			<p class="lead"><?php echo wp_kses_post( get_the_archive_description() ); ?></p>
		<?php endif; ?>

		<?php get_template_part( 'template-parts/archive/filter-bar' ); ?>

		<?php if ( have_posts() ) : ?>
			<div data-filter-scope="archive">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/log-row' );
				endwhile;
				?>
				<?php
				get_template_part(
					'template-parts/content/empty-state',
					null,
					array(
						'hidden'  => true,
						'title'   => __( 'Nessun pezzo con questi filtri.', 'lupin3rd' ),
						'message' => __( 'Togline uno e riprova.', 'lupin3rd' ),
					)
				);
				?>
			</div>

			<?php Template_Tags::pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/empty-state' ); ?>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
