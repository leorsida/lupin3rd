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

defined( 'ABSPATH' ) || exit;

get_header();

/**
 * Return the tags whose slug most resembles the requested path.
 *
 * @param int $limit Maximum suggestions.
 * @return array<int, WP_Term>
 */
function l3_404_suggestions( int $limit = 5 ): array {
	$path     = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$segments = array_filter( explode( '/', $path ) );
	$slug     = sanitize_title( (string) ( $segments ? (string) array_pop( $segments ) : '' ) );
	$terms = get_terms(
		array(
			'taxonomy'   => 'post_tag',
			'hide_empty' => true,
			'number'     => 60,
		)
	);

	if ( is_wp_error( $terms ) || ! $terms || '' === $slug ) {
		return is_wp_error( $terms ) ? array() : array_slice( (array) $terms, 0, $limit );
	}

	usort(
		$terms,
		static fn ( WP_Term $a, WP_Term $b ): int =>
			levenshtein( $slug, $a->slug ) <=> levenshtein( $slug, $b->slug )
	);

	return array_slice( $terms, 0, $limit );
}

$l3_suggestions = l3_404_suggestions();

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
