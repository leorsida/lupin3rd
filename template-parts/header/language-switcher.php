<?php
/**
 * Language switch.
 *
 * Two real links, never a script that rewrites the text in place: each
 * language has its own content, its own URLs and its own feeds.
 *
 * The pair below is what the switch shows; `I18n::published()` is what decides
 * whether an entry is a working link or an inert label. Phase 1 publishes
 * Italian only, so EN is shown greyed and unclickable rather than pointing at
 * an /en/ that would answer 404. When the English build lands, `published()`
 * gains `'en'` and this file needs no edit.
 *
 * @package Lupin3rd
 */

use Lupin3rd\I18n;

defined( 'ABSPATH' ) || exit;

$l3_current   = I18n::instance()->language();
$l3_published = I18n::published();
?>
<div class="langswitch">
	<?php foreach ( array( 'it', 'en' ) as $l3_code ) : ?>
		<?php if ( $l3_code === $l3_current ) : ?>
			<span class="is-on" aria-current="true"><?php echo esc_html( strtoupper( $l3_code ) ); ?></span>
		<?php elseif ( in_array( $l3_code, $l3_published, true ) ) : ?>
			<a href="<?php echo esc_url( home_url( '/' . $l3_code . '/' ) ); ?>" lang="<?php echo esc_attr( $l3_code ); ?>">
				<?php echo esc_html( strtoupper( $l3_code ) ); ?>
			</a>
		<?php else : ?>
			<span class="is-off" aria-disabled="true" title="<?php esc_attr_e( 'Non ancora disponibile', 'lupin3rd' ); ?>"><?php echo esc_html( strtoupper( $l3_code ) ); ?></span>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
