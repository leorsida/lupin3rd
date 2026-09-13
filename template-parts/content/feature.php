<?php
/**
 * The lead piece, laid out across two columns.
 *
 * When it opens the home page it changes shape: the cover fills the width, the
 * words sit over its left half, and a gradient carries one into the other. It
 * also carries the page's `h1` at full size, because there the lead story *is*
 * the heading and a front page says so by how big it is.
 *
 * The standfirst is shorter in that shape. Next to a headline that size, the
 * thirty-four words that read as a summary beside a card read as a paragraph
 * somebody forgot to cut.
 *
 * @package Lupin3rd
 *
 * @var array{heading?: string} $args
 */

use Lupin3rd\Content_Types;
use Lupin3rd\Marks;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

$l3_is_lead = isset( $args['heading'] ) && 'h1' === $args['heading'];
?>
<a class="post <?php echo $l3_is_lead ? 'lead-cover' : 'split-feature'; ?>" href="<?php the_permalink(); ?>"<?php echo Template_Tags::filter_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_attr. ?>>
	<?php
	/*
	 * No corner label on the lead: it is pinned bottom-left, which is where the
	 * words now are. The post type is in the metadata line either way.
	 */
	Marks::render( get_post(), 'wide', $l3_is_lead ? '' : Content_Types::label( get_post_type() ) );
	?>

	<div class="stack<?php echo $l3_is_lead ? ' lead-cover-text' : ''; ?>">
		<?php Template_Tags::post_meta( get_post(), false ); ?>
		<?php if ( $l3_is_lead ) : ?>
			<h1><?php the_title(); ?></h1>
		<?php else : ?>
			<h2><?php the_title(); ?></h2>
		<?php endif; ?>
		<p class="lead"><?php echo esc_html( Template_Tags::summary( get_post(), $l3_is_lead ? 22 : 34 ) ); ?></p>
	</div>
</a>
