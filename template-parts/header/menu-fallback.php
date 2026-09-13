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

/*
 * Which of these is the section the reader is in. The same rule the real menu
 * uses, so the two do not disagree about where you are.
 */
$l3_paths   = Template_Tags::section_paths();
$l3_here    = array_map( static fn ( array $l3_item ): string => Template_Tags::path_of( (string) $l3_item['url'] ), $l3_links );
$l3_current = '';

foreach ( $l3_paths as $l3_path ) {
	if ( in_array( $l3_path, $l3_here, true ) ) {
		$l3_current = $l3_path;
		break;
	}
}
?>
<nav id="primary-nav" aria-label="<?php esc_attr_e( 'Navigazione principale', 'lupin3rd' ); ?>">
	<?php foreach ( $l3_links as $l3_link ) : ?>
		<?php if ( $l3_link['url'] ) : ?>
			<a href="<?php echo esc_url( $l3_link['url'] ); ?>"
				<?php echo Template_Tags::path_of( $l3_link['url'] ) === $l3_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $l3_link['label'] ); ?></a>
		<?php endif; ?>
	<?php endforeach; ?>

	<?php get_template_part( 'template-parts/header/nav-extra' ); ?>
</nav>
