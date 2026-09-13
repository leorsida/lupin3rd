<?php
/**
 * Open a session.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Authentication;

defined( 'ABSPATH' ) || exit;
?>
<form class="formcard stack" method="post" data-validate
	action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

	<input type="hidden" name="action" value="<?php echo esc_attr( Authentication::ACTION_SIGN_IN ); ?>">
	<?php wp_nonce_field( Authentication::ACTION_SIGN_IN, '_wpnonce', false ); ?>
	<?php get_template_part( 'template-parts/auth/redirect-field' ); ?>

	<p class="hidden" aria-hidden="true">
		<label for="l3-website-in"><?php esc_html_e( 'Sito', 'lupin3rd' ); ?></label>
		<input class="input" type="text" id="l3-website-in" name="l3_website" tabindex="-1" autocomplete="off" value="">
	</p>

	<div class="field">
		<label for="a-identity"><?php esc_html_e( 'Pseudonimo o email', 'lupin3rd' ); ?></label>
		<input class="input" type="text" id="a-identity" name="l3_identity" required autocomplete="username">
		<p class="field-error hidden" data-error-for="l3_identity"><?php esc_html_e( 'Serve lo pseudonimo o l\'email.', 'lupin3rd' ); ?></p>
	</div>

	<div class="field">
		<label for="a-password"><?php esc_html_e( 'Password', 'lupin3rd' ); ?></label>
		<input class="input" type="password" id="a-password" name="l3_password" required autocomplete="current-password">
		<p class="field-error hidden" data-error-for="l3_password"><?php esc_html_e( 'Serve la password.', 'lupin3rd' ); ?></p>
	</div>

	<div class="row-between row-wrap">
		<a class="meta link-quiet" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
			<?php esc_html_e( 'Password persa?', 'lupin3rd' ); ?>
		</a>
		<button class="btn btn-primary" type="submit"><?php esc_html_e( 'Entra', 'lupin3rd' ); ?></button>
	</div>
</form>
