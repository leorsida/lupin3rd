<?php
/**
 * Sticky navigation.
 *
 * The account area renders signed-out for every reader, always. A script
 * corrects it from a display cookie, so that the page itself stays identical
 * for everybody and can be served from the static cache. See D-017.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;
?>
<header class="topnav">
	<div class="container topnav-inner">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
			<span class="motto"><?php esc_html_e( 'dal 1999', 'lupin3rd' ); ?></span>
		</a>

		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu(
				array(
					'theme_location'       => 'primary',
					'container'            => 'nav',
					'container_aria_label' => __( 'Navigazione principale', 'lupin3rd' ),
					'items_wrap'           => '%3$s',
					'depth'                => 1,
					'walker'               => new Lupin3rd\Nav_Walker(),
					'fallback_cb'          => false,
				)
			);
		} else {
			get_template_part( 'template-parts/header/menu-fallback' );
		}
		?>

		<div class="nav-tools">
			<?php get_template_part( 'template-parts/header/language-switcher' ); ?>

			<button class="icon-btn" type="button" data-search-open aria-label="<?php esc_attr_e( 'Cerca', 'lupin3rd' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
					<circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path>
				</svg>
			</button>

			<button class="icon-btn" type="button" data-theme-toggle aria-label="<?php esc_attr_e( 'Cambia tema', 'lupin3rd' ); ?>" aria-pressed="false">
				<svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
					<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"></path>
				</svg>
				<svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
					<circle cx="12" cy="12" r="4"></circle><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5M17.5 17.5 19 19M19 5l-1.5 1.5M6.5 17.5 5 19"></path>
				</svg>
			</button>

			<button class="icon-btn nav-burger" type="button" data-nav-toggle aria-label="<?php esc_attr_e( 'Menu', 'lupin3rd' ); ?>" aria-expanded="false">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
					<path d="M4 7h16M4 12h16M4 17h16"></path>
				</svg>
			</button>
		</div>
	</div>
</header>

<?php get_template_part( 'template-parts/header/search-overlay' ); ?>
