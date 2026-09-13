<?php
/**
 * The lead piece, laid out across two columns.
 *
 * When it opens the home page it changes shape: the cover fills the width and
 * the words sit on top of it, the way a front page leads. It also carries the
 * page's `h1`, because there the lead story *is* the heading — while keeping
 * the `h2` size, since the design system separates what a heading means from
 * how big it is and this is exactly what that separation is for.
 *
 * The scrim under the text fades to `--bg` and not to black: the text then
 * sits on the same colour it sits on everywhere else on the page, so the
 * contrast is the one that was measured, whatever the cover turns out to be —
 * a generated mark, a dark photograph, a light one — and in both themes.
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
			<h1 class="h2"><?php the_title(); ?></h1>
		<?php else : ?>
			<h2><?php the_title(); ?></h2>
		<?php endif; ?>
		<p class="lead"><?php echo esc_html( Template_Tags::summary( get_post(), 34 ) ); ?></p>
	</div>
</a>
