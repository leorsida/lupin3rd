<?php
/**
 * Closing the account: two proofs and a plain description of what happens.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Account;
use Lupin3rd\Newsletter;
use Lupin3rd\Profile_Fields;

defined( 'ABSPATH' ) || exit;

$l3_me        = wp_get_current_user();
$l3_pseudonym = Profile_Fields::pseudonym( $l3_me );
$l3_on_list   = Newsletter::has_record( (string) $l3_me->user_email );
?>
<form class="formcard stack" method="post" data-close-account
	action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

	<input type="hidden" name="action" value="<?php echo esc_attr( Account::ACTION_CLOSE ); ?>">
	<?php wp_nonce_field( Account::ACTION_CLOSE, '_wpnonce', false ); ?>

	<ul class="plain-list">
		<li><?php esc_html_e( 'Non potrai più entrare.', 'lupin3rd' ); ?></li>
		<li><?php esc_html_e( 'Vieni rimosso dalla nostra community.', 'lupin3rd' ); ?></li>
		<li><?php esc_html_e( 'Quello che hai scritto resta nelle discussioni, a firma «account rimosso».', 'lupin3rd' ); ?></li>
		<li><?php esc_html_e( 'Lo pseudonimo resta comunque tuo: nessun altro può sceglierlo in fase di registrazione.', 'lupin3rd' ); ?></li>
	</ul>

	<div class="field">
		<label for="x-password"><?php esc_html_e( 'La tua password', 'lupin3rd' ); ?></label>
		<input class="input" type="password" id="x-password" name="l3_current_password"
			autocomplete="current-password" required>
	</div>

	<div class="field">
		<label for="x-confirm">
			<?php
			printf(
				/* translators: %s: the reader's own pseudonym. */
				esc_html__( 'Scrivi %s per confermare', 'lupin3rd' ),
				'<code>' . esc_html( $l3_pseudonym ) . '</code>'
			);
			?>
		</label>
		<input class="input" type="text" id="x-confirm" name="l3_confirm_pseudonym"
			autocomplete="off" autocapitalize="none" spellcheck="false" required
			data-close-confirm="<?php echo esc_attr( $l3_pseudonym ); ?>">
	</div>

	<?php if ( $l3_on_list ) : ?>
		<label class="check">
			<input type="checkbox" name="l3_drop_newsletter" value="1" checked>
			<span><?php esc_html_e( 'Toglimi anche dalla newsletter. Sono due consensi diversi: l\'iscrizione resta se togli la spunta.', 'lupin3rd' ); ?></span>
		</label>
	<?php endif; ?>

	<p>
		<button class="btn btn-danger btn-sm" type="submit" data-close-submit>
			<?php esc_html_e( 'Chiudi l\'account', 'lupin3rd' ); ?>
		</button>
	</p>
</form>
