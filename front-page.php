<?php
/**
 * Home.
 *
 * A lead piece, the running feed, recent tutorials, and the dossiers. The
 * order is editorial: what happened, how to do it, what we are still digging
 * into.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Content_Types;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * The lead is a choice, not an accident of ordering: a sticky post if the desk
 * marked one, otherwise the most recent piece. Dossier instalments are excluded
 * throughout — "Puntata 2" means nothing to somebody arriving on the home page,
 * and the dossier index is the thing worth linking to.
 */
$l3_common = array(
	'post_type'    => Content_Types::editorial_types(),
	'post_parent'  => 0,
	'post_status'  => 'publish',
	'no_found_rows' => true,
);

$l3_sticky = array_filter( (array) get_option( 'sticky_posts' ) );

$l3_lead = new WP_Query(
	$l3_common + array(
		'posts_per_page'      => 1,
		'post__in'            => $l3_sticky ?: null,
		'ignore_sticky_posts' => true,
	)
);

if ( ! $l3_lead->have_posts() ) {
	$l3_lead = new WP_Query( $l3_common + array( 'posts_per_page' => 1 ) );
}

$l3_lead_id = $l3_lead->have_posts() ? $l3_lead->posts[0]->ID : 0;

$l3_feed = new WP_Query(
	$l3_common + array(
		'posts_per_page' => 6,
		'post__not_in'   => array_filter( array( $l3_lead_id ) ),
	)
);

$l3_tutorials = new WP_Query(
	array(
		'post_type'      => Content_Types::TUTORIAL,
		'posts_per_page' => 3,
		'post_status'    => 'publish',
		'no_found_rows'  => true,
	)
);

$l3_dossiers = new WP_Query(
	array(
		'post_type'      => Content_Types::DOSSIER,
		'posts_per_page' => 2,
		'post_parent'    => 0,
		'no_found_rows'  => true,
	)
);
?>

<section class="section hero">
	<div class="container">
		<p class="eyebrow"><?php esc_html_e( 'Rivista indipendente · dal 1999', 'lupin3rd' ); ?></p>
		<h1><?php echo esc_html( get_bloginfo( 'description' ) ?: __( 'Quello che l\'informatica fa quando nessuno guarda.', 'lupin3rd' ) ); ?></h1>

		<div class="grid-2-1">
			<p class="lead">
				<?php esc_html_e( 'Notizie, tutorial con laboratorio riproducibile e inchieste a puntate. Nessun investitore, nessun cookie di profilazione, nessuna terza parte in pagina.', 'lupin3rd' ); ?>
			</p>
			<div class="row">
				<a class="btn btn-primary btn-arrow" href="<?php echo esc_url( (string) get_post_type_archive_link( Content_Types::TUTORIAL ) ); ?>">
					<?php esc_html_e( 'Tutorial', 'lupin3rd' ); ?>
				</a>
				<a class="btn btn-secondary" href="<?php echo esc_url( Template_Tags::archive_url() ); ?>">
					<?php esc_html_e( 'Archivio', 'lupin3rd' ); ?>
				</a>
			</div>
		</div>
	</div>
</section>

<?php if ( $l3_lead->have_posts() ) : ?>
	<section class="section">
		<div class="container">
			<div class="row-between">
				<p class="kicker"><?php esc_html_e( 'In evidenza', 'lupin3rd' ); ?></p>
			</div>
			<?php
			while ( $l3_lead->have_posts() ) :
				$l3_lead->the_post();
				get_template_part( 'template-parts/content/feature' );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
<?php endif; ?>

<?php if ( $l3_feed->have_posts() ) : ?>
	<section class="section">
		<div class="container stack">
			<div class="row-between">
				<div>
					<p class="kicker"><?php esc_html_e( 'Ultime dal fronte', 'lupin3rd' ); ?></p>
					<h2><?php esc_html_e( 'Cosa è successo', 'lupin3rd' ); ?></h2>
				</div>
				<a class="btn btn-ghost btn-arrow btn-sm" href="<?php echo esc_url( Template_Tags::archive_url() ); ?>">
					<?php esc_html_e( 'Tutto l\'archivio', 'lupin3rd' ); ?>
				</a>
			</div>

			<div data-filter-scope>
				<?php
				while ( $l3_feed->have_posts() ) :
					$l3_feed->the_post();
					get_template_part( 'template-parts/content/log-row' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $l3_tutorials->have_posts() ) : ?>
	<section class="section">
		<div class="container stack">
			<div class="row-between">
				<div class="mw-46">
					<p class="kicker"><?php esc_html_e( 'Tutorial', 'lupin3rd' ); ?></p>
					<h2><?php esc_html_e( 'Passo-passo, con laboratorio riproducibile', 'lupin3rd' ); ?></h2>
				</div>
				<a class="btn btn-ghost btn-arrow btn-sm" href="<?php echo esc_url( (string) get_post_type_archive_link( Content_Types::TUTORIAL ) ); ?>">
					<?php esc_html_e( 'Tutti i tutorial', 'lupin3rd' ); ?>
				</a>
			</div>

			<div class="grid-3">
				<?php
				while ( $l3_tutorials->have_posts() ) :
					$l3_tutorials->the_post();
					get_template_part( 'template-parts/content/card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $l3_dossiers->have_posts() ) : ?>
	<section class="section">
		<div class="container grid-main-side">
			<div class="stack">
				<div>
					<p class="kicker"><?php esc_html_e( 'Dossier', 'lupin3rd' ); ?></p>
					<h2 class="mw-36"><?php esc_html_e( 'Inchieste lunghe, pubblicate a puntate', 'lupin3rd' ); ?></h2>
				</div>

				<div class="grid-2">
					<?php
					while ( $l3_dossiers->have_posts() ) :
						$l3_dossiers->the_post();
						?>
						<article class="card-rule">
							<?php Template_Tags::post_meta(); ?>
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo esc_html( Template_Tags::summary( get_post(), 30 ) ); ?></p>
						</article>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</div>

				<div class="callout callout--legal">
					<p class="callout-title"><?php esc_html_e( 'Perimetro legale', 'lupin3rd' ); ?></p>
					<p><?php esc_html_e( 'Quello che pubblichiamo serve a capire e a difendersi. Provare una tecnica su sistemi che non sono tuoi, o senza un incarico scritto, è un reato in quasi ogni giurisdizione.', 'lupin3rd' ); ?></p>
				</div>
			</div>

			<?php get_sidebar(); ?>
		</div>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/home/newsletter' ); ?>

<?php
get_footer();
