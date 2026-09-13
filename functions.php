<?php
/**
 * Theme bootstrap.
 *
 * This file registers the autoloader and starts the theme. It holds no logic
 * of its own: everything lives in a module under inc/, and Theme is the only
 * class that knows the module list.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/class-autoloader.php';

Lupin3rd\Autoloader::instance()->register();
Lupin3rd\Theme::instance()->boot();
