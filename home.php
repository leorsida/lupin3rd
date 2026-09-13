<?php
/**
 * The blog index — the general archive.
 *
 * Reached at the site root when no static front page is set, and at the posts
 * page once one is. front-page.php takes precedence at the root, so in a
 * configured site this file only ever renders the archive.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="section">
	<div class="container stack stack-lg">
		<?php
		Template_Tags::breadcrumb(
			array(
				array( 'label' => __( 'Home', 'lupin3rd' ), 'url' => home_url( '/' ) ),
				array( 'label' => __( 'Archivio', 'lupin3rd' ), 'url' => '' ),
			)
		);
		?>

		<div class="row-between row-between--bottom">
			<div class="mw-46">
				<h1><?php esc_html_e( 'Archivio', 'lupin3rd' ); ?></h1>
				<p class="kicker"><?php esc_html_e( 'Tutto quello che abbiamo pubblicato', 'lupin3rd' ); ?></p>
			</div>
			<p class="meta">
				<?php
				printf(
					/* translators: %d: number of pieces. */
					esc_html( _n( '%d pezzo', '%d pezzi', (int) $GLOBALS['wp_query']->found_posts, 'lupin3rd' ) ),
					(int) $GLOBALS['wp_query']->found_posts
				);
				?>
			</p>
		</div>

		<?php get_template_part( 'template-parts/archive/filter-bar' ); ?>

		<?php if ( have_posts() ) : ?>
			<div data-filter-scope="archive">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/log-row' );
				endwhile;

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
