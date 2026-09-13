<?php
/**
 * What the drawer carries besides the menu.
 *
 * It is rendered inside the <nav> element, not beside it, because on a narrow
 * screen that element *is* the drawer: anything that has to slide in with the
 * menu has to be one of its children. On a wide screen the block is hidden and
 * the same controls sit in the bar, where there is room for them.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="nav-extra">
	<?php get_template_part( 'template-parts/header/language-switcher' ); ?>

	<?php
	/*
	 * Hidden until the script runs: with no drawer there is nothing to close,
	 * and a button that does nothing is worse than no button.
	 */
	?>
	<button class="btn btn-secondary btn-sm nav-close" type="button" data-nav-close hidden>
		<?php esc_html_e( 'Chiudi', 'lupin3rd' ); ?>
	</button>
</div>

<?php get_template_part( 'template-parts/header/account-drawer' ); ?>
