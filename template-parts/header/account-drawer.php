<?php
/**
 * The account, as it appears in the drawer.
 *
 * Plain links, not the chip and its dropdown: a menu that opens inside a panel
 * that has just slid in is one layer too many, and on a phone the room the
 * dropdown saves is room the drawer already has.
 *
 * Same behaviour hooks as the bar's version, and the script writes to all of
 * them at once — so this is a second rendering of the same state, never a
 * second state.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Authentication;
use Lupin3rd\Community;

defined( 'ABSPATH' ) || exit;
?>
<div class="nav-account">
	<a class="btn btn-secondary btn-sm" href="<?php echo esc_url( Authentication::sign_in_url() ); ?>" data-when-signed-out>
		<?php esc_html_e( 'Accedi', 'lupin3rd' ); ?>
	</a>

	<span class="nav-account-name meta hidden" data-when-signed-in data-account-name></span>

	<a class="hidden" href="<?php echo esc_url( Authentication::account_url() ); ?>" data-when-signed-in>
		<?php esc_html_e( 'Il tuo profilo', 'lupin3rd' ); ?>
	</a>

	<a class="hidden" href="<?php echo esc_url( home_url( '/community/' ) ); ?>" data-when-signed-in data-account-public>
		<?php esc_html_e( 'Profilo pubblico', 'lupin3rd' ); ?>
	</a>

	<a class="hidden" href="<?php echo esc_url( Authentication::sign_out_url( home_url( '/' ), false ) ); ?>"
		data-when-signed-in data-account-signout>
		<?php esc_html_e( 'Esci', 'lupin3rd' ); ?>
	</a>
</div>
