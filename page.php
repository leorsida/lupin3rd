<?php
/**
 * A static page.
 *
 * Title, standfirst, body. No sidebar: these pages are read, not browsed.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<article class="section">
		<div class="container stack">
			<?php
			Template_Tags::breadcrumb(
				array(
					array( 'label' => __( 'Home', 'lupin3rd' ), 'url' => home_url( '/' ) ),
					array( 'label' => get_the_title(), 'url' => '' ),
				)
			);
			?>

			<header class="stack">
				<h1><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="lead"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
				<?php endif; ?>
			</header>

			<div class="prose mw-46">
				<?php the_content(); ?>
				<?php wp_link_pages( array( 'before' => '<nav class="pagination">', 'after' => '</nav>' ) ); ?>
			</div>
		</div>
	</article>

	<?php
endwhile;

get_footer();
