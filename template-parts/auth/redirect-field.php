<?php
/**
 * Where to land after signing in, when the reader was sent here from somewhere.
 *
 * Only a path on this site survives: `Authentication` puts the value through
 * `wp_validate_redirect()` before using it, so an open redirect cannot be built
 * out of this field.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading where to go, and validated again on the way out.
$l3_back = isset( $_GET['redirect_to'] ) ? rawurldecode( sanitize_text_field( wp_unslash( (string) $_GET['redirect_to'] ) ) ) : '';

if ( '' === $l3_back ) {
	return;
}
?>
<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $l3_back ); ?>">
