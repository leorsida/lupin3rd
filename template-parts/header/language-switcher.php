<?php
/**
 * Language switch.
 *
 * Two real links, never a script that rewrites the text in place: each
 * language has its own content, its own URLs and its own feeds. Phase 1 ships
 * Italian only, so the second entry is inert until the multilingual plugin
 * arrives and fills it in.
 *
 * @package Lupin3rd
 */

use Lupin3rd\I18n;

defined( 'ABSPATH' ) || exit;

$l3_current = I18n::instance()->language();
?>
<div class="langswitch">
	<?php foreach ( array( 'it', 'en' ) as $l3_code ) : ?>
		<?php if ( $l3_code === $l3_current ) : ?>
			<span class="is-on" aria-current="true"><?php echo esc_html( strtoupper( $l3_code ) ); ?></span>
		<?php else : ?>
			<a href="<?php echo esc_url( home_url( '/' . $l3_code . '/' ) ); ?>" lang="<?php echo esc_attr( $l3_code ); ?>">
				<?php echo esc_html( strtoupper( $l3_code ) ); ?>
			</a>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
