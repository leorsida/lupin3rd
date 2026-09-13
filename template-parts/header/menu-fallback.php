<?php
/**
 * Navigation shown before anyone has assigned a menu.
 *
 * A theme that renders nothing until it is configured looks broken. This is
 * the site's own structure, so the first run already makes sense.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Content_Types;

use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

$l3_links = array(
	array(
		'label' => __( 'Notizie', 'lupin3rd' ),
		'url'   => home_url( '/' ),
	),
	array(
		'label' => __( 'Tutorial', 'lupin3rd' ),
		'url'   => (string) get_post_type_archive_link( Content_Types::TUTORIAL ),
	),
	array(
		'label' => __( 'Dossier', 'lupin3rd' ),
		'url'   => (string) get_post_type_archive_link( Content_Types::DOSSIER ),
	),
	array(
		'label' => __( 'Archivio', 'lupin3rd' ),
		'url'   => Template_Tags::archive_url(),
	),
);
?>
<nav id="primary-nav" aria-label="<?php esc_attr_e( 'Navigazione principale', 'lupin3rd' ); ?>">
	<?php foreach ( $l3_links as $l3_link ) : ?>
		<?php if ( $l3_link['url'] ) : ?>
			<a href="<?php echo esc_url( $l3_link['url'] ); ?>"><?php echo esc_html( $l3_link['label'] ); ?></a>
		<?php endif; ?>
	<?php endforeach; ?>

	<?php get_template_part( 'template-parts/header/nav-extra' ); ?>
</nav>
