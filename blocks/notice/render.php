<?php
/**
 * Server render for l3/notice.
 *
 * @package Lupin3rd
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Inner blocks, already rendered.
 */

defined( 'ABSPATH' ) || exit;

if ( '' === trim( (string) $content ) ) {
	return;
}

$l3_variant = (string) ( $attributes['variant'] ?? 'method' );

$l3_titles = array(
	'method'     => __( 'Nota di metodo', 'lupin3rd' ),
	'legal'      => __( 'Perimetro legale', 'lupin3rd' ),
	'correction' => __( 'Correzione', 'lupin3rd' ),
);

/*
 * The icons carry no meaning a reader needs: the heading says what the box is.
 * They are decoration and are hidden from assistive technology.
 */
$l3_icons = array(
	'method'     => '<circle cx="12" cy="12" r="9"/><path d="M12 10.5v6M12 7.5v.01"/>',
	'legal'      => '<path d="M12 3l9 16H3z"/><path d="M12 9.5v4M12 16.5v.01"/>',
	'correction' => '<path d="M4 12h16"/><path d="M9 7l-5 5 5 5"/>',
);

$l3_title = $l3_titles[ $l3_variant ] ?? $l3_titles['method'];
$l3_icon  = $l3_icons[ $l3_variant ] ?? $l3_icons['method'];
$l3_class = 'legal' === $l3_variant ? 'callout callout--legal' : 'callout';
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes( array( 'class' => $l3_class ) ) ); ?>>
	<p class="callout-title">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
			<?php echo wp_kses( $l3_icon, array( 'circle' => array( 'cx' => true, 'cy' => true, 'r' => true ), 'path' => array( 'd' => true ) ) ); ?>
		</svg>
		<?php echo esc_html( $l3_title ); ?>
	</p>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner blocks, rendered by core. ?>
</div>
