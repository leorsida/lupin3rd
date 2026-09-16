<?php
/**
 * The status bar above the navigation.
 *
 * Node, mirror and key: the three things a reader of this magazine checks
 * before reading anything else.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;

$l3_edition = get_option( 'l3_edition_number', '182' );
?>
<div class="statusbar">
	<div class="container">
		<div class="ticker">
			<span><?php esc_html_e( 'nodo', 'lupin3rd' ); ?> <b><?php esc_html_e( 'online', 'lupin3rd' ); ?></b></span>
			<span><?php esc_html_e( 'mirror .onion', 'lupin3rd' ); ?> <b><?php esc_html_e( 'soon', 'lupin3rd' ); ?></b></span>
			<span><?php esc_html_e( 'chiave pgp', 'lupin3rd' ); ?> <b>435296A73BCF8593</b></span>
		</div>
		<span>
			<?php
			printf(
				/* translators: 1: edition number, 2: date. */
				esc_html__( 'ed. #%1$s · %2$s', 'lupin3rd' ),
				esc_html( (string) $l3_edition ),
				esc_html( Lupin3rd\I18n::instance()->date( time() ) )
			);
			?>
			<span class="caret" aria-hidden="true"></span>
		</span>
	</div>
</div>
