<?php
/**
 * Open an account.
 *
 * A real POST to admin-post.php with a nonce: it works with JavaScript off,
 * and the script only puts the error next to the field instead of in a bubble.
 * Nothing here is validated in the browser for safety — the server checks the
 * same things again, and it is the only check that counts.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Authentication;

defined( 'ABSPATH' ) || exit;
?>
<form class="formcard stack" method="post" data-validate
	action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

	<input type="hidden" name="action" value="<?php echo esc_attr( Authentication::ACTION_REGISTER ); ?>">
	<?php wp_nonce_field( Authentication::ACTION_REGISTER, '_wpnonce', false ); ?>

	<?php
	/*
	 * A field no reader ever sees, and no reader ever fills. It is wrapped in
	 * the same class the stylesheet hides, rather than in `display: none` here,
	 * so that it disappears exactly once and in one place.
	 */
	?>
	<p class="hidden" aria-hidden="true">
		<label for="l3-website"><?php esc_html_e( 'Sito', 'lupin3rd' ); ?></label>
		<input class="input" type="text" id="l3-website" name="l3_website" tabindex="-1" autocomplete="off" value="">
	</p>

	<div class="field">
		<label for="r-pseudonym"><?php esc_html_e( 'Pseudonimo', 'lupin3rd' ); ?></label>
		<input class="input" type="text" id="r-pseudonym" name="l3_pseudonym" required
			autocomplete="username" placeholder="<?php esc_attr_e( 'come vuoi firmarti', 'lupin3rd' ); ?>">
		<p class="hint"><?php esc_html_e( 'Da 3 a 20 caratteri, minuscole, niente spazi. Comparirà così sotto ogni tuo intervento.', 'lupin3rd' ); ?></p>
		<p class="field-error hidden" data-error-for="l3_pseudonym"><?php esc_html_e( 'Serve uno pseudonimo, anche inventato.', 'lupin3rd' ); ?></p>
	</div>

	<div class="field">
		<label for="r-email"><?php esc_html_e( 'Email', 'lupin3rd' ); ?></label>
		<input class="input" type="email" id="r-email" name="l3_email" required
			autocomplete="email" placeholder="tu@dominio.tld">
		<p class="hint"><?php esc_html_e( 'Solo per recuperare l\'accesso e avvisarti delle risposte. Non è pubblica e non entra nella newsletter.', 'lupin3rd' ); ?></p>
		<p class="field-error hidden" data-error-for="l3_email"><?php esc_html_e( 'Indirizzo email non valido.', 'lupin3rd' ); ?></p>
	</div>

	<div class="field">
		<label for="r-password"><?php esc_html_e( 'Password', 'lupin3rd' ); ?></label>
		<input class="input" type="password" id="r-password" name="l3_password" required
			autocomplete="new-password" minlength="<?php echo esc_attr( (string) Authentication::MIN_PASSWORD ); ?>"
			placeholder="<?php esc_attr_e( 'almeno dieci caratteri', 'lupin3rd' ); ?>"
			data-password-strength="#l3-strength">
		<div class="meter" id="l3-strength" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
		<p class="hint" data-password-strength-echo
			data-level-0="<?php esc_attr_e( 'Quattro parole senza senso battono una password contorta.', 'lupin3rd' ); ?>"
			data-level-1="<?php esc_attr_e( 'Corta. Allungala, non complicarla.', 'lupin3rd' ); ?>"
			data-level-2="<?php esc_attr_e( 'Accettabile.', 'lupin3rd' ); ?>"
			data-level-3="<?php esc_attr_e( 'Buona.', 'lupin3rd' ); ?>"
			data-level-4="<?php esc_attr_e( 'Ottima: annotala nel gestore, non a memoria.', 'lupin3rd' ); ?>">
			<?php esc_html_e( 'Quattro parole senza senso battono una password contorta.', 'lupin3rd' ); ?>
		</p>
		<p class="field-error hidden" data-error-for="l3_password"><?php esc_html_e( 'Serve una password.', 'lupin3rd' ); ?></p>
	</div>

	<div class="field">
		<label for="r-pgp"><?php esc_html_e( 'Impronta PGP (facoltativa)', 'lupin3rd' ); ?></label>
		<input class="input" type="text" id="r-pgp" name="l3_pgp" placeholder="3B7A 91C4 0E52 DD18 …">
		<p class="hint"><?php esc_html_e( 'Se la dichiari, la redazione la controfirma e il profilo mostra l\'etichetta «chiave verificata».', 'lupin3rd' ); ?></p>
	</div>

	<label class="check">
		<input type="checkbox" id="r-rules" name="l3_rules" value="1" required>
		<span>
			<?php
			printf(
				/* translators: %s: link to the guidelines page. */
				esc_html__( 'Ho letto le %s: si discute del metodo, si porta la fonte, non si pubblicano dati di terzi.', 'lupin3rd' ),
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( home_url( '/linee-guida/' ) ),
					esc_html__( 'linee guida', 'lupin3rd' )
				)
			);
			?>
		</span>
	</label>
	<p class="field-error hidden" data-error-for="l3_rules"><?php esc_html_e( 'Senza questa spunta non possiamo aprire l\'account.', 'lupin3rd' ); ?></p>

	<div class="row-between row-wrap">
		<span class="meta"><?php esc_html_e( 'Nessuna verifica dell\'identità. Nessun invito necessario.', 'lupin3rd' ); ?></span>
		<button class="btn btn-primary" type="submit"><?php esc_html_e( 'Crea l\'account', 'lupin3rd' ); ?></button>
	</div>
</form>
