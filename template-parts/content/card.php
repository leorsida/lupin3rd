<?php
/**
 * A post as a grid card.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Content_Types;
use Lupin3rd\Marks;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;
?>
<a class="post" href="<?php the_permalink(); ?>"<?php echo Template_Tags::filter_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_attr. ?>>
	<?php Marks::render( get_post(), '', Content_Types::label( get_post_type() ) ); ?>
	<?php Template_Tags::post_meta( get_post(), false ); ?>
	<h3><?php the_title(); ?></h3>
	<p><?php echo esc_html( Template_Tags::summary( get_post() ) ); ?></p>
</a>
