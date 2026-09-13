<?php
/**
 * The address and the password, both behind the current password.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Account;
use Lupin3rd\Authentication;

defined( 'ABSPATH' ) || exit;

$l3_me = wp_get_current_user();
?>
<form class="formcard stack" method="post" data-validate
	action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

	<input type="hidden" name="action" value="<?php echo esc_attr( Account::ACTION_CREDENTIALS ); ?>">
	<?php wp_nonce_field( Account::ACTION_CREDENTIALS, '_wpnonce', false ); ?>

	<div class="field">
		<label for="c-email"><?php esc_html_e( 'Email', 'lupin3rd' ); ?></label>
		<input class="input" type="email" id="c-email" name="l3_email" autocomplete="email"
			value="<?php echo esc_attr( $l3_me->user_email ); ?>">
		<p class="hint"><?php esc_html_e( 'Serve solo a recuperare l\'accesso e ad avvisarti delle risposte. Non è pubblica.', 'lupin3rd' ); ?></p>
	</div>

	<div class="field">
		<label for="c-new"><?php esc_html_e( 'Nuova password', 'lupin3rd' ); ?></label>
		<input class="input" type="password" id="c-new" name="l3_new_password" autocomplete="new-password"
			minlength="<?php echo esc_attr( (string) Authentication::MIN_PASSWORD ); ?>"
			data-password-strength="#l3-strength-new">
		<div class="meter" id="l3-strength-new" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
		<p class="hint" data-password-strength-echo
			data-level-0="<?php esc_attr_e( 'Lasciala vuota se non vuoi cambiarla.', 'lupin3rd' ); ?>"
			data-level-1="<?php esc_attr_e( 'Corta. Allungala, non complicarla.', 'lupin3rd' ); ?>"
			data-level-2="<?php esc_attr_e( 'Accettabile.', 'lupin3rd' ); ?>"
			data-level-3="<?php esc_attr_e( 'Buona.', 'lupin3rd' ); ?>"
			data-level-4="<?php esc_attr_e( 'Ottima: annotala nel gestore, non a memoria.', 'lupin3rd' ); ?>">
			<?php esc_html_e( 'Lasciala vuota se non vuoi cambiarla.', 'lupin3rd' ); ?>
		</p>
	</div>

	<div class="field">
		<label for="c-current"><?php esc_html_e( 'Password attuale', 'lupin3rd' ); ?></label>
		<input class="input" type="password" id="c-current" name="l3_current_password" required autocomplete="current-password">
		<p class="hint"><?php esc_html_e( 'Serve per cambiare l\'indirizzo o la password: una sessione rubata da sola non deve bastare a prendersi l\'account.', 'lupin3rd' ); ?></p>
		<p class="field-error hidden" data-error-for="l3_current_password"><?php esc_html_e( 'Serve la password attuale.', 'lupin3rd' ); ?></p>
	</div>

	<div class="row-between row-wrap">
		<span class="meta"><?php esc_html_e( 'Cambiando la password, le altre sessioni si chiudono.', 'lupin3rd' ); ?></span>
		<button class="btn btn-secondary" type="submit"><?php esc_html_e( 'Aggiorna', 'lupin3rd' ); ?></button>
	</div>
</form>
