<?php
/**
 * A post as a dense archive row.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Content_Types;
use Lupin3rd\I18n;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

$l3_category = Template_Tags::primary_category( get_post() );
?>
<article class="log-row"<?php echo Template_Tags::filter_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_attr. ?>>
	<p class="meta num">
		<time datetime="<?php echo esc_attr( (string) get_the_date( 'c' ) ); ?>">
			<?php echo esc_html( I18n::instance()->post_date() ); ?>
		</time>
	</p>

	<div>
		<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p><?php echo esc_html( Template_Tags::summary( get_post(), 22 ) ); ?></p>
	</div>

	<p class="meta">
		<?php echo esc_html( $l3_category ? $l3_category->name : Content_Types::label( get_post_type() ) ); ?>
	</p>
</article>
