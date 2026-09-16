<?php
/**
 * The secure-drop pointer.
 *
 * Sensitive material must never arrive through a comment or a contact form,
 * so the theme says so wherever somebody might be about to try.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="widget">
	<p class="widget-title"><?php esc_html_e( 'Email sicura', 'lupin3rd' ); ?></p>
	<p><?php esc_html_e( 'Materiale sensibile invialo solo via email cifrata con PGP. Non usare i commenti per quello.', 'lupin3rd' ); ?></p>
	<a class="btn btn-secondary btn-sm" href="<?php echo esc_url( home_url( '/cassetta-sicura/' ) ); ?>">
		<?php esc_html_e( 'Come scrivere in modo sicuro', 'lupin3rd' ); ?>
	</a>
</div>
