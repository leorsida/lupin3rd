<?php
/**
 * The Thursday dispatch: the form on the home page.
 *
 * A real POST to admin-post.php, so it works with JavaScript off. The client
 * side only checks the address before the round trip.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Newsletter;

defined( 'ABSPATH' ) || exit;

$l3_notice = Newsletter::notice();
$l3_user   = wp_get_current_user();
$l3_email  = $l3_user && $l3_user->exists() ? (string) $l3_user->user_email : '';
?>
<section class="section section-cta" id="dispaccio">
	<div class="container container-narrow stack">
		<h2><?php esc_html_e( 'Un dispaccio a settimana, il giovedì', 'lupin3rd' ); ?></h2>
		<p class="lead"><?php esc_html_e( 'Tre link commentati e una nota di metodo. Nessun tracciamento nelle email, archivio pubblico, disiscrizione con un clic.', 'lupin3rd' ); ?></p>

		<form class="row row-center" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-validate>
			<input type="hidden" name="action" value="<?php echo esc_attr( Newsletter::ACTION ); ?>">
			<?php wp_nonce_field( Newsletter::ACTION ); ?>

			<label class="sr-only" for="l3-email"><?php esc_html_e( 'Indirizzo email', 'lupin3rd' ); ?></label>
			<input class="input input-inline" id="l3-email" name="l3_email" type="email" value="<?php echo esc_attr( $l3_email ); ?>" placeholder="tu@dominio.tld" required>

			<?php
			/*
			 * Left empty by anyone who can see it, which is nobody: the label
			 * and the field are hidden from the page and from screen readers,
			 * and the field is out of the tab order.
			 */
			?>
			<p class="hidden" aria-hidden="true">
				<label for="l3-website"><?php esc_html_e( 'Non compilare', 'lupin3rd' ); ?></label>
				<input id="l3-website" name="l3_website" type="text" value="" tabindex="-1" autocomplete="off">
			</p>

			<button class="btn btn-primary" type="submit"><?php esc_html_e( 'Iscrivimi', 'lupin3rd' ); ?></button>

			<p class="field-error hidden" data-error-for="l3_email"><?php esc_html_e( 'Inserisci un indirizzo email valido.', 'lupin3rd' ); ?></p>

			<?php if ( $l3_notice ) : ?>
				<p class="form-note<?php echo 'error' === $l3_notice['tone'] ? ' form-note--error' : ''; ?>" role="status">
					<?php echo esc_html( $l3_notice['text'] ); ?>
				</p>
			<?php endif; ?>
		</form>
	</div>
</section>
