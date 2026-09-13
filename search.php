<?php
/**
 * Search results.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

$l3_query = get_search_query();
?>

<section class="section">
	<div class="container stack">
		<header class="stack">
			<h1>
				<?php
				printf(
					/* translators: %s: search term. */
					esc_html__( 'Risultati per «%s»', 'lupin3rd' ),
					esc_html( $l3_query )
				);
				?>
			</h1>
			<p class="kicker"><?php esc_html_e( 'Ricerca', 'lupin3rd' ); ?></p>
			<p class="meta">
				<?php
				printf(
					/* translators: %d: number of results. */
					esc_html( _n( '%d pezzo trovato', '%d pezzi trovati', (int) $GLOBALS['wp_query']->found_posts, 'lupin3rd' ) ),
					(int) $GLOBALS['wp_query']->found_posts
				);
				?>
			</p>
		</header>

		<?php if ( have_posts() ) : ?>
			<div>
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/log-row' );
				endwhile;
				?>
			</div>

			<?php Template_Tags::pagination(); ?>
		<?php else : ?>
			<?php
			get_template_part(
				'template-parts/content/empty-state',
				null,
				array(
					'title'   => __( 'Nessun risultato.', 'lupin3rd' ),
					'message' => __( 'Prova con un termine più corto, o con un tag.', 'lupin3rd' ),
				)
			);
			?>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
