<?php
/**
 * Side index, built from the piece's own h2 headings.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;

$l3_headings = $args['headings'] ?? array();

if ( ! $l3_headings ) {
	return;
}
?>
<nav class="toc widget" data-toc aria-label="<?php esc_attr_e( 'Indice', 'lupin3rd' ); ?>">
	<p class="widget-title"><?php esc_html_e( 'Indice', 'lupin3rd' ); ?></p>
	<div class="progress" aria-hidden="true"><i data-progress></i></div>
	<ul>
		<?php foreach ( $l3_headings as $l3_heading ) : ?>
			<li><a href="#<?php echo esc_attr( $l3_heading['id'] ); ?>"><?php echo esc_html( $l3_heading['text'] ); ?></a></li>
		<?php endforeach; ?>
	</ul>
</nav>
