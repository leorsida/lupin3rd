<?php
/**
 * Document head and site header.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Marks;

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<?php
/*
 * `no-js` is removed by the snippet Assets prints as the first thing in the
 * head, before the first paint. Everything that JavaScript would otherwise
 * rearrange after the fact — the drawer, below — is decided by CSS from that
 * class, so the page is laid out once instead of twice. The markup is the same
 * for every reader, which is what D-017 requires.
 */
?>
<html class="no-js" <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#content"><?php esc_html_e( 'Salta al contenuto', 'lupin3rd' ); ?></a>

<?php get_template_part( 'template-parts/header/statusbar' ); ?>
<?php get_template_part( 'template-parts/header/navbar' ); ?>

<main id="content">
