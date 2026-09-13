<?php
/**
 * The lead piece, laid out across two columns.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Content_Types;
use Lupin3rd\Marks;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;
?>
<a class="post split-feature" href="<?php the_permalink(); ?>"<?php echo Template_Tags::filter_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_attr. ?>>
	<?php Marks::render( get_post(), 'wide', Content_Types::label( get_post_type() ) ); ?>

	<div class="stack">
		<?php Template_Tags::post_meta( get_post(), false ); ?>
		<h2><?php the_title(); ?></h2>
		<p class="lead"><?php echo esc_html( Template_Tags::summary( get_post(), 34 ) ); ?></p>
	</div>
</a>
