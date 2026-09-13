<?php
/**
 * A news item.
 *
 * No side index, no lab box: a news item is read straight through.
 *
 * @package Lupin3rd
 */

use Lupin3rd\I18n;
use Lupin3rd\Marks;
use Lupin3rd\Meta_Fields;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$l3_category = Template_Tags::primary_category( get_post() );
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
						'label' => $l3_category ? $l3_category->name : __( 'Notizie', 'lupin3rd' ),
						'url'   => $l3_category ? (string) get_category_link( $l3_category ) : '',
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

				<?php
				/*
				 * The section the piece belongs to travels with the rest of the
				 * byline, as a chip, the way the prototype does it. Above the
				 * title it only repeated the breadcrumb one line higher.
				 */
				?>
				<div class="post-meta">
					<?php if ( $l3_category ) : ?>
						<a class="tag tag-strong" href="<?php echo esc_url( (string) get_category_link( $l3_category ) ); ?>"><?php echo esc_html( $l3_category->name ); ?></a>
					<?php else : ?>
						<span class="tag tag-strong"><?php echo esc_html__( 'Notizia', 'lupin3rd' ); ?></span>
					<?php endif; ?>

					<span class="meta">
						<time datetime="<?php echo esc_attr( (string) get_the_date( 'c' ) ); ?>"><?php echo esc_html( I18n::instance()->post_date() ); ?></time>
						&middot;
						<?php
						printf(
							/* translators: %d: minutes. */
							esc_html__( '%d min di lettura', 'lupin3rd' ),
							(int) Template_Tags::reading_minutes()
						);
						?>
						<?php if ( Meta_Fields::get( 'l3_primary_source' ) ) : ?>
							&middot; <a href="<?php echo esc_url( (string) Meta_Fields::get( 'l3_primary_source' ) ); ?>" rel="nofollow noopener"><?php esc_html_e( 'fonte primaria', 'lupin3rd' ); ?></a>
						<?php endif; ?>
					</span>
				</div>
			</header>

			<?php Marks::render( get_post(), 'wide' ); ?>

			<div class="grid-main-side">
				<div class="stack stack-lg">
					<div class="prose">
						<?php the_content(); ?>
						<?php
						wp_link_pages(
							array(
								'before' => '<nav class="pagination">',
								'after'  => '</nav>',
							)
						);
						?>
					</div>

					<?php get_template_part( 'template-parts/single/author-card' ); ?>

					<?php Template_Tags::tags( get_post() ); ?>

					<?php comments_template(); ?>
				</div>

				<?php get_sidebar(); ?>
			</div>
		</div>
	</article>

	<?php get_template_part( 'template-parts/single/related' ); ?>

	<?php
endwhile;

get_footer();
