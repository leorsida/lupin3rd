<?php
/**
 * Site footer.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Content_Types;

defined( 'ABSPATH' ) || exit;
?>
</main>

<footer class="pagefoot">
	<div class="container">
		<div class="foot-grid">
			<div>
				<p class="logo">
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
					<span class="motto"><?php esc_html_e( 'dal 1999', 'lupin3rd' ); ?></span>
				</p>
				<p class="meta"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			</div>

			<div>
				<h4 class="widget-title"><?php esc_html_e( 'Sezioni', 'lupin3rd' ); ?></h4>
				<?php
				if ( has_nav_menu( 'sections' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'sections',
							'container'      => false,
							'menu_class'     => 'list-plain',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
				} else {
					?>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Notizie', 'lupin3rd' ); ?></a></li>
						<li><a href="<?php echo esc_url( (string) get_post_type_archive_link( Content_Types::TUTORIAL ) ); ?>"><?php esc_html_e( 'Tutorial', 'lupin3rd' ); ?></a></li>
						<li><a href="<?php echo esc_url( (string) get_post_type_archive_link( Content_Types::DOSSIER ) ); ?>"><?php esc_html_e( 'Dossier', 'lupin3rd' ); ?></a></li>
					</ul>
					<?php
				}
				?>
			</div>

			<div>
				<h4 class="widget-title"><?php esc_html_e( 'Redazione', 'lupin3rd' ); ?></h4>
				<?php
				if ( has_nav_menu( 'desk' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'desk',
							'container'      => false,
							'menu_class'     => 'list-plain',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
				} else {
					/*
					 * Until somebody assigns a menu, list the editorial pages
					 * that exist. Built from real pages rather than fixed hrefs:
					 * a footer link to a page nobody has written yet is worse
					 * than no link.
					 */
					$l3_desk_pages = array( 'chi-siamo', 'linee-guida', 'cassetta-sicura', 'privacy', 'struttura' );
					$l3_found      = array();

					foreach ( $l3_desk_pages as $l3_slug ) {
						$l3_page = get_page_by_path( $l3_slug );

						if ( $l3_page instanceof WP_Post && 'publish' === $l3_page->post_status ) {
							$l3_found[] = $l3_page;
						}
					}

					if ( $l3_found ) {
						echo '<ul class="list-plain">';
						foreach ( $l3_found as $l3_page ) {
							printf(
								'<li><a href="%s">%s</a></li>',
								esc_url( (string) get_permalink( $l3_page ) ),
								esc_html( get_the_title( $l3_page ) )
							);
						}
						echo '</ul>';
					}
				}
				?>

				<?php
				/*
				 * Reopens the notice. A real link to /privacy/ underneath it,
				 * so that a reader with no JavaScript is not left with a
				 * control that does nothing — they get the page that explains
				 * the same thing.
				 */
				?>
				<p class="meta">
					<a class="link-quiet" href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>" data-consent-open>
						<?php esc_html_e( 'Preferenze cookie', 'lupin3rd' ); ?>
					</a>
				</p>
			</div>

			<div>
				<h4 class="widget-title"><?php esc_html_e( 'Canali', 'lupin3rd' ); ?></h4>
				<ul>
					<li><a href="<?php echo esc_url( (string) get_feed_link() ); ?>"><?php esc_html_e( 'Feed RSS', 'lupin3rd' ); ?></a></li>
					<?php
					if ( has_nav_menu( 'channels' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'channels',
								'container'      => false,
								'items_wrap'     => '%3$s',
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
					}
					?>
				</ul>
			</div>
		</div>

		<div class="foot-bottom meta">
			<p>
				<?php
				printf(
					/* translators: %s: site name. */
					esc_html__( '© 2019–%1$s %2$s · testi in CC BY-SA 4.0, codice in MIT', 'lupin3rd' ),
					esc_html( wp_date( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>
			<p><?php esc_html_e( 'Nessun cookie di profilazione · statistiche aggregate self-hosted', 'lupin3rd' ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
