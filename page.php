<?php
/**
 * A static page.
 *
 * Title, standfirst, optional featured image, body. No sidebar of widgets:
 * these pages are read, not browsed. The one exception is the index — a page
 * with three or more h2 headings is a piece with sections, and a reader who
 * has to find «come segnalare un errore» halfway down deserves the same index
 * an article gets. Below that the page stays a single column: an empty aside,
 * or one holding a list of two links, is worse than no aside.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Marks;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	/*
	 * One pass over the rendered content: it adds the ids to the h2 headings
	 * and hands back the list the index is built from. The content is then
	 * printed from here rather than through the_content(), because printing it
	 * again would run the filters a second time and lose the ids this pass
	 * added.
	 */
	$l3_parsed = Template_Tags::extract_headings( (string) apply_filters( 'the_content', get_the_content() ) );

	/*
	 * Three sections is where an index starts being worth its column. Below
	 * that it is a list of one or two links next to a page the reader can see
	 * the end of, and it costs the full width of the aside to say nothing.
	 */
	$l3_index = count( $l3_parsed['headings'] ) >= 3 ? $l3_parsed['headings'] : array();
	?>

	<article class="section">
		<div class="container stack">
			<?php
			Template_Tags::breadcrumb(
				array(
					array(
						'label' => __( 'Home', 'lupin3rd' ),
						'url'   => home_url( '/' ),
					),
					array(
						'label' => get_the_title(),
						'url'   => '',
					),
				)
			);
			?>

			<header class="stack">
				<h1><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="lead"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
				<?php endif; ?>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<?php
				/*
				 * Only when the desk actually set one. An article without a
				 * featured image still gets a mark, because it appears in
				 * lists and a card needs a picture; a static page appears in
				 * no list, so here an invented one would be decoration
				 * standing in for content. Same call as single.php: the image
				 * is the lead, so it is fetched eagerly and at the large size.
				 */
				Marks::render( get_post(), 'wide' );
				?>
			<?php endif; ?>

			<div class="<?php echo $l3_index ? 'grid-main-side' : 'stack'; ?>">
				<div class="stack stack-lg">
					<div class="prose">
						<?php
						/*
						 * Printed without kses: this string is already the output of
						 * the_content filters, and running kses over it would strip
						 * exactly what the theme's own blocks depend on.
						 */
						echo $l3_parsed['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
						<?php
						wp_link_pages(
							array(
								'before' => '<nav class="pagination">',
								'after'  => '</nav>',
							)
						);
						?>
					</div>
				</div>

				<?php if ( $l3_index ) : ?>
					<aside>
						<?php get_template_part( 'template-parts/single/table-of-contents', null, array( 'headings' => $l3_index ) ); ?>
					</aside>
				<?php endif; ?>
			</div>
		</div>
	</article>

	<?php
endwhile;

get_footer();
