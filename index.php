<?php
/**
 * Fallback template.
 *
 * Reached only when nothing more specific applies. Renders the archive, which
 * is the right answer for almost any list of posts.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="section">
	<div class="container stack">
		<div class="row-between">
			<div class="mw-46">
				<h1><?php echo esc_html( wp_get_document_title() ); ?></h1>
				<p class="kicker"><?php esc_html_e( 'Archivio', 'lupin3rd' ); ?></p>
			</div>
		</div>

		<?php if ( have_posts() ) : ?>
			<div>
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/log-row' );
				endwhile;
				?>
			</div>

			<?php Lupin3rd\Template_Tags::pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/empty-state' ); ?>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
