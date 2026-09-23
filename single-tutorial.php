<?php
/**
 * A tutorial.
 *
 * Carries what a news item does not: a difficulty meter, an estimated time, a
 * side index built from the h2 headings, and the lab details.
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

	$l3_parsed   = Template_Tags::extract_headings( (string) apply_filters( 'the_content', get_the_content() ) );
	$l3_category = Template_Tags::primary_category( get_post() );
	$l3_minutes  = (int) Meta_Fields::get( 'l3_estimated_time', 0 );
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
						'label' => __( 'Tutorial', 'lupin3rd' ),
						'url'   => (string) get_post_type_archive_link( 'tutorial' ),
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
						<span class="tag tag-strong"><?php echo esc_html__( 'Tutorial', 'lupin3rd' ); ?></span>
					<?php endif; ?>

					<span class="meta">
						<time datetime="<?php echo esc_attr( (string) get_the_date( 'c' ) ); ?>"><?php echo esc_html( I18n::instance()->post_date() ); ?></time>
						&middot; <?php Template_Tags::difficulty(); ?>
						<?php if ( $l3_minutes ) : ?>
							&middot;
							<?php
							printf(
								/* translators: %d: minutes. */
								esc_html__( '%d min', 'lupin3rd' ),
								(int) $l3_minutes
							);
							?>
						<?php endif; ?>
					</span>

					<?php Template_Tags::markdown_link(); ?>
				</div>
			</header>

			<?php Marks::render( get_post(), 'wide', __( 'Tutorial', 'lupin3rd' ) ); ?>

			<div class="grid-main-side">
				<div class="stack stack-lg">
					<div class="prose" data-article>
						<?php if ( Meta_Fields::get( 'l3_prerequisites' ) ) : ?>
							<div class="callout">
								<p class="callout-title"><?php esc_html_e( 'Prerequisiti', 'lupin3rd' ); ?></p>
								<ul>
									<?php foreach ( preg_split( '/\R/', (string) Meta_Fields::get( 'l3_prerequisites' ) ) ?: array() as $l3_line ) : ?>
										<?php if ( trim( $l3_line ) ) : ?>
											<li><?php echo esc_html( trim( $l3_line ) ); ?></li>
										<?php endif; ?>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php
						/*
						 * Printed without kses. This string is already the output of
						 * the_content filters, and kses would strip exactly what the
						 * theme's own blocks depend on: the data-* attributes the copy
						 * button binds to, and the <svg> in a notice's heading. Running
						 * it a second time sanitises nothing new and breaks the page.
						 */
						echo $l3_parsed['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>

						<?php if ( Meta_Fields::get( 'l3_lab_repository' ) || Meta_Fields::get( 'l3_sample_hash' ) ) : ?>
							<div class="callout">
								<p class="callout-title"><?php esc_html_e( 'Laboratorio', 'lupin3rd' ); ?></p>
								<?php if ( Meta_Fields::get( 'l3_lab_repository' ) ) : ?>
									<p>
										<?php esc_html_e( 'Repository:', 'lupin3rd' ); ?>
										<a href="<?php echo esc_url( (string) Meta_Fields::get( 'l3_lab_repository' ) ); ?>" rel="noopener">
											<?php echo esc_html( (string) Meta_Fields::get( 'l3_lab_repository' ) ); ?>
										</a>
									</p>
								<?php endif; ?>
								<?php if ( Meta_Fields::get( 'l3_sample_hash' ) ) : ?>
									<p><?php esc_html_e( 'SHA-256 del campione:', 'lupin3rd' ); ?> <code class="code-inline"><?php echo esc_html( (string) Meta_Fields::get( 'l3_sample_hash' ) ); ?></code></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php get_template_part( 'template-parts/single/author-card' ); ?>

					<?php Template_Tags::tags( get_post() ); ?>

					<?php comments_template(); ?>
				</div>

				<aside>
					<?php
					if ( $l3_parsed['headings'] ) {
						get_template_part( 'template-parts/single/table-of-contents', null, array( 'headings' => $l3_parsed['headings'] ) );
					}
					get_template_part( 'template-parts/sidebar/widget-links' );
					?>
				</aside>
			</div>
		</div>
	</article>

	<?php get_template_part( 'template-parts/single/related' ); ?>

	<?php
endwhile;

get_footer();
