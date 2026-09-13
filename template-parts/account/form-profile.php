<?php
/**
 * What the account says about itself in public.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Account;
use Lupin3rd\Profile_Fields;

defined( 'ABSPATH' ) || exit;

$l3_me = wp_get_current_user();
?>
<form class="formcard stack" method="post" data-validate
	action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

	<input type="hidden" name="action" value="<?php echo esc_attr( Account::ACTION_PROFILE ); ?>">
	<?php wp_nonce_field( Account::ACTION_PROFILE, '_wpnonce', false ); ?>

	<div class="field">
		<label for="p-pseudonym"><?php esc_html_e( 'Pseudonimo', 'lupin3rd' ); ?></label>
		<input class="input" type="text" id="p-pseudonym" name="l3_pseudonym" required
			value="<?php echo esc_attr( Profile_Fields::pseudonym( $l3_me ) ); ?>">
		<p class="hint"><?php esc_html_e( 'Cambiandolo, gli interventi già pubblicati passano al nuovo nome. Il vecchio resta bloccato per novanta giorni.', 'lupin3rd' ); ?></p>
		<p class="field-error hidden" data-error-for="l3_pseudonym"><?php esc_html_e( 'Serve uno pseudonimo.', 'lupin3rd' ); ?></p>
	</div>

	<div class="field">
		<label for="p-bio"><?php esc_html_e( 'Due righe su di te', 'lupin3rd' ); ?></label>
		<textarea class="textarea" id="p-bio" name="l3_bio" rows="3"><?php echo esc_textarea( Profile_Fields::bio( $l3_me ) ); ?></textarea>
		<p class="hint"><?php esc_html_e( 'Testo semplice. I collegamenti non vengono pubblicati.', 'lupin3rd' ); ?></p>
	</div>

	<div class="field">
		<label for="p-pgp"><?php esc_html_e( 'Impronta PGP (facoltativa)', 'lupin3rd' ); ?></label>
		<input class="input" type="text" id="p-pgp" name="l3_pgp"
			value="<?php echo esc_attr( Profile_Fields::fingerprint( $l3_me ) ); ?>">
		<p class="hint"><?php esc_html_e( 'Se cambia, l\'etichetta «chiave verificata» sparisce finché la redazione non controfirma la nuova.', 'lupin3rd' ); ?></p>
	</div>

	<div class="field">
		<label for="p-languages"><?php esc_html_e( 'Lingue in cui scrivi', 'lupin3rd' ); ?></label>
		<input class="input" type="text" id="p-languages" name="l3_languages" placeholder="it, en"
			value="<?php echo esc_attr( implode( ', ', Profile_Fields::languages( $l3_me ) ) ); ?>">
	</div>

	<label class="check">
		<input type="checkbox" name="l3_show_join_date" value="1" <?php checked( Profile_Fields::joined( $l3_me ) > 0 ); ?>>
		<span><?php esc_html_e( 'Mostra la data di iscrizione sul profilo pubblico.', 'lupin3rd' ); ?></span>
	</label>

	<label class="check">
		<input type="checkbox" name="l3_notify_replies" value="1" <?php checked( (bool) get_user_meta( $l3_me->ID, Profile_Fields::NOTIFY_REPLIES, true ) ); ?>>
		<span><?php esc_html_e( 'Avvisami via email quando qualcuno risponde a un mio intervento.', 'lupin3rd' ); ?></span>
	</label>

	<div class="row-between row-wrap">
		<span class="meta"><?php esc_html_e( 'Email, paese e ultimo accesso non compaiono mai sul profilo: non esiste un campo che possa contenerli.', 'lupin3rd' ); ?></span>
		<button class="btn btn-primary" type="submit"><?php esc_html_e( 'Salva', 'lupin3rd' ); ?></button>
	</div>
</form>
