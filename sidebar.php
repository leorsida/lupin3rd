<?php
/**
 * The widget column.
 *
 * Fixed partials rather than registered widget areas: more control, and a
 * smaller surface for somebody to drop a third-party script into.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;
?>
<aside>
	<?php get_template_part( 'template-parts/sidebar/widget-tags' ); ?>
	<?php get_template_part( 'template-parts/sidebar/widget-links' ); ?>
</aside>
