<?php
/**
 * Shown when a query returns nothing.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;

$l3_args  = wp_parse_args(
	$args ?? array(),
	array(
		'title'   => __( 'Nessun risultato.', 'lupin3rd' ),
		'message' => __( 'Prova con un tag, o togli qualche filtro.', 'lupin3rd' ),
		'hidden'  => false,
	)
);
?>
<div class="emptystate<?php echo $l3_args['hidden'] ? ' hidden' : ''; ?>" data-filter-empty>
	<p class="emptystate-mark" aria-hidden="true">∅</p>
	<h3><?php echo esc_html( $l3_args['title'] ); ?></h3>
	<p><?php echo esc_html( $l3_args['message'] ); ?></p>
	<div class="row">
		<button class="btn btn-secondary btn-sm" type="button" data-reset-filters>
			<?php esc_html_e( 'Azzera i filtri', 'lupin3rd' ); ?>
		</button>
		<a class="btn btn-ghost btn-sm btn-arrow" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php esc_html_e( 'Torna alla home', 'lupin3rd' ); ?>
		</a>
	</div>
</div>
