<?php
/**
 * Taxonomy filters.
 *
 * Each chip is a real link that runs a real query. The script upgrades them to
 * instant client-side filtering; without it they still work, which is what a
 * reader with JavaScript off and every crawler actually get.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Archive_Filters;
use Lupin3rd\Content_Types;

defined( 'ABSPATH' ) || exit;

$l3_formats = array( '' => __( 'Tutto', 'lupin3rd' ) );

foreach ( Content_Types::editorial_types() as $l3_type ) {
	$l3_formats[ $l3_type ] = Content_Types::label( $l3_type );
}

$l3_current = Archive_Filters::current( 'format' );
?>
<div class="filterbar" data-filter-group="format" data-filter-target="archive" role="group" aria-label="<?php esc_attr_e( 'Filtra per formato', 'lupin3rd' ); ?>">
	<?php foreach ( $l3_formats as $l3_value => $l3_label ) : ?>
		<a
			class="chip<?php echo $l3_value === $l3_current ? ' is-active' : ''; ?>"
			href="<?php echo esc_url( Archive_Filters::url( 'format', (string) $l3_value ) ); ?>"
			data-value="<?php echo esc_attr( (string) $l3_value ); ?>"
			aria-pressed="<?php echo $l3_value === $l3_current ? 'true' : 'false'; ?>">
			<?php echo esc_html( $l3_label ); ?>
		</a>
	<?php endforeach; ?>
</div>
