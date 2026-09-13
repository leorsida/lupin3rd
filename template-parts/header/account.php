<?php
/**
 * The account corner of the header.
 *
 * Rendered signed-out for everybody, always: this markup is in every cached
 * page, so it cannot depend on who is asking. A script swaps the two states
 * from a minimal display cookie (D-017), and both states occupy the same 44px
 * box so that the swap moves nothing.
 *
 * There is no sign-out link here, and that is deliberate: ending a session
 * needs a nonce, a nonce is different for every reader, and a nonce in this
 * markup would make every page unique — which is the one thing D-017 exists to
 * prevent. «Esci» therefore leads to /accesso/, which is never cached and
 * carries the real button.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Authentication;

defined( 'ABSPATH' ) || exit;
?>
<div class="account">
	<a class="btn btn-secondary btn-nav" href="<?php echo esc_url( Authentication::sign_in_url() ); ?>" data-when-signed-out>
		<?php esc_html_e( 'Accedi', 'lupin3rd' ); ?>
	</a>

	<button class="account-chip hidden" type="button" data-when-signed-in data-account-chip
		aria-expanded="false" aria-haspopup="true" aria-controls="account-panel"
		aria-label="<?php esc_attr_e( 'Il tuo account', 'lupin3rd' ); ?>">
		<span class="avatar avatar-self" aria-hidden="true"><b></b></span>
	</button>

	<div class="account-menu hidden" id="account-panel" data-account-panel>
		<span class="account-menu-head meta" data-account-name></span>
		<a href="<?php echo esc_url( Authentication::account_url() ); ?>" data-account-profile><?php esc_html_e( 'Il tuo profilo', 'lupin3rd' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/comunita/' ) ); ?>"><?php esc_html_e( 'Comunità', 'lupin3rd' ); ?></a>
		<a href="<?php echo esc_url( Authentication::sign_in_url() ); ?>"><?php esc_html_e( 'Esci', 'lupin3rd' ); ?></a>
	</div>
</div>
